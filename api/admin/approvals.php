<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/permissions.php';
require_once __DIR__ . '/../common/response.php';

$currentUser = requireRole('administrator');

function requireAdminCsrf(): void
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
        $allowed = ['pending', 'approved', 'rejected', 'all'];
        if (!in_array($status, $allowed, true)) {
            $status = 'pending';
        }

        $sql = 'SELECT u.user_account_id, u.username, u.approval_status, u.is_active, u.created_at,
                       p.patient_id, p.registration_number, p.national_id, p.first_name, p.last_name,
                       p.date_of_birth, p.sex, p.phone, p.email, p.address
                FROM user_accounts u
                INNER JOIN patients p ON p.user_account_id = u.user_account_id
                WHERE u.role = "patient"';

        if ($status !== 'all') {
            $sql .= ' AND u.approval_status = :status';
            $sql .= ' ORDER BY u.created_at DESC';
            $stmt = $db->prepare($sql);
            $stmt->execute(['status' => $status]);
        } else {
            $sql .= ' ORDER BY u.created_at DESC';
            $stmt = $db->query($sql);
        }

        sendJsonSuccess(['patients' => $stmt->fetchAll()]);
    }

    if ($method === 'POST') {
        requireAdminCsrf();
        $body = json_decode((string) file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($body)) {
            sendJsonError('Invalid request format.', 422);
        }

        $action = $body['action'] ?? '';
        $userAccountId = filter_var($body['user_account_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if ($userAccountId === false) {
            sendJsonError('Valid patient user account ID is required.', 422);
        }

        if ($action === 'approve') {
            $stmt = $db->prepare(
                'UPDATE user_accounts
                 SET approval_status = "approved", is_active = 1
                 WHERE user_account_id = :id AND role = "patient"'
            );
            $stmt->execute(['id' => $userAccountId]);
            sendJsonSuccess(['message' => 'Patient account approved successfully.']);
        }

        if ($action === 'reject') {
            $stmt = $db->prepare(
                'UPDATE user_accounts
                 SET approval_status = "rejected", is_active = 0
                 WHERE user_account_id = :id AND role = "patient"'
            );
            $stmt->execute(['id' => $userAccountId]);
            sendJsonSuccess(['message' => 'Patient account rejected.']);
        }

        sendJsonError('Unknown action.', 422);
    }

    sendJsonError('Method not allowed.', 405);
} catch (Throwable $e) {
    error_log('Admin patient approvals API error: ' . $e->getMessage());
    sendJsonError('Unable to process patient approvals.', 500);
}
