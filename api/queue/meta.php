<?php
declare(strict_types=1);

require_once __DIR__ . '/common.php';

requireQueueAccess();
try {
    $db = getDatabaseConnection();
    $doctors = $db->query(
        'SELECT d.doctor_id, CONCAT(s.first_name, " ", s.last_name) AS doctor_name
         FROM doctors d
         INNER JOIN staff s ON s.staff_id = d.staff_id
         INNER JOIN user_accounts u ON u.user_account_id = s.user_account_id
         WHERE u.is_active = 1
         ORDER BY s.last_name, s.first_name'
    )->fetchAll();
    $patients = $db->query(
        'SELECT patient_id, registration_number, first_name, last_name
         FROM patients WHERE is_active = 1 ORDER BY last_name, first_name LIMIT 1000'
    )->fetchAll();
    sendJsonSuccess(['doctors' => $doctors, 'patients' => $patients]);
} catch (Throwable $exception) {
    queueException($exception);
}
