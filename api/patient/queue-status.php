<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/permissions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../common/response.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    sendJsonError('Method not allowed.', 405);
}

$user = requireRole('patient');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

try {
    $db = getDatabaseConnection();
    $patientId = getAuthenticatedPatientId($db, (int) $user['user_account_id']);
    if ($patientId === null) {
        sendJsonError('No patient profile linked to this user account.', 404);
    }

    $today = (new DateTimeImmutable('today'))->format('Y-m-d');
    $queueStatement = $db->prepare(
        'SELECT q.queue_id, q.clinic_code, q.queue_date, q.queue_number, q.status,
                COALESCE(c.name, q.clinic_code) AS clinic_name,
                CONCAT(s.first_name, " ", s.last_name) AS doctor_name
         FROM queues q
         INNER JOIN doctors d ON d.doctor_id = q.doctor_id
         INNER JOIN staff s ON s.staff_id = d.staff_id
         LEFT JOIN clinics c ON c.clinic_id = q.clinic_id
         WHERE q.patient_id = :patient_id
           AND q.queue_date = :queue_date
           AND q.status IN ("waiting", "called", "in_consultation")
         ORDER BY q.checked_in_at DESC, q.queue_id DESC
         LIMIT 1'
    );
    $queueStatement->execute([
        'patient_id' => $patientId,
        'queue_date' => $today,
    ]);
    $queue = $queueStatement->fetch();

    if (!is_array($queue)) {
        sendJsonSuccess(['queue' => null]);
    }

    $aheadStatement = $db->prepare(
        'SELECT COUNT(*)
         FROM queues
         WHERE clinic_code = :clinic_code
           AND queue_date = :queue_date
           AND queue_number < :queue_number
           AND status IN ("waiting", "called", "in_consultation")'
    );
    $aheadStatement->execute([
        'clinic_code' => $queue['clinic_code'],
        'queue_date' => $queue['queue_date'],
        'queue_number' => $queue['queue_number'],
    ]);

    $callingStatement = $db->prepare(
        'SELECT queue_number
         FROM queues
         WHERE clinic_code = :clinic_code
           AND queue_date = :queue_date
           AND status = "called"
         ORDER BY called_at DESC, queue_id DESC'
    );
    $callingStatement->execute([
        'clinic_code' => $queue['clinic_code'],
        'queue_date' => $queue['queue_date'],
    ]);

    sendJsonSuccess([
        'queue' => [
            'ticket_number' => (int) $queue['queue_number'],
            'status' => $queue['status'],
            'people_ahead' => (int) $aheadStatement->fetchColumn(),
            'now_calling' => array_map('intval', $callingStatement->fetchAll(PDO::FETCH_COLUMN)),
            'clinic_name' => $queue['clinic_name'],
            'doctor_name' => $queue['doctor_name'],
        ],
    ]);
} catch (Throwable $exception) {
    error_log('Patient queue status API error: ' . $exception->getMessage());
    sendJsonError('Unable to load your live queue status.', 500);
}
