<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/permissions.php';
require_once __DIR__ . '/../common/response.php';
require_once __DIR__ . '/../common/validation.php';

$admin = requireRole('administrator');

function requireAppointmentApprovalCsrf(): void
{
    startSecureSession();
    if (!isValidCsrfToken($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
        sendJsonError('Your session token is invalid or has expired. Refresh and try again.', 419);
    }
}

try {
    $db = getDatabaseConnection();
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    if ($method === 'GET') {
        $status = $_GET['status'] ?? 'pending';
        if (!in_array($status, ['pending', 'approved', 'rejected', 'all'], true)) {
            sendJsonError('Invalid approval status filter.', 422);
        }

        $sql = 'SELECT a.appointment_id, a.clinic_id, a.appointment_date, a.start_time,
                       a.end_time, a.reason_category, a.reason, a.status, a.approval_status,
                       a.approval_note, a.created_at, p.registration_number,
                       CONCAT(p.first_name, " ", p.last_name) AS patient_name,
                       CONCAT(s.first_name, " ", s.last_name) AS doctor_name,
                       d.specialization, c.name AS clinic_name
                FROM appointments a
                INNER JOIN patients p ON p.patient_id = a.patient_id
                INNER JOIN doctors d ON d.doctor_id = a.doctor_id
                INNER JOIN staff s ON s.staff_id = d.staff_id
                LEFT JOIN clinics c ON c.clinic_id = a.clinic_id';

        if ($status !== 'all') {
            $sql .= ' WHERE a.approval_status = :approval_status';
            if ($status === 'pending') {
                $sql .= ' AND a.status = "scheduled"';
            }
        }
        $sql .= ' ORDER BY (a.approval_status = "pending") DESC,
                           a.appointment_date ASC, a.start_time ASC
                  LIMIT 300';

        $statement = $db->prepare($sql);
        $statement->execute($status === 'all' ? [] : ['approval_status' => $status]);
        sendJsonSuccess(['appointments' => $statement->fetchAll()]);
    }

    if ($method !== 'POST') {
        sendJsonError('Method not allowed.', 405);
    }

    requireAppointmentApprovalCsrf();
    $body = getJsonRequestBody();
    $appointmentId = filter_var(
        $body['appointment_id'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );
    if ($appointmentId === false) {
        sendJsonError('A valid appointment ID is required.', 422);
    }

    $action = $body['action'] ?? '';
    if (!in_array($action, ['approve', 'reject'], true)) {
        sendJsonError('Unknown approval action.', 422);
    }

    $note = validateOptionalString($body['approval_note'] ?? null, 'Decision note', 255);
    $approvalStatus = $action === 'approve' ? 'approved' : 'rejected';
    $statement = $db->prepare(
        'UPDATE appointments
         SET approval_status = :approval_status,
             approved_by_user_account_id = :admin_id,
             approved_at = CURRENT_TIMESTAMP,
             approval_note = :approval_note
         WHERE appointment_id = :appointment_id
           AND approval_status = "pending"
           AND status = "scheduled"'
    );
    $statement->execute([
        'approval_status' => $approvalStatus,
        'admin_id' => $admin['user_account_id'],
        'approval_note' => $note,
        'appointment_id' => $appointmentId,
    ]);

    if ($statement->rowCount() !== 1) {
        sendJsonError('This appointment request is no longer pending or cannot be reviewed.', 409);
    }

    sendJsonSuccess(['approval_status' => $approvalStatus]);
} catch (Throwable $exception) {
    error_log('CQAMS appointment approval error: ' . $exception->getMessage());
    sendJsonError('The appointment approval request could not be completed.', 500);
}
