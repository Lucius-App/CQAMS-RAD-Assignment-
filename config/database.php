<?php
declare(strict_types=1);

function getDatabaseConnection(): PDO
{
    static $connection = null;

    if ($connection instanceof PDO) {
        return $connection;
    }

    $host = getenv('CQAMS_DB_HOST') ?: '127.0.0.1';
    $port = getenv('CQAMS_DB_PORT') ?: '3306';
    $database = getenv('CQAMS_DB_NAME') ?: 'cqams';
    $username = getenv('CQAMS_DB_USER') ?: 'root';
    $password = getenv('CQAMS_DB_PASSWORD') ?: '';

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        $host,
        $port,
        $database
    );

    try {
        $connection = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $exception) {
        error_log('CQAMS database connection failed: ' . $exception->getMessage());
        throw new RuntimeException('The database is currently unavailable.', 0, $exception);
    }

    return $connection;
}
