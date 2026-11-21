<?php
declare(strict_types=1);

require_once __DIR__ . '/common.php';

$user = requireDoctorAccess();
try {
    requireDoctorMutation();
    $db = getDatabaseConnection();
    $doctorId = getAuthenticatedDoctorId($db, (int) $user['user_account_id']);
    $body = getJsonRequestBody();
    $action = $body['action'] ?? '';
    $db->beginTransaction();

    if ($action === 'start') {
        $queueId = filter_var($body['queue_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($queueId === false) {
            throw new InvalidArgumentException('A valid queue record is required.');
        }
        $queueStmt = $db->prepare(
            'SELECT queue_id, doctor_id, status FROM queues
             WHERE queue_id = :queue_id AND doctor_id = :doctor_id
             FOR UPDATE'
        );
        $queueStmt->execute(['queue_id' => $queueId, 'doctor_id' => $doctorId]);
        $queue = $queueStmt->fetch();
        if (!is_array($queue) || $queue['status'] !== 'called') {
            throw new DomainException('Only a called patient assigned to you can start consultation.');
        }
        $active = $db->prepare(
            'SELECT consultation_id FROM consultations
             WHERE doctor_id = :doctor_id AND status = "in_progress"
             LIMIT 1 FOR UPDATE'
        );
        $active->execute(['doctor_id' => $doctorId]);
        if ($active->fetchColumn() !== false) {
            throw new DomainException('You already have an active consultation.');
        }
        $insert = $db->prepare(
            'INSERT INTO consultations (queue_id, doctor_id, status)
             VALUES (:queue_id, :doctor_id, "in_progress")'
        );
        $insert->execute(['queue_id' => $queueId, 'doctor_id' => $doctorId]);
        $consultationId = (int) $db->lastInsertId();
        $update = $db->prepare('UPDATE queues SET status = "in_consultation" WHERE queue_id = :queue_id');
        $update->execute(['queue_id' => $queueId]);
        recordQueueEvent($db, (int) $queueId, 'called', 'in_consultation', (int) $user['user_account_id'], 'Consultation started');
        $db->commit();
        sendJsonSuccess(['consultation_id' => $consultationId], 201);
    }

    if ($action === 'complete') {
        $consultationId = filter_var($body['consultation_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($consultationId === false) {
            throw new InvalidArgumentException('A valid consultation is required.');
        }
        $notes = validateOptionalString($body['notes'] ?? null, 'Consultation notes', 10000);
        $outcome = validateOptionalString($body['outcome'] ?? null, 'Consultation outcome', 255);
        $consultationStmt = $db->prepare(
            'SELECT consultation_id, queue_id, status FROM consultations
             WHERE consultation_id = :consultation_id AND doctor_id = :doctor_id
             FOR UPDATE'
        );
        $consultationStmt->execute(['consultation_id' => $consultationId, 'doctor_id' => $doctorId]);
        $consultation = $consultationStmt->fetch();
        if (!is_array($consultation) || $consultation['status'] !== 'in_progress') {
            throw new DomainException('Only an active consultation assigned to you can be completed.');
        }

        // Handle prescriptions if provided
        $prescriptions = $body['prescriptions'] ?? [];
        if (is_array($prescriptions) && !empty($prescriptions)) {
            $rxInsert = $db->prepare(
                'INSERT INTO prescriptions (consultation_id, medicine_name, dosage, frequency, duration, instructions)
                 VALUES (:consultation_id, :medicine_name, :dosage, :frequency, :duration, :instructions)'
            );
            foreach ($prescriptions as $rx) {
                if (!is_array($rx)) {
                    continue;
                }
                $medName = trim((string) ($rx['medicine_name'] ?? ''));
                if ($medName === '') {
                    continue;
                }
                $dosage = trim((string) ($rx['dosage'] ?? ''));
                $freq = trim((string) ($rx['frequency'] ?? ''));
                $duration = trim((string) ($rx['duration'] ?? ''));
                $instructions = trim((string) ($rx['instructions'] ?? ''));

                if (mb_strlen($medName) > 120) {
                    throw new InvalidArgumentException('Medication name cannot exceed 120 characters.');
                }
                if (mb_strlen($dosage) > 80) {
                    throw new InvalidArgumentException('Dosage cannot exceed 80 characters.');
                }
                if (mb_strlen($freq) > 120) {
                    throw new InvalidArgumentException('Frequency cannot exceed 120 characters.');
                }
                if (mb_strlen($duration) > 80) {
                    throw new InvalidArgumentException('Duration cannot exceed 80 characters.');
                }
                if (mb_strlen($instructions) > 255) {
                    throw new InvalidArgumentException('Instructions cannot exceed 255 characters.');
                }

                $rxInsert->execute([
                    'consultation_id' => $consultationId,
                    'medicine_name' => $medName,
                    'dosage' => $dosage !== '' ? $dosage : 'As directed',
                    'frequency' => $freq !== '' ? $freq : 'Standard',
                    'duration' => $duration !== '' ? $duration : 'Until completed',
                    'instructions' => $instructions !== '' ? $instructions : null,
                ]);
            }
        }

        $update = $db->prepare(
            'UPDATE consultations
             SET status = "completed", ended_at = CURRENT_TIMESTAMP,
                 notes = :notes, outcome = :outcome
             WHERE consultation_id = :consultation_id'
        );
        $update->execute([
            'notes' => $notes,
            'outcome' => $outcome,
            'consultation_id' => $consultationId,
        ]);
        $queueUpdate = $db->prepare(
            'UPDATE queues SET status = "completed", completed_at = CURRENT_TIMESTAMP
             WHERE queue_id = :queue_id AND status = "in_consultation"'
        );
        $queueUpdate->execute(['queue_id' => $consultation['queue_id']]);
        if ($queueUpdate->rowCount() !== 1) {
            throw new DomainException('The queue state does not allow completion.');
        }

        // Synchronize appointment status if linked
        $aptStmt = $db->prepare('SELECT appointment_id FROM queues WHERE queue_id = :queue_id');
        $aptStmt->execute(['queue_id' => $consultation['queue_id']]);
        $aptId = $aptStmt->fetchColumn();
        if ($aptId) {
            $aptUpdate = $db->prepare('UPDATE appointments SET status = "completed" WHERE appointment_id = :apt_id AND status != "cancelled"');
            $aptUpdate->execute(['apt_id' => $aptId]);
        }

        recordQueueEvent($db, (int) $consultation['queue_id'], 'in_consultation', 'completed', (int) $user['user_account_id'], 'Consultation completed');
        $db->commit();
        sendJsonSuccess(['consultation_id' => $consultationId]);
    }

    throw new InvalidArgumentException('Unknown consultation action.');
} catch (Throwable $exception) {
    if (isset($db) && $db instanceof PDO && $db->inTransaction()) {
        $db->rollBack();
    }
    doctorApiException($exception);
}
