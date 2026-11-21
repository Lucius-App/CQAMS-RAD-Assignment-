<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';
require_once dirname(__DIR__) . '/config/database.php';

function startSecureSession(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}

function getAuthenticatedUser(): ?array
{
    static $validated = false;
    static $validatedUser = null;

    startSecureSession();
    $user = $_SESSION['user'] ?? null;

    if (!is_array($user)) {
        return null;
    }

    if ($validated) {
        return $validatedUser;
    }

    $userAccountId = filter_var(
        $user['user_account_id'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );
    if ($userAccountId === false) {
        logOutUser();
        return null;
    }

    $statement = getDatabaseConnection()->prepare(
        'SELECT user_account_id, username, role, approval_status
         FROM user_accounts
         WHERE user_account_id = :user_account_id AND is_active = 1
         LIMIT 1'
    );
    $statement->execute(['user_account_id' => $userAccountId]);
    $currentUser = $statement->fetch();

    if (
        !is_array($currentUser)
        || !in_array($currentUser['role'] ?? '', ['administrator', 'doctor', 'patient'], true)
        || (string) $currentUser['username'] !== (string) ($user['username'] ?? '')
        || (string) $currentUser['role'] !== (string) ($user['role'] ?? '')
        || (string) ($currentUser['approval_status'] ?? 'approved') !== 'approved'
    ) {
        logOutUser();
        return null;
    }

    $validatedUser = [
        'user_account_id' => (int) $currentUser['user_account_id'],
        'username' => (string) $currentUser['username'],
        'role' => (string) $currentUser['role'],
    ];
    $validated = true;

    return $validatedUser;
}

function isAuthenticated(): bool
{
    return getAuthenticatedUser() !== null;
}

function authenticateUser(PDO $connection, string $username, string $password): array
{
    $statement = $connection->prepare(
        'SELECT user_account_id, username, password_hash, role, approval_status, is_active
         FROM user_accounts
         WHERE username = :username
         LIMIT 1'
    );
    $statement->execute(['username' => $username]);
    $user = $statement->fetch();

    if (!is_array($user) || !password_verify($password, $user['password_hash'])) {
        return ['success' => false, 'error' => 'Invalid username or password.'];
    }

    if (!in_array($user['role'], ['administrator', 'doctor', 'patient'], true)) {
        return ['success' => false, 'error' => 'This account role is no longer supported. Contact the administrator.'];
    }

    if ((string) ($user['approval_status'] ?? 'approved') === 'pending') {
        return ['success' => false, 'error' => 'Your patient account is pending administrator approval. Please check back later.'];
    }

    if ((string) ($user['approval_status'] ?? 'approved') === 'rejected' || (int) $user['is_active'] !== 1) {
        return ['success' => false, 'error' => 'Your account is deactivated or has been rejected. Please contact the clinic.'];
    }

    unset($user['password_hash']);
    return ['success' => true, 'user' => $user];
}

function logInUser(array $user): void
{
    startSecureSession();
    session_regenerate_id(true);
    $_SESSION['user'] = [
        'user_account_id' => (int) $user['user_account_id'],
        'username' => (string) $user['username'],
        'role' => (string) $user['role'],
    ];
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function getDashboardPath(string $role): string
{
    return match ($role) {
        'administrator' => '/CQMS/admin/',
        'doctor' => '/CQMS/doctor/',
        'patient' => '/CQMS/patient/',
        default => '/CQMS/login.php',
    };
}

function getAuthenticatedPatientId(PDO $connection, int $userAccountId): ?int
{
    $statement = $connection->prepare(
        'SELECT patient_id FROM patients WHERE user_account_id = :user_account_id LIMIT 1'
    );
    $statement->execute(['user_account_id' => $userAccountId]);
    $id = $statement->fetchColumn();

    return $id !== false ? (int) $id : null;
}

function logOutUser(): void
{
    startSecureSession();
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', (bool) $params['secure'], (bool) $params['httponly']);
    }

    session_destroy();
}
