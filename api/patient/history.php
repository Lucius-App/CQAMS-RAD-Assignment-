<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/permissions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../common/response.php';

$user = requireRole('patient');

try {
    $db = getDatabaseConnection();
    $patientId = getAuthenticatedPatientId($db, (int) $user['user_account_id']);
    if ($patientId === null) {
        sendJsonError('No patient profile linked to this user account.', 404);
    }

    // Consultations for this patient
    $stmt = $db->prepare(
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
    $stmt->execute(['patient_id' => $patientId]);
    $consultations = $stmt->fetchAll();

    // Fetch prescriptions for each consultation
    foreach ($consultations as &$consultation) {
        $rxStmt = $db->prepare(
            'SELECT prescription_id, medicine_name, dosage, frequency, duration, instructions
             FROM prescriptions
             WHERE consultation_id = :cid
             ORDER BY prescription_id'
        );
        $rxStmt->execute(['cid' => $consultation['consultation_id']]);
        $consultation['prescriptions'] = $rxStmt->fetchAll();
    }
    unset($consultation);

    sendJsonSuccess(['history' => $consultations]);
} catch (Throwable $e) {
    error_log('Patient history API error: ' . $e->getMessage());
    sendJsonError('Unable to load medical history.', 500);
}
