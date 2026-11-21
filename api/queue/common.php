<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/includes/permissions.php';
require_once dirname(__DIR__, 2) . '/api/common/validation.php';

function requireQueueAccess(): array
{
    return requireRole('administrator');
}

function requireQueueMutation(): void
{
    requireRole('administrator');
    startSecureSession();
    if (!isValidCsrfToken($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
        sendJsonError('Your session token is invalid or has expired. Refresh the page and try again.', 419);
    }
}

function queueToday(): string
{
    return (new DateTimeImmutable('today'))->format('Y-m-d');
}

function queueActionId(): int
{
    $value = $_GET['queue_id'] ?? null;
    $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($id === false) {
        sendJsonError('A valid queue ID is required.', 400);
    }
    return (int) $id;
}

function queueRecord(PDO $db, int $queueId): ?array
{
    $stmt = $db->prepare(
        'SELECT q.queue_id, q.clinic_code, q.queue_date, q.queue_number, q.patient_id,
                q.appointment_id, q.doctor_id, q.status, q.checked_in_at, q.called_at,
                q.completed_at, q.updated_at, p.registration_number,
                CONCAT(p.first_name, " ", p.last_name) AS patient_name,
                CONCAT(s.first_name, " ", s.last_name) AS doctor_name
         FROM queues q
         INNER JOIN patients p ON p.patient_id = q.patient_id
         INNER JOIN doctors d ON d.doctor_id = q.doctor_id
         INNER JOIN staff s ON s.staff_id = d.staff_id
         WHERE q.queue_id = :queue_id'
    );
    $stmt->execute(['queue_id' => $queueId]);
    $row = $stmt->fetch();
    return is_array($row) ? $row : null;
}

function recordQueueEvent(PDO $db, int $queueId, ?string $previous, string $next, int $userId, ?string $note = null): void
{
    $stmt = $db->prepare(
        'INSERT INTO queue_events
            (queue_id, previous_status, new_status, changed_by_user_account_id, event_note)
         VALUES (:queue_id, :previous_status, :new_status, :user_id, :event_note)'
    );
    $stmt->execute([
        'queue_id' => $queueId,
        'previous_status' => $previous,
        'new_status' => $next,
        'user_id' => $userId,
        'event_note' => $note,
    ]);
}

function queueException(Throwable $exception): never
{
    if ($exception instanceof InvalidArgumentException || $exception instanceof DomainException) {
        sendJsonError($exception->getMessage(), $exception instanceof DomainException ? 409 : 422);
    }
    if ($exception instanceof PDOException && $exception->getCode() === '23000') {
        sendJsonError('The patient is already in today\'s queue or the queue number was taken. Refresh and try again.', 409);
    }
    error_log('CQAMS queue API error: ' . $exception->getMessage());
    sendJsonError('The queue request could not be completed.', 500);
}
