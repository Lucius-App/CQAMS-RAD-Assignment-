<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';

$pageTitle = 'PDO Connection Test';
$connectionStatus = 'Database connection failed.';
$isSuccessful = false;

try {
    $connection = getDatabaseConnection();
    $statement = $connection->query('SELECT 1');
    $isSuccessful = (int) $statement->fetchColumn() === 1;
    $connectionStatus = $isSuccessful
        ? 'PDO connection and database query succeeded.'
        : 'PDO connected, but the verification query returned an unexpected result.';
} catch (Throwable $exception) {
    error_log('CQAMS PDO test failed: ' . $exception->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?> | CQAMS</title>
    <link rel="stylesheet" href="/CQMS/assets/css/app.css">
</head>
<body>
<main>
    <h1><?= htmlspecialchars($pageTitle, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></h1>
    <p><?= htmlspecialchars($connectionStatus, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
</main>
</body>
</html>
