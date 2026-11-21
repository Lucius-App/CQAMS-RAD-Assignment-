<?php
declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

startSecureSession();

if (isAuthenticated()) {
    header('Location: ' . getDashboardPath((string) getAuthenticatedUser()['role']));
    exit;
}

$errorMessage = null;
$successMessage = null;
if (isset($_GET['registered'])) {
    $successMessage = 'Registration submitted! Your account is pending administrator approval. You can log in once approved.';
}
$username = '';

if (requestMethodIs('POST')) {
    $username = getPostString('username');
    $password = is_string($_POST['password'] ?? '') ? $_POST['password'] : '';

    if ($username === '' || $password === '') {
        $errorMessage = 'Username and password are required.';
    } else {
        try {
            $result = authenticateUser(getDatabaseConnection(), $username, $password);
            if (!$result['success']) {
                $errorMessage = $result['error'] ?? 'Invalid username or password.';
            } else {
                $user = $result['user'];
                logInUser($user);
                $connection = getDatabaseConnection();
                $statement = $connection->prepare(
                    'UPDATE user_accounts SET last_login_at = CURRENT_TIMESTAMP
                     WHERE user_account_id = :user_account_id'
                );
                $statement->execute(['user_account_id' => $user['user_account_id']]);
                header('Location: ' . getDashboardPath((string) $user['role']));
                exit;
            }
        } catch (Throwable $exception) {
            error_log('CQAMS login failed: ' . $exception->getMessage());
            $errorMessage = 'Login is temporarily unavailable.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In | CQAMS</title>
    <link rel="stylesheet" href="/CQMS/assets/css/app.css">
</head>
<body class="login-page">
<main class="login-shell">
    <section class="login-brand-panel">
        <div class="login-brand-mark" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round">
                <path d="M9 6.5h11M9 12h11M9 17.5h7"/>
                <circle cx="5" cy="6.5" r="1" fill="currentColor" stroke="none"/>
                <circle cx="5" cy="12" r="1" fill="currentColor" stroke="none"/>
                <circle cx="5" cy="17.5" r="1" fill="currentColor" stroke="none"/>
            </svg>
        </div>
        <p class="eyebrow">Clinic management</p>
        <h1>CQAMS</h1>
        <p>Clinic Queue and Appointment Management System</p>
        <div class="login-brand-line"></div>
        <small>Portal for patients, doctors, and clinic administrators.</small>
        <div style="margin-top: 1.5rem;">
            <a href="/CQMS/" style="color: #ccfbf1; text-decoration: none; font-size: 0.875rem;">← Back to Homepage</a>
        </div>
    </section>
    <section class="login-card" aria-labelledby="login-heading">
        <p class="eyebrow">User access</p>
        <h2 id="login-heading">Welcome back</h2>
        <p class="login-help">Sign in with your patient or staff credentials.</p>
        <?php if ($successMessage !== null): ?>
            <p class="message message-success" role="status"><?= escapeHtml($successMessage) ?></p>
        <?php endif; ?>
        <?php if ($errorMessage !== null): ?>
            <p class="message message-error" role="alert"><?= escapeHtml($errorMessage) ?></p>
        <?php endif; ?>
        <form class="login-form" method="post" action="/CQMS/login.php">
            <label for="username">Username</label>
            <input id="username" name="username" type="text" maxlength="80" autocomplete="username" value="<?= escapeHtml($username) ?>" required autofocus>

            <label for="password">Password</label>
            <input id="password" name="password" type="password" autocomplete="current-password" required>

            <button class="button login-submit" type="submit">Sign in <span aria-hidden="true">→</span></button>
        </form>

        <div style="margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid var(--color-border); text-align: center;">
            <p style="margin: 0; font-size: 0.9rem; color: var(--color-muted);">
                New patient? <a href="/CQMS/register.php" style="color: var(--color-primary); font-weight: 600; text-decoration: none;">Register for an account</a>
            </p>
        </div>
        <p class="login-security" style="margin-top: 1rem;"><span aria-hidden="true">●</span> Secure SSL/TLS encrypted access</p>
    </section>
</main>
</body>
</html>
