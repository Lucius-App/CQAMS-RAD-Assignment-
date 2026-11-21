<?php
declare(strict_types=1);

require_once __DIR__ . '/common.php';

requireQueueAccess();
$queueId = queueActionId();
try {
    requireQueueMutation();
    $db = getDatabaseConnection();
    $body = getJsonRequestBody();
    $action = $body['action'] ?? '';
    $user = getAuthenticatedUser();
    $db->beginTransaction();
    $lock = $db->prepare('SELECT * FROM queues WHERE queue_id = :id FOR UPDATE');
    $lock->execute(['id' => $queueId]);
    $queue = $lock->fetch();
    if (!is_array($queue)) {
        throw new InvalidArgumentException('Queue record not found.');
    }
    $status = $queue['status'];
    $next = null;
    $note = null;
    $updates = [];
    if ($action === 'call_next') {
        if ($status !== 'waiting') {
            throw new DomainException('Only waiting queue records can be called.');
        }
        $active = $db->prepare(
            'SELECT queue_id FROM queues
             WHERE doctor_id = :doctor AND queue_date = :date
               AND status IN ("called", "in_consultation")
               AND queue_id <> :id FOR UPDATE'
        );
        $active->execute(['doctor' => $queue['doctor_id'], 'date' => queueToday(), 'id' => $queueId]);
        if ($active->fetchColumn() !== false) {
            throw new DomainException('This doctor already has an active queue patient.');
        }
        $next = 'called';
        $updates[] = 'called_at = COALESCE(called_at, CURRENT_TIMESTAMP)';
    } elseif ($action === 'recall') {
        if ($status !== 'called') {
            throw new DomainException('Only a called patient can be recalled.');
        }
        $next = 'called';
        $note = 'Patient recalled';
    } elseif ($action === 'skip') {
        if (!in_array($status, ['called', 'waiting'], true)) {
            throw new DomainException('Only waiting or called patients can be skipped.');
        }
        $next = 'skipped';
    } elseif ($action === 'return') {
        if ($status !== 'skipped') {
            throw new DomainException('Only skipped patients can return to the queue.');
        }
        $next = 'waiting';
    } elseif ($action === 'start_service') {
        if ($status !== 'called') {
            throw new DomainException('Only called patients can enter service.');
        }
        $next = 'in_consultation';
    } elseif ($action === 'complete') {
        if ($status !== 'in_consultation') {
            throw new DomainException('Only patients in service can be completed.');
        }
        $next = 'completed';
        $updates[] = 'completed_at = CURRENT_TIMESTAMP';
    } elseif ($action === 'cancel') {
        if (!in_array($status, ['waiting', 'called', 'skipped'], true)) {
            throw new DomainException('This queue record cannot be cancelled now.');
        }
        $next = 'cancelled';
        $note = validateOptionalString($body['note'] ?? null, 'Cancellation note', 255);
    } else {
        throw new InvalidArgumentException('Unknown queue action.');
    }

    if ($next !== $status) {
        $updates[] = 'status = ' . $db->quote($next);
    }
    if ($updates !== []) {
        $sql = 'UPDATE queues SET ' . implode(', ', $updates) . ' WHERE queue_id = :id';
        $update = $db->prepare($sql);
        $update->execute(['id' => $queueId]);
    }
    recordQueueEvent($db, $queueId, $status, $next, (int) $user['user_account_id'], $note);
    $db->commit();
    sendJsonSuccess(['queue' => queueRecord($db, $queueId)]);
} catch (Throwable $exception) {
    if (isset($db) && $db instanceof PDO && $db->inTransaction()) {
        $db->rollBack();
    }
    queueException($exception);
}
