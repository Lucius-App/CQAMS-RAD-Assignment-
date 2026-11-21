<?php
declare(strict_types=1);

require_once __DIR__ . '/common.php';

requireRole('administrator');
$appointmentId = getAppointmentIdFromRequest();
try {
    $connection = getDatabaseConnection();
    $appointment = getAppointmentById($connection, $appointmentId);
    if ($appointment === null) {
        sendJsonError('Appointment not found.', 404);
    }

    if (requestMethodIs('GET')) {
        sendJsonSuccess(['appointment' => $appointment]);
    }

    if (requestMethodIs('PUT')) {
        requireAppointmentMutation();
        if ($appointment['status'] === 'cancelled' || $appointment['status'] === 'completed' || $appointment['status'] === 'no_show') {
            sendJsonError('This appointment cannot be rescheduled.', 409);
        }
        $connection->beginTransaction();
        try {
            $bookingRequest = getJsonRequestBody();
            $bookingRequest['clinic_id'] = $appointment['clinic_id'] ?? 1;
            $bookingRequest['reason_category'] = $appointment['reason_category'] ?? 'routine';
            $booking = validateBooking($connection, $bookingRequest, $appointmentId);
            $statement = $connection->prepare(
                'UPDATE appointments
                 SET clinic_id = :clinic_id, patient_id = :patient_id,
                     doctor_id = :doctor_id, reason_category = :reason_category,
                     doctor_schedule_id = :doctor_schedule_id,
                     appointment_date = :appointment_date, start_time = :start_time,
                     end_time = :end_time, reason = :reason, status = "scheduled",
                     cancelled_at = NULL, cancellation_reason = NULL,
                     cancelled_by_user_account_id = NULL
                 WHERE appointment_id = :appointment_id'
            );
            $statement->execute(array_merge($booking, ['appointment_id' => $appointmentId]));
            $updated = getAppointmentById($connection, $appointmentId);
            $connection->commit();
            sendJsonSuccess(['appointment' => $updated]);
        } catch (Throwable $exception) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }
            throw $exception;
        }
    }

    if (requestMethodIs('DELETE')) {
        requireAppointmentMutation();
        if (($_GET['permanent'] ?? '') === '1') {
            $connection->beginTransaction();
            try {
                $lock = $connection->prepare(
                    'SELECT appointment_id
                     FROM appointments
                     WHERE appointment_id = :appointment_id
                     FOR UPDATE'
                );
                $lock->execute(['appointment_id' => $appointmentId]);
                if ($lock->fetchColumn() === false) {
                    $connection->rollBack();
                    sendJsonError('Appointment not found.', 404);
                }

                $queue = $connection->prepare(
                    'SELECT queue_id
                     FROM queues
                     WHERE appointment_id = :appointment_id
                     LIMIT 1'
                );
                $queue->execute(['appointment_id' => $appointmentId]);
                if ($queue->fetchColumn() !== false) {
                    throw new DomainException(
                        'Appointments with queue history cannot be permanently deleted. Cancel the appointment to preserve its service record.'
                    );
                }

                $delete = $connection->prepare(
                    'DELETE FROM appointments WHERE appointment_id = :appointment_id'
                );
                $delete->execute(['appointment_id' => $appointmentId]);
                if ($delete->rowCount() !== 1) {
                    throw new DomainException('Appointment was not deleted. Refresh the list and try again.');
                }

                $connection->commit();
                sendJsonSuccess(['message' => 'Appointment deleted permanently.']);
            } catch (Throwable $exception) {
                if ($connection->inTransaction()) {
                    $connection->rollBack();
                }
                throw $exception;
            }
        }

        if ($appointment['status'] === 'cancelled') {
            sendJsonError('Appointment is already cancelled.', 409);
        }
        $body = getJsonRequestBody();
        $reason = validateOptionalString($body['cancellation_reason'] ?? null, 'Cancellation reason', 255);
        $user = getAuthenticatedUser();
        $statement = $connection->prepare(
            'UPDATE appointments
             SET status = "cancelled", cancelled_at = CURRENT_TIMESTAMP,
                 cancelled_by_user_account_id = :cancelled_by,
                 cancellation_reason = :cancellation_reason
             WHERE appointment_id = :appointment_id AND status <> "cancelled"'
        );
        $statement->execute([
            'cancelled_by' => $user['user_account_id'],
            'cancellation_reason' => $reason,
            'appointment_id' => $appointmentId,
        ]);
        sendJsonSuccess(['appointment' => getAppointmentById($connection, $appointmentId)]);
    }

    sendJsonError('Method not allowed.', 405);
} catch (Throwable $exception) {
    appointmentApiException($exception);
}
