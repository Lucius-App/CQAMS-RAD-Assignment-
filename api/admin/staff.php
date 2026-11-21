<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/permissions.php';
require_once __DIR__ . '/../common/response.php';
require_once __DIR__ . '/../common/validation.php';

$currentUser = requireRole('administrator');

function requireAdminCsrf(): void
{
    startSecureSession();
    if (!isValidCsrfToken($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
        sendJsonError('Your session token is invalid or has expired. Refresh the page and try again.', 419);
    }
}

function validateStaffPayload(array $body, bool $passwordRequired): array
{
    $username = validateRequiredString($body['username'] ?? null, 'Username', 80);
    if (!preg_match('/^[A-Za-z0-9._-]{3,80}$/', $username)) {
        throw new InvalidArgumentException('Username may contain only letters, numbers, dots, underscores, and hyphens.');
    }

    $role = validateEnum($body['role'] ?? null, 'Role', ['administrator', 'doctor']);
    $firstName = validateRequiredString($body['first_name'] ?? null, 'First name', 80);
    $lastName = validateRequiredString($body['last_name'] ?? null, 'Last name', 80);
    $employeeNumber = validateOptionalString($body['employee_number'] ?? null, 'Employee number', 50);
    $phone = validateOptionalString($body['phone'] ?? null, 'Phone number', 30);
    $email = validateOptionalString($body['email'] ?? null, 'Email', 254);
    if ($email !== null && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        throw new InvalidArgumentException('Email must be valid.');
    }

    $password = null;
    if ($passwordRequired || ($body['password'] ?? '') !== '') {
        if (!is_string($body['password'] ?? null) || strlen($body['password']) < 12) {
            throw new InvalidArgumentException('Password must be at least 12 characters.');
        }
        $password = $body['password'];
    }

    return compact('username', 'role', 'firstName', 'lastName', 'employeeNumber', 'phone', 'email', 'password');
}

function staffApiError(Throwable $exception): never
{
    if ($exception instanceof InvalidArgumentException) {
        sendJsonError($exception->getMessage(), 422);
    }
    if ($exception instanceof PDOException && $exception->getCode() === '23000') {
        sendJsonError('The username or employee number is already in use.', 409);
    }
    error_log('CQAMS staff API error: ' . $exception->getMessage());
    sendJsonError('The staff account request could not be completed.', 500);
}

try {
    $db = getDatabaseConnection();
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    if ($method === 'GET') {
        $statement = $db->query(
            'SELECT u.user_account_id, u.username, u.role, u.is_active, u.last_login_at,
                    s.staff_id, s.employee_number, s.first_name, s.last_name, s.phone, s.email,
                    s.created_at, s.updated_at
             FROM user_accounts u
             INNER JOIN staff s ON s.user_account_id = u.user_account_id
             ORDER BY s.last_name, s.first_name, u.username'
        );
        sendJsonSuccess(['staff' => $statement->fetchAll()]);
    }

    if ($method !== 'POST') {
        sendJsonError('Method not allowed.', 405);
    }

    requireAdminCsrf();
    try {
        $body = json_decode((string) file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException) {
        sendJsonError('Request body must contain valid JSON.', 422);
    }
    if (!is_array($body)) {
        sendJsonError('Request body must contain a JSON object.', 422);
    }
    $action = $body['action'] ?? '';

    if ($action === 'create') {
        $data = validateStaffPayload($body, true);
        $db->beginTransaction();
        $account = $db->prepare(
            'INSERT INTO user_accounts (username, password_hash, role)
             VALUES (:username, :password_hash, :role)'
        );
        $account->execute([
            'username' => $data['username'],
            'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
            'role' => $data['role'],
        ]);
        $userAccountId = (int) $db->lastInsertId();
        $staff = $db->prepare(
            'INSERT INTO staff (user_account_id, employee_number, first_name, last_name, phone, email)
             VALUES (:user_account_id, :employee_number, :first_name, :last_name, :phone, :email)'
        );
        $staff->execute([
            'user_account_id' => $userAccountId,
            'employee_number' => $data['employeeNumber'],
            'first_name' => $data['firstName'],
            'last_name' => $data['lastName'],
            'phone' => $data['phone'],
            'email' => $data['email'],
        ]);
        $db->commit();
        sendJsonSuccess(['user_account_id' => $userAccountId], 201);
    }

    $userAccountId = filter_var($body['user_account_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($userAccountId === false) {
        sendJsonError('A valid staff account is required.', 422);
    }
    if ((int) $userAccountId === (int) $currentUser['user_account_id'] && $action === 'toggle') {
        sendJsonError('You cannot disable your own administrator account.', 409);
    }

    if ($action === 'update') {
        $data = validateStaffPayload($body, false);
        $db->beginTransaction();
        $account = $db->prepare('UPDATE user_accounts SET username = :username, role = :role WHERE user_account_id = :user_account_id');
        $account->execute(['username' => $data['username'], 'role' => $data['role'], 'user_account_id' => $userAccountId]);
        $staff = $db->prepare(
            'UPDATE staff SET employee_number = :employee_number, first_name = :first_name,
                    last_name = :last_name, phone = :phone, email = :email
             WHERE user_account_id = :user_account_id'
        );
        $staff->execute([
            'employee_number' => $data['employeeNumber'],
            'first_name' => $data['firstName'],
            'last_name' => $data['lastName'],
            'phone' => $data['phone'],
            'email' => $data['email'],
            'user_account_id' => $userAccountId,
        ]);
        if ($data['password'] !== null) {
            $password = $db->prepare('UPDATE user_accounts SET password_hash = :password_hash WHERE user_account_id = :user_account_id');
            $password->execute([
                'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
                'user_account_id' => $userAccountId,
            ]);
        }
        $db->commit();
        sendJsonSuccess();
    }

    if ($action === 'toggle') {
        $statement = $db->prepare(
            'UPDATE user_accounts SET is_active = CASE WHEN is_active = 1 THEN 0 ELSE 1 END
             WHERE user_account_id = :user_account_id'
        );
        $statement->execute(['user_account_id' => $userAccountId]);
        if ($statement->rowCount() !== 1) {
            sendJsonError('Staff account not found.', 404);
        }
        sendJsonSuccess();
    }

    throw new InvalidArgumentException('Unknown staff action.');
} catch (Throwable $exception) {
    if (isset($db) && $db instanceof PDO && $db->inTransaction()) {
        $db->rollBack();
    }
    staffApiError($exception);
}
