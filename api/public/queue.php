<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__) . '/common/response.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    sendJsonError('Method not allowed.', 405);
}

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

try {
    $db = getDatabaseConnection();
    $today = (new DateTimeImmutable('today'))->format('Y-m-d');
    $clinicCode = 'MAIN';

    $current = $db->prepare(
        'SELECT q.queue_number, q.status, q.called_at,
                CONCAT(s.first_name, " ", s.last_name) AS doctor_name
         FROM queues q
         INNER JOIN doctors d ON d.doctor_id = q.doctor_id
         INNER JOIN staff s ON s.staff_id = d.staff_id
         INNER JOIN user_accounts u ON u.user_account_id = s.user_account_id
         WHERE q.clinic_code = :current_clinic
           AND q.queue_date = :current_date
           AND q.status = "called"
           AND u.is_active = 1
         ORDER BY q.called_at DESC, q.queue_id DESC
         LIMIT 1'
    );
    $current->execute([
        'current_clinic' => $clinicCode,
        'current_date' => $today,
    ]);

    $recent = $db->prepare(
        'SELECT q.queue_number, q.status,
                CONCAT(s.first_name, " ", s.last_name) AS doctor_name
         FROM queues q
         INNER JOIN doctors d ON d.doctor_id = q.doctor_id
         INNER JOIN staff s ON s.staff_id = d.staff_id
         INNER JOIN user_accounts u ON u.user_account_id = s.user_account_id
         WHERE q.clinic_code = :recent_clinic
           AND q.queue_date = :recent_date
           AND q.status IN ("called", "in_consultation", "completed")
           AND u.is_active = 1
         ORDER BY q.called_at DESC, q.queue_id DESC
         LIMIT 5'
    );
    $recent->execute([
        'recent_clinic' => $clinicCode,
        'recent_date' => $today,
    ]);

    $waiting = $db->prepare(
        'SELECT COUNT(*)
         FROM queues q
         INNER JOIN doctors d ON d.doctor_id = q.doctor_id
         INNER JOIN staff s ON s.staff_id = d.staff_id
         INNER JOIN user_accounts u ON u.user_account_id = s.user_account_id
         WHERE q.clinic_code = :waiting_clinic
           AND q.queue_date = :waiting_date
           AND q.status = "waiting"
           AND u.is_active = 1'
    );
    $waiting->execute([
        'waiting_clinic' => $clinicCode,
        'waiting_date' => $today,
    ]);

    $currentCall = $current->fetch();
    sendJsonSuccess([
        'clinic_name' => 'CQAMS Clinic',
        'current' => is_array($currentCall) ? $currentCall : null,
        'recent' => $recent->fetchAll(),
        'waiting_count' => (int) $waiting->fetchColumn(),
        'updated_at' => (new DateTimeImmutable())->format(DateTimeInterface::ATOM),
    ]);
} catch (Throwable $exception) {
    error_log('CQAMS public queue error: ' . $exception->getMessage());
    sendJsonError('The public queue display is temporarily unavailable.', 500);
}
