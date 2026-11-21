<?php
declare(strict_types=1);

function sendJsonResponse(array $data, int $statusCode = 200): never
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    exit;
}

function sendJsonSuccess(mixed $data = null, int $statusCode = 200): never
{
    $response = ['success' => true];
    if ($data !== null) {
        $response['data'] = $data;
    }

    sendJsonResponse($response, $statusCode);
}

function sendJsonError(string $message, int $statusCode = 400, array $errors = []): never
{
    $response = [
        'success' => false,
        'message' => $message,
    ];
    if ($errors !== []) {
        $response['errors'] = $errors;
    }

    sendJsonResponse($response, $statusCode);
}

function handleJsonEndpointException(Throwable $exception): never
{
    error_log('CQAMS API error: ' . $exception->getMessage());
    sendJsonError('An unexpected server error occurred.', 500);
}
