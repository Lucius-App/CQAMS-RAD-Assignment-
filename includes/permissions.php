<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once dirname(__DIR__) . '/api/common/response.php';

function userHasRole(string ...$roles): bool
{
    $user = getAuthenticatedUser();

    return $user !== null && in_array($user['role'] ?? '', $roles, true);
}

function requireAuthentication(): array
{
    $user = getAuthenticatedUser();
    if ($user === null) {
        if (isApiRequest()) {
            sendJsonError('Authentication required.', 401);
        }

        http_response_code(401);
        exit('Authentication required.');
    }

    return $user;
}

function requireRole(string ...$roles): array
{
    $user = requireAuthentication();
    if (!in_array($user['role'] ?? '', $roles, true)) {
        if (isApiRequest()) {
            sendJsonError('You do not have permission to access this resource.', 403);
        }

        http_response_code(403);
        exit('You do not have permission to access this page.');
    }

    return $user;
}

function isApiRequest(): bool
{
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    $requestedWith = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';

    return str_contains($accept, 'application/json')
        || strtolower($requestedWith) === 'xmlhttprequest';
}
