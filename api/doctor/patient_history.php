<?php
declare(strict_types=1);

require_once __DIR__ . '/common.php';

$user = requireDoctorAccess();

try {
    $db = getDatabaseConnection();
    $doctorId = getAuthenticatedDoctorId($db, (int) $user['user_account_id']);

    $patientId = filter_var($_GET['patient_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($patientId === false) {
        sendJsonError('A valid patient ID is required.', 422);
    }

    $accessStmt = $db->prepare(
        'SELECT 1
         FROM queues
         WHERE patient_id = :queue_patient_id AND doctor_id = :queue_doctor_id
         UNION ALL
         SELECT 1
         FROM appointments
         WHERE patient_id = :appointment_patient_id
           AND doctor_id = :appointment_doctor_id
           AND approval_status = "approved"
         LIMIT 1'
    );
    $accessStmt->execute([
        'queue_patient_id' => $patientId,
        'queue_doctor_id' => $doctorId,
        'appointment_patient_id' => $patientId,
        'appointment_doctor_id' => $doctorId,
    ]);
    if ($accessStmt->fetchColumn() === false) {
        sendJsonError('Patient record not found or not assigned to you.', 404);
    }

    // Fetch patient demographics
    $patientStmt = $db->prepare(
        'SELECT patient_id, registration_number, national_id, first_name, last_name,
                date_of_birth, sex, phone, email, address, emergency_contact_name,
                emergency_contact_phone, is_active, created_at
         FROM patients
         WHERE patient_id = :patient_id
         LIMIT 1'
    );
    $patientStmt->execute(['patient_id' => $patientId]);
    $patient = $patientStmt->fetch();

    if (!is_array($patient)) {
        sendJsonError('Patient record not found.', 404);
    }

    // Fetch all consultations for this patient
    $consultStmt = $db->prepare(
        'SELECT c.consultation_id, c.queue_id, c.doctor_id, c.status, c.started_at, c.ended_at,
                c.notes, c.outcome, q.queue_date, q.queue_number,
                CONCAT(s.first_name, " ", s.last_name) AS doctor_name,
                d.specialization, COALESCE(cl.name, "Central HQ") AS clinic_name
         FROM consultations c
         INNER JOIN queues q ON q.queue_id = c.queue_id
         INNER JOIN doctors d ON d.doctor_id = c.doctor_id
         INNER JOIN staff s ON s.staff_id = d.staff_id
         LEFT JOIN clinics cl ON cl.clinic_id = q.clinic_id
         WHERE q.patient_id = :patient_id
         ORDER BY c.started_at DESC'
    );
    $consultStmt->execute(['patient_id' => $patientId]);
    $consultations = $consultStmt->fetchAll();

    // Fetch prescriptions for each consultation
    foreach ($consultations as &$consultation) {
        $rxStmt = $db->prepare(
            'SELECT prescription_id, medicine_name, dosage, frequency, duration, instructions
             FROM prescriptions
             WHERE consultation_id = :cid
             ORDER BY prescription_id ASC'
        );
        $rxStmt->execute(['cid' => $consultation['consultation_id']]);
        $consultation['prescriptions'] = $rxStmt->fetchAll();
    }
    unset($consultation);

    sendJsonSuccess([
        'patient' => $patient,
        'history' => $consultations,
    ]);
} catch (Throwable $exception) {
    doctorApiException($exception);
}
