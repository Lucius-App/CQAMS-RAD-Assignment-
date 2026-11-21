<?php
declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/api/patients/common.php';

startSecureSession();

if (isAuthenticated()) {
    header('Location: ' . getDashboardPath((string) getAuthenticatedUser()['role']));
    exit;
}

$errors = [];
$formData = [
    'first_name' => '',
    'last_name' => '',
    'username' => '',
    'email' => '',
    'phone' => '',
    'national_id' => '',
    'date_of_birth' => '',
    'sex' => 'undisclosed',
    'address' => '',
    'emergency_name' => '',
    'emergency_phone' => '',
];

if (requestMethodIs('POST')) {
    $formData['first_name'] = trim($_POST['first_name'] ?? '');
    $formData['last_name'] = trim($_POST['last_name'] ?? '');
    $formData['username'] = trim($_POST['username'] ?? '');
    $formData['email'] = trim($_POST['email'] ?? '');
    $formData['phone'] = trim($_POST['phone'] ?? '');
    $formData['national_id'] = trim($_POST['national_id'] ?? '');
    $formData['date_of_birth'] = trim($_POST['date_of_birth'] ?? '');
    $formData['sex'] = trim($_POST['sex'] ?? 'undisclosed');
    $formData['address'] = trim($_POST['address'] ?? '');
    $formData['emergency_name'] = trim($_POST['emergency_contact_name'] ?? '');
    $formData['emergency_phone'] = trim($_POST['emergency_contact_phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';

    // Validation
    if ($formData['first_name'] === '' || $formData['last_name'] === '') {
        $errors[] = 'First name and last name are required.';
    }
    if (!preg_match('/^[A-Za-z0-9._-]{3,80}$/', $formData['username'])) {
        $errors[] = 'Username must be 3 to 80 characters and contain only letters, numbers, dots, hyphens, or underscores.';
    }
    if ($formData['email'] !== '' && filter_var($formData['email'], FILTER_VALIDATE_EMAIL) === false) {
        $errors[] = 'Please provide a valid email address.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long.';
    }
    if ($password !== $passwordConfirm) {
        $errors[] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        try {
            $db = getDatabaseConnection();
            $db->beginTransaction();

            // Check duplicate username
            $checkUser = $db->prepare('SELECT 1 FROM user_accounts WHERE username = :username LIMIT 1');
            $checkUser->execute(['username' => $formData['username']]);
            if ($checkUser->fetchColumn() !== false) {
                $errors[] = 'This username is already taken. Please choose another.';
            }

            // Check duplicate national ID if provided
            if ($formData['national_id'] !== '') {
                $checkId = $db->prepare('SELECT 1 FROM patients WHERE national_id = :nid LIMIT 1');
                $checkId->execute(['nid' => $formData['national_id']]);
                if ($checkId->fetchColumn() !== false) {
                    $errors[] = 'A patient with this National ID is already registered.';
                }
            }

            if (empty($errors)) {
                // Insert User Account (pending approval, is_active=0)
                $stmtUser = $db->prepare(
                    'INSERT INTO user_accounts (username, password_hash, role, approval_status, is_active)
                     VALUES (:username, :password_hash, "patient", "pending", 0)'
                );
                $stmtUser->execute([
                    'username' => $formData['username'],
                    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                ]);
                $userAccountId = (int) $db->lastInsertId();

                // Generate Patient Reference Number
                $regNumber = generatePatientReference($db);

                // Insert Patient Profile
                $stmtPatient = $db->prepare(
                    'INSERT INTO patients (
                        user_account_id, registration_number, national_id, first_name, last_name,
                        date_of_birth, sex, phone, email, address,
                        emergency_contact_name, emergency_contact_phone, is_active
                    ) VALUES (
                        :user_account_id, :registration_number, :national_id, :first_name, :last_name,
                        :date_of_birth, :sex, :phone, :email, :address,
                        :emergency_name, :emergency_phone, 1
                    )'
                );
                $stmtPatient->execute([
                    'user_account_id' => $userAccountId,
                    'registration_number' => $regNumber,
                    'national_id' => $formData['national_id'] !== '' ? $formData['national_id'] : null,
                    'first_name' => $formData['first_name'],
                    'last_name' => $formData['last_name'],
                    'date_of_birth' => $formData['date_of_birth'] !== '' ? $formData['date_of_birth'] : null,
                    'sex' => in_array($formData['sex'], ['female', 'male', 'other', 'undisclosed'], true) ? $formData['sex'] : 'undisclosed',
                    'phone' => $formData['phone'] !== '' ? $formData['phone'] : null,
                    'email' => $formData['email'] !== '' ? $formData['email'] : null,
                    'address' => $formData['address'] !== '' ? $formData['address'] : null,
                    'emergency_name' => $formData['emergency_name'] !== '' ? $formData['emergency_name'] : null,
                    'emergency_phone' => $formData['emergency_phone'] !== '' ? $formData['emergency_phone'] : null,
                ]);

                $db->commit();
                header('Location: /CQMS/login.php?registered=1');
                exit;
            } else {
                $db->rollBack();
            }
        } catch (Throwable $e) {
            if (isset($db) && $db->inTransaction()) {
                $db->rollBack();
            }
            error_log('Patient self-registration error: ' . $e->getMessage());
            $errors[] = 'Registration could not be completed. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Registration | CQAMS</title>
    <link rel="stylesheet" href="/CQMS/assets/css/app.css">
</head>
<body class="login-page">
<main class="login-shell" style="grid-template-columns: minmax(18rem, .8fr) minmax(26rem, 1.4fr); max-width: 950px; width: 100%;">
    <section class="login-brand-panel">
        <div class="login-brand-mark" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round">
                <path d="M9 6.5h11M9 12h11M9 17.5h7"/>
                <circle cx="5" cy="6.5" r="1" fill="currentColor" stroke="none"/>
                <circle cx="5" cy="12" r="1" fill="currentColor" stroke="none"/>
                <circle cx="5" cy="17.5" r="1" fill="currentColor" stroke="none"/>
            </svg>
        </div>
        <p class="eyebrow">Patient self-service</p>
        <h1>Join CQAMS</h1>
        <p>Register as a clinic patient to book doctor appointments, track your medical history, and access live consultation queues.</p>
        <div class="login-brand-line"></div>
        <small>After registration, an administrator will review and activate your account.</small>
        <div style="margin-top: 2rem;">
            <a href="/CQMS/" style="color: #ccfbf1; text-decoration: none; font-size: 0.875rem;">← Return to Clinic Homepage</a>
        </div>
    </section>

    <section class="login-card" aria-labelledby="register-heading" style="max-height: 90vh; overflow-y: auto;">
        <p class="eyebrow">New Patient Account</p>
        <h2 id="register-heading">Patient Registration</h2>
        <p class="login-help">Please fill in your personal and contact details.</p>

        <?php if (!empty($errors)): ?>
            <div class="message message-error" role="alert">
                <ul style="margin: 0; padding-left: 1.25rem;">
                    <?php foreach ($errors as $error): ?>
                        <li><?= escapeHtml($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" action="/CQMS/register.php" id="registration-form">
            <h3 style="font-size: 1rem; color: var(--color-primary-dark); margin: 1rem 0 0.5rem; border-bottom: 1px solid var(--color-border); padding-bottom: 0.25rem;">Account Credentials</h3>
            <div class="form-grid">
                <label>Username *
                    <input name="username" maxlength="80" value="<?= escapeHtml($formData['username']) ?>" required pattern="[A-Za-z0-9._-]{3,80}" placeholder="e.g. john_doe">
                </label>
                <div></div>
                <label>Password * (min 8 chars)
                    <input name="password" id="reg-password" type="password" minlength="8" required autocomplete="new-password">
                </label>
                <label>Confirm Password *
                    <input name="password_confirm" id="reg-confirm" type="password" minlength="8" required autocomplete="new-password">
                </label>
            </div>

            <h3 style="font-size: 1rem; color: var(--color-primary-dark); margin: 1.5rem 0 0.5rem; border-bottom: 1px solid var(--color-border); padding-bottom: 0.25rem;">Personal Information</h3>
            <div class="form-grid">
                <label>First Name *
                    <input name="first_name" maxlength="80" value="<?= escapeHtml($formData['first_name']) ?>" required>
                </label>
                <label>Last Name *
                    <input name="last_name" maxlength="80" value="<?= escapeHtml($formData['last_name']) ?>" required>
                </label>
                <label>Date of Birth
                    <input name="date_of_birth" type="date" value="<?= escapeHtml($formData['date_of_birth']) ?>">
                </label>
                <label>Gender
                    <select name="sex">
                        <option value="undisclosed" <?= $formData['sex'] === 'undisclosed' ? 'selected' : '' ?>>Prefer not to say</option>
                        <option value="female" <?= $formData['sex'] === 'female' ? 'selected' : '' ?>>Female</option>
                        <option value="male" <?= $formData['sex'] === 'male' ? 'selected' : '' ?>>Male</option>
                        <option value="other" <?= $formData['sex'] === 'other' ? 'selected' : '' ?>>Other</option>
                    </select>
                </label>
                <label class="form-span-2">National ID / Passport Number
                    <input name="national_id" maxlength="50" value="<?= escapeHtml($formData['national_id']) ?>" placeholder="e.g. 950322-10-5555">
                </label>
            </div>

            <h3 style="font-size: 1rem; color: var(--color-primary-dark); margin: 1.5rem 0 0.5rem; border-bottom: 1px solid var(--color-border); padding-bottom: 0.25rem;">Contact Information</h3>
            <div class="form-grid">
                <label>Phone Number
                    <input name="phone" type="tel" maxlength="30" value="<?= escapeHtml($formData['phone']) ?>" placeholder="e.g. 012-3456789">
                </label>
                <label>Email Address
                    <input name="email" type="email" maxlength="254" value="<?= escapeHtml($formData['email']) ?>" placeholder="e.g. patient@email.com">
                </label>
                <label class="form-span-2">Residential Address
                    <textarea name="address" rows="2" maxlength="255"><?= escapeHtml($formData['address']) ?></textarea>
                </label>
                <label>Emergency Contact Name
                    <input name="emergency_contact_name" maxlength="160" value="<?= escapeHtml($formData['emergency_name']) ?>">
                </label>
                <label>Emergency Contact Phone
                    <input name="emergency_contact_phone" type="tel" maxlength="30" value="<?= escapeHtml($formData['emergency_phone']) ?>">
                </label>
            </div>

            <div style="margin-top: 1.5rem;">
                <button class="button login-submit" type="submit" style="width: 100%;">Submit Registration <span aria-hidden="true">→</span></button>
            </div>
        </form>

        <div style="margin-top: 1.5rem; text-align: center; border-top: 1px solid var(--color-border); padding-top: 1rem;">
            <p style="margin: 0; font-size: 0.9rem; color: var(--color-muted);">
                Already have an approved account? <a href="/CQMS/login.php" style="color: var(--color-primary); font-weight: 600; text-decoration: none;">Sign in here</a>
            </p>
        </div>
    </section>
</main>
<script>
    // Client-side password validation
    document.getElementById('registration-form').addEventListener('submit', function(e) {
        var p1 = document.getElementById('reg-password').value;
        var p2 = document.getElementById('reg-confirm').value;
        if (p1 !== p2) {
            e.preventDefault();
            alert('Passwords do not match. Please re-enter.');
            document.getElementById('reg-confirm').focus();
        }
    });
</script>
</body>
</html>
