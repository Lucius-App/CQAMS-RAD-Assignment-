<?php
declare(strict_types=1);

require_once __DIR__ . '/common.php';

requireQueueAccess();
try {
    $db = getDatabaseConnection();
    $date = $_GET['date'] ?? queueToday();
    if (!is_string($date) || $date !== queueToday()) {
        sendJsonError('Only today\'s active queue can be operated.', 422);
    }
    if (!requestMethodIs('GET')) {
        sendJsonError('Method not allowed.', 405);
    }
    $stmt = $db->prepare(
        'SELECT q.queue_id, q.queue_number, q.status, q.checked_in_at, q.called_at,
                q.completed_at, q.appointment_id, q.doctor_id,
                p.registration_number, CONCAT(p.first_name, " ", p.last_name) AS patient_name,
                CONCAT(s.first_name, " ", s.last_name) AS doctor_name
         FROM queues q
         INNER JOIN patients p ON p.patient_id = q.patient_id
         INNER JOIN doctors d ON d.doctor_id = q.doctor_id
         INNER JOIN staff s ON s.staff_id = d.staff_id
         WHERE q.clinic_code = "MAIN" AND q.queue_date = :queue_date
         ORDER BY q.queue_number'
    );
    $stmt->execute(['queue_date' => $date]);
    sendJsonSuccess(['queue' => $stmt->fetchAll()]);
} catch (Throwable $exception) {
    queueException($exception);
}
