<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/permissions.php';
require_once __DIR__ . '/../common/response.php';

requireRole('administrator');

function reportDate(mixed $value, string $field, string $fallback): string
{
    $date = is_string($value) && $value !== '' ? $value : $fallback;
    $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
    $errors = DateTimeImmutable::getLastErrors();
    $hasErrors = is_array($errors) && ($errors['warning_count'] > 0 || $errors['error_count'] > 0);
    if ($parsed === false || $hasErrors || $parsed->format('Y-m-d') !== $date) {
        sendJsonError($field . ' must use the YYYY-MM-DD format.', 422);
    }
    return $date;
}

try {
    $db = getDatabaseConnection();
    $today = (new DateTimeImmutable('today'))->format('Y-m-d');
    $from = reportDate($_GET['from'] ?? null, 'Start date', $today);
    $to = reportDate($_GET['to'] ?? null, 'End date', $today);
    if ($from > $to) {
        sendJsonError('Start date cannot be after end date.', 422);
    }

    $appointmentCounts = $db->prepare(
        'SELECT status, COUNT(*) AS total
         FROM appointments
        WHERE approval_status = "approved"
          AND appointment_date BETWEEN :appointment_from AND :appointment_to
         GROUP BY status'
    );
    $appointmentCounts->execute(['appointment_from' => $from, 'appointment_to' => $to]);
    $appointments = [];
    foreach ($appointmentCounts->fetchAll() as $row) {
        $appointments[$row['status']] = (int) $row['total'];
    }

    $queueCounts = $db->prepare(
        'SELECT status, COUNT(*) AS total
         FROM queues
         WHERE queue_date BETWEEN :queue_from AND :queue_to
         GROUP BY status'
    );
    $queueCounts->execute(['queue_from' => $from, 'queue_to' => $to]);
    $queues = [];
    foreach ($queueCounts->fetchAll() as $row) {
        $queues[$row['status']] = (int) $row['total'];
    }

    $dashboardAppointments = $db->prepare(
        'SELECT COUNT(*) FROM appointments
         WHERE approval_status = "approved" AND appointment_date = :today_appointments'
    );
    $dashboardAppointments->execute(['today_appointments' => $today]);
    $dashboardQueues = $db->prepare(
        'SELECT COUNT(*) FROM queues WHERE queue_date = :today_queues'
    );
    $dashboardQueues->execute(['today_queues' => $today]);
    $waitingQueues = $db->prepare(
        'SELECT COUNT(*) FROM queues
         WHERE queue_date = :today_waiting AND status IN ("waiting", "called", "in_consultation")'
    );
    $waitingQueues->execute(['today_waiting' => $today]);
    $activeDoctors = $db->query(
        'SELECT COUNT(*)
         FROM doctors d
         INNER JOIN staff s ON s.staff_id = d.staff_id
         INNER JOIN user_accounts u ON u.user_account_id = s.user_account_id
         WHERE u.is_active = 1'
    )->fetchColumn();

    $daily = $db->prepare(
        'SELECT dates.report_date,
                COALESCE(a.appointment_total, 0) AS appointment_total,
                COALESCE(a.completed_appointments, 0) AS completed_appointments,
                COALESCE(a.cancelled_appointments, 0) AS cancelled_appointments,
                COALESCE(q.queue_total, 0) AS queue_total,
                COALESCE(q.completed_queue, 0) AS completed_queue,
                COALESCE(q.skipped_queue, 0) AS skipped_queue
         FROM (
             SELECT DISTINCT appointment_date AS report_date
             FROM appointments
             WHERE approval_status = "approved"
               AND appointment_date BETWEEN :daily_appointment_from AND :daily_appointment_to
             UNION
             SELECT DISTINCT queue_date AS report_date
             FROM queues
             WHERE queue_date BETWEEN :daily_queue_from AND :daily_queue_to
         ) dates
         LEFT JOIN (
             SELECT appointment_date, COUNT(*) AS appointment_total,
                    SUM(status = "completed") AS completed_appointments,
                    SUM(status = "cancelled") AS cancelled_appointments
             FROM appointments
             WHERE approval_status = "approved"
               AND appointment_date BETWEEN :summary_appointment_from AND :summary_appointment_to
             GROUP BY appointment_date
         ) a ON a.appointment_date = dates.report_date
         LEFT JOIN (
             SELECT queue_date, COUNT(*) AS queue_total,
                    SUM(status = "completed") AS completed_queue,
                    SUM(status = "skipped") AS skipped_queue
             FROM queues
             WHERE queue_date BETWEEN :summary_queue_from AND :summary_queue_to
             GROUP BY queue_date
         ) q ON q.queue_date = dates.report_date
         ORDER BY dates.report_date DESC'
    );
    $daily->execute([
        'daily_appointment_from' => $from,
        'daily_appointment_to' => $to,
        'daily_queue_from' => $from,
        'daily_queue_to' => $to,
        'summary_appointment_from' => $from,
        'summary_appointment_to' => $to,
        'summary_queue_from' => $from,
        'summary_queue_to' => $to,
    ]);

    sendJsonSuccess([
        'today' => [
            'appointments' => (int) $dashboardAppointments->fetchColumn(),
            'queue_total' => (int) $dashboardQueues->fetchColumn(),
            'queue_active' => (int) $waitingQueues->fetchColumn(),
            'active_doctors' => (int) $activeDoctors,
        ],
        'summary' => [
            'from' => $from,
            'to' => $to,
            'appointments' => $appointments,
            'queues' => $queues,
        ],
        'daily' => $daily->fetchAll(),
    ]);
} catch (Throwable $exception) {
    error_log('CQAMS administrator metrics error: ' . $exception->getMessage());
    sendJsonError('The administrator statistics could not be loaded.', 500);
}
