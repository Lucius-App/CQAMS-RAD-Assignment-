<?php
declare(strict_types=1);

require_once __DIR__ . '/common.php';

requireQueueAccess();
try {
    $db = getDatabaseConnection();
    $query = trim((string) ($_GET['q'] ?? ''));
    if ($query === '') {
        sendJsonSuccess(['appointments' => []]);
    }
    $stmt = $db->prepare(
        'SELECT a.appointment_id, a.appointment_date, a.start_time, a.status,
                a.patient_id, a.doctor_id, p.registration_number,
                CONCAT(p.first_name, " ", p.last_name) AS patient_name,
                CONCAT(s.first_name, " ", s.last_name) AS doctor_name
         FROM appointments a
         INNER JOIN patients p ON p.patient_id = a.patient_id
         INNER JOIN doctors d ON d.doctor_id = a.doctor_id
         INNER JOIN staff s ON s.staff_id = d.staff_id
         WHERE a.appointment_date = :today
           AND a.status IN ("scheduled", "confirmed")
           AND a.approval_status = "approved"
           AND (p.registration_number LIKE :reference
                OR p.first_name LIKE :first_name
                OR p.last_name LIKE :last_name)
         ORDER BY a.start_time
         LIMIT 20'
    );
    $like = '%' . $query . '%';
    $stmt->execute([
        'today' => queueToday(),
        'reference' => $like,
        'first_name' => $like,
        'last_name' => $like,
    ]);
    sendJsonSuccess(['appointments' => $stmt->fetchAll()]);
} catch (Throwable $exception) {
    queueException($exception);
}
