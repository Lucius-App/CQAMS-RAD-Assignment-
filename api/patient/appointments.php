<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/permissions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../common/response.php';
require_once __DIR__ . '/../appointments/common.php';

$user = requireRole('patient');

try {
    $db = getDatabaseConnection();
    $patientId = getAuthenticatedPatientId($db, (int) $user['user_account_id']);
    if ($patientId === null) {
        sendJsonError('No patient profile linked to this user account.', 404);
    }

    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    if ($method === 'GET') {
        $stmt = $db->prepare(
            'SELECT a.appointment_id, a.clinic_id, a.appointment_date, a.start_time, a.end_time,
                    a.reason_category, a.reason, a.status, a.approval_status,
                    a.approval_note, a.cancellation_reason, a.cancelled_at,
                    CONCAT(s.first_name, " ", s.last_name) AS doctor_name,
                    d.specialization, COALESCE(c.name, "Central HQ") AS clinic_name,
                    c.address AS clinic_address, c.photo_url AS clinic_photo
             FROM appointments a
             INNER JOIN doctors d ON d.doctor_id = a.doctor_id
             INNER JOIN staff s ON s.staff_id = d.staff_id
             LEFT JOIN clinics c ON c.clinic_id = a.clinic_id
             WHERE a.patient_id = :patient_id
             ORDER BY a.appointment_date DESC, a.start_time DESC'
        );
        $stmt->execute(['patient_id' => $patientId]);
        sendJsonSuccess(['appointments' => $stmt->fetchAll()]);
    }

    if ($method === 'POST') {
        requireAppointmentMutation();
        $body = getJsonRequestBody();
        $body['patient_id'] = $patientId; // Force patient ID to the authenticated patient!

        $db->beginTransaction();
        try {
            $booking = validateBooking($db, $body);
            $stmt = $db->prepare(
                'INSERT INTO appointments (
                    clinic_id, patient_id, doctor_id, doctor_schedule_id, appointment_date,
                    start_time, end_time, reason_category, reason, approval_status,
                    created_by_user_account_id
                 ) VALUES (
                    :clinic_id, :patient_id, :doctor_id, :doctor_schedule_id, :appointment_date,
                    :start_time, :end_time, :reason_category, :reason, "pending",
                    :created_by_user_account_id
                 )'
            );
            $stmt->execute(array_merge($booking, [
                'created_by_user_account_id' => $user['user_account_id'],
            ]));
            $newId = (int) $db->lastInsertId();
            $appointment = getAppointmentById($db, $newId);
            $db->commit();
            sendJsonSuccess(['appointment' => $appointment], 201);
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    if ($method === 'DELETE') {
        requireAppointmentMutation();
        $appointmentId = filter_var($_GET['appointment_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($appointmentId === false) {
            sendJsonError('Valid appointment ID is required.', 422);
        }

        // Verify patient owns this appointment
        $check = $db->prepare('SELECT status FROM appointments WHERE appointment_id = :id AND patient_id = :pid');
        $check->execute(['id' => $appointmentId, 'pid' => $patientId]);
        $status = $check->fetchColumn();
        if ($status === false) {
            sendJsonError('Appointment not found.', 404);
        }
        if ($status === 'cancelled') {
            sendJsonError('This appointment is already cancelled.', 409);
        }
        if ($status === 'completed') {
            sendJsonError('Completed appointments cannot be cancelled.', 409);
        }

        $body = getJsonRequestBody();
        $reason = validateOptionalString($body['cancellation_reason'] ?? null, 'Cancellation reason', 255);

        $stmt = $db->prepare(
            'UPDATE appointments
             SET status = "cancelled", cancelled_at = CURRENT_TIMESTAMP,
                 cancelled_by_user_account_id = :user_id,
                 cancellation_reason = :reason
             WHERE appointment_id = :id'
        );
        $stmt->execute([
            'user_id' => $user['user_account_id'],
            'reason' => $reason ?: 'Cancelled by patient',
            'id' => $appointmentId,
        ]);

        sendJsonSuccess(['message' => 'Appointment cancelled successfully.']);
    }

    sendJsonError('Method not allowed.', 405);
} catch (Throwable $exception) {
    appointmentApiException($exception);
}
