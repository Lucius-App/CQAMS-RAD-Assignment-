<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/permissions.php';

requireRole('administrator');

function reportDate(mixed $value, string $field, string $fallback): string
{
    $date = is_string($value) && $value !== '' ? $value : $fallback;
    $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
    $errors = DateTimeImmutable::getLastErrors();
    $hasErrors = is_array($errors) && ($errors['warning_count'] > 0 || $errors['error_count'] > 0);
    if ($parsed === false || $hasErrors || $parsed->format('Y-m-d') !== $date) {
        http_response_code(422);
        exit('Invalid date parameter.');
    }
    return $date;
}

try {
    $db = getDatabaseConnection();
    $today = (new DateTimeImmutable('today'))->format('Y-m-d');
    $from = reportDate($_GET['from'] ?? null, 'Start date', $today);
    $to = reportDate($_GET['to'] ?? null, 'End date', $today);

    if ($from > $to) {
        http_response_code(422);
        exit('Start date cannot be after end date.');
    }

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

    $filename = sprintf('cqams_operational_report_%s_to_%s.csv', $from, $to);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');

    // Output UTF-8 BOM for Microsoft Excel compatibility
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

    // CSV Header row
    fputcsv($output, [
        'Report Date',
        'Total Appointments',
        'Completed Appointments',
        'Cancelled Appointments',
        'Total Queue Entries',
        'Completed Queue',
        'Skipped Queue',
    ]);

    // Data rows
    while ($row = $daily->fetch()) {
        fputcsv($output, [
            $row['report_date'],
            (int) $row['appointment_total'],
            (int) $row['completed_appointments'],
            (int) $row['cancelled_appointments'],
            (int) $row['queue_total'],
            (int) $row['completed_queue'],
            (int) $row['skipped_queue'],
        ]);
    }

    fclose($output);
    exit;
} catch (Throwable $e) {
    error_log('CSV Export error: ' . $e->getMessage());
    http_response_code(500);
    exit('Failed to export CSV report.');
}
