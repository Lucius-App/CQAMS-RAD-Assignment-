<?php
declare(strict_types=1);

require_once __DIR__ . '/common.php';

requireQueueAccess();
try {
    requireQueueMutation();
    $db = getDatabaseConnection();
    $body = getJsonRequestBody();
    $appointmentId = filter_var($body['appointment_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $patientId = filter_var($body['patient_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $doctorId = filter_var($body['doctor_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $isWalkIn = $appointmentId === false;

    $db->beginTransaction();
    if ($isWalkIn) {
        if ($patientId === false || $doctorId === false) {
            throw new InvalidArgumentException('A walk-in patient and assigned doctor are required.');
        }
        $patientStmt = $db->prepare('SELECT patient_id FROM patients WHERE patient_id = :id AND is_active = 1');
        $patientStmt->execute(['id' => $patientId]);
        if ($patientStmt->fetchColumn() === false) {
            throw new InvalidArgumentException('The selected patient does not exist.');
        }
    } else {
        $appointmentStmt = $db->prepare(
            'SELECT appointment_id, patient_id, doctor_id, appointment_date, status, approval_status
             FROM appointments WHERE appointment_id = :id FOR UPDATE'
        );
        $appointmentStmt->execute(['id' => $appointmentId]);
        $appointment = $appointmentStmt->fetch();
        if (
            !is_array($appointment)
            || !in_array($appointment['status'], ['scheduled', 'confirmed'], true)
            || $appointment['approval_status'] !== 'approved'
            || $appointment['appointment_date'] !== queueToday()
        ) {
            throw new DomainException('This appointment is not eligible for check-in.');
        }
        if ((int) $appointment['patient_id'] !== (int) ($patientId ?: $appointment['patient_id'])) {
            throw new DomainException('Patient identity does not match the appointment.');
        }
        $patientId = (int) $appointment['patient_id'];
        $doctorId = (int) $appointment['doctor_id'];
    }

    $existingStmt = $db->prepare(
        'SELECT queue_id FROM queues
         WHERE clinic_code = "MAIN" AND queue_date = :date AND patient_id = :patient_id
         FOR UPDATE'
    );
    $existingStmt->execute(['date' => queueToday(), 'patient_id' => $patientId]);
    if ($existingStmt->fetchColumn() !== false) {
        throw new DomainException('This patient is already checked in today.');
    }

    $maxStmt = $db->prepare(
        'SELECT COALESCE(MAX(queue_number), 0) FROM queues
         WHERE clinic_code = "MAIN" AND queue_date = :date FOR UPDATE'
    );
    $maxStmt->execute(['date' => queueToday()]);
    $queueNumber = (int) $maxStmt->fetchColumn() + 1;
    $user = getAuthenticatedUser();
    $insert = $db->prepare(
        'INSERT INTO queues
            (clinic_code, queue_date, queue_number, patient_id, appointment_id, doctor_id, checked_in_by_user_account_id)
         VALUES ("MAIN", :date, :number, :patient_id, :appointment_id, :doctor_id, :user_id)'
    );
    $insert->execute([
        'date' => queueToday(),
        'number' => $queueNumber,
        'patient_id' => $patientId,
        'appointment_id' => $isWalkIn ? null : $appointmentId,
        'doctor_id' => $doctorId,
        'user_id' => $user['user_account_id'],
    ]);
    $queueId = (int) $db->lastInsertId();
    recordQueueEvent($db, $queueId, null, 'waiting', (int) $user['user_account_id'], $isWalkIn ? 'Walk-in check-in' : 'Appointment check-in');
    $db->commit();
    sendJsonSuccess(['queue' => queueRecord($db, $queueId)], 201);
} catch (Throwable $exception) {
    if (isset($db) && $db instanceof PDO && $db->inTransaction()) {
        $db->rollBack();
    }
    queueException($exception);
}
