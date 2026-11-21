<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/includes/permissions.php';
require_once dirname(__DIR__, 2) . '/api/common/validation.php';
require_once dirname(__DIR__) . '/queue/common.php';

function requireDoctorAccess(): array
{
    return requireRole('doctor');
}

function getAuthenticatedDoctorId(PDO $db, int $userAccountId): int
{
    $stmt = $db->prepare(
        'SELECT d.doctor_id
         FROM doctors d
         INNER JOIN staff s ON s.staff_id = d.staff_id
         WHERE s.user_account_id = :user_account_id
         LIMIT 1'
    );
    $stmt->execute(['user_account_id' => $userAccountId]);
    $doctorId = $stmt->fetchColumn();
    if ($doctorId === false) {
        sendJsonError('Your account is not linked to a doctor profile.', 403);
    }

    return (int) $doctorId;
}

function requireDoctorMutation(): void
{
    startSecureSession();
    if (!isValidCsrfToken($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
        sendJsonError('Your session token is invalid or has expired. Refresh the page and try again.', 419);
    }
}

function doctorApiException(Throwable $exception): never
{
    if ($exception instanceof InvalidArgumentException || $exception instanceof DomainException) {
        sendJsonError($exception->getMessage(), $exception instanceof DomainException ? 409 : 422);
    }
    if ($exception instanceof PDOException && $exception->getCode() === '23000') {
        sendJsonError('This queue patient already has an active consultation.', 409);
    }

    error_log('CQAMS doctor API error: ' . $exception->getMessage());
    sendJsonError('The doctor request could not be completed.', 500);
}
