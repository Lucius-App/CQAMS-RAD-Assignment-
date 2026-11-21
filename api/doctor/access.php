<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/permissions.php';

$user = requireRole('doctor');
sendJsonSuccess([
    'message' => 'Doctor API access granted.',
    'user_account_id' => $user['user_account_id'],
]);
