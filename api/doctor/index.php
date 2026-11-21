<?php
declare(strict_types=1);

require_once __DIR__ . '/common.php';

$user = requireDoctorAccess();
try {
    $db = getDatabaseConnection();
    $doctorId = getAuthenticatedDoctorId($db, (int) $user['user_account_id']);
    $today = (new DateTimeImmutable('today'))->format('Y-m-d');

    $appointments = $db->prepare(
        'SELECT a.appointment_id, a.patient_id, a.appointment_date, a.start_time,
                a.end_time, a.status, a.reason, p.registration_number,
                CONCAT(p.first_name, " ", p.last_name) AS patient_name,
                p.date_of_birth, p.sex, p.phone, p.email
         FROM appointments a
         INNER JOIN patients p ON p.patient_id = a.patient_id
         WHERE a.doctor_id = :doctor_id AND a.appointment_date = :today
           AND a.approval_status = "approved"
         ORDER BY a.start_time'
    );
    $appointments->execute(['doctor_id' => $doctorId, 'today' => $today]);

    $queue = $db->prepare(
        'SELECT q.queue_id, q.queue_number, q.status, q.appointment_id, q.patient_id,
                q.checked_in_at, q.called_at, p.registration_number,
                CONCAT(p.first_name, " ", p.last_name) AS patient_name,
                p.date_of_birth, p.sex, p.phone, c.consultation_id
         FROM queues q
         INNER JOIN patients p ON p.patient_id = q.patient_id
         LEFT JOIN consultations c ON c.queue_id = q.queue_id AND c.status = "in_progress"
         WHERE q.doctor_id = :doctor_id AND q.queue_date = :today
         ORDER BY q.queue_number'
    );
    $queue->execute(['doctor_id' => $doctorId, 'today' => $today]);

    $consultations = $db->prepare(
        'SELECT c.consultation_id, c.queue_id, c.status, c.started_at, c.ended_at,
                c.notes, c.outcome, q.queue_number, q.patient_id,
                CONCAT(p.first_name, " ", p.last_name) AS patient_name,
                p.registration_number
         FROM consultations c
         INNER JOIN queues q ON q.queue_id = c.queue_id
         INNER JOIN patients p ON p.patient_id = q.patient_id
         WHERE c.doctor_id = :doctor_id
         ORDER BY c.created_at DESC
         LIMIT 100'
    );
    $consultations->execute(['doctor_id' => $doctorId]);

    sendJsonSuccess([
        'appointments' => $appointments->fetchAll(),
        'queue' => $queue->fetchAll(),
        'consultations' => $consultations->fetchAll(),
    ]);
} catch (Throwable $exception) {
    doctorApiException($exception);
}
