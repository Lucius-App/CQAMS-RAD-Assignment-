<?php
declare(strict_types=1);

require_once __DIR__ . '/common.php';

$user = requireAppointmentAccess();
try {
    $connection = getDatabaseConnection();
    $clinics = $connection->query(
        'SELECT clinic_id, clinic_code, name, address, phone, photo_url
         FROM clinics WHERE is_active = 1 ORDER BY clinic_id'
    )->fetchAll();

    $doctors = $connection->query(
        'SELECT d.doctor_id, d.clinic_id, CONCAT(s.first_name, " ", s.last_name) AS doctor_name,
                d.specialization, COALESCE(c.name, "Central HQ") AS clinic_name
         FROM doctors d
         INNER JOIN staff s ON s.staff_id = d.staff_id
         INNER JOIN user_accounts u ON u.user_account_id = s.user_account_id
         LEFT JOIN clinics c ON c.clinic_id = d.clinic_id
         WHERE u.is_active = 1
         ORDER BY s.last_name, s.first_name'
    )->fetchAll();

    $patients = [];
    if ($user['role'] === 'administrator') {
        $patients = $connection->query(
            'SELECT patient_id, registration_number, first_name, last_name
             FROM patients WHERE is_active = 1 ORDER BY last_name, first_name LIMIT 1000'
        )->fetchAll();
    }

    sendJsonSuccess([
        'clinics' => $clinics,
        'doctors' => $doctors,
        'patients' => $patients,
    ]);
} catch (Throwable $exception) {
    appointmentApiException($exception);
}
