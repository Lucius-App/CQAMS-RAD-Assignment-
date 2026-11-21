<?php
declare(strict_types=1);

function validateRequiredString(mixed $value, string $fieldName, int $maxLength = 255): string
{
    if (!is_string($value)) {
        throw new InvalidArgumentException($fieldName . ' is required.');
    }

    $value = trim($value);
    if ($value === '') {
        throw new InvalidArgumentException($fieldName . ' is required.');
    }

    if (mb_strlen($value) > $maxLength) {
        throw new InvalidArgumentException($fieldName . ' is too long.');
    }

    return $value;
}

function validateOptionalString(mixed $value, string $fieldName, int $maxLength = 255): ?string
{
    if ($value === null || $value === '') {
        return null;
    }

    return validateRequiredString($value, $fieldName, $maxLength);
}

function validateEmail(mixed $value): string
{
    $email = validateRequiredString($value, 'Email', 254);
    if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        throw new InvalidArgumentException('Email must be valid.');
    }

    return $email;
}

function validateEnum(mixed $value, string $fieldName, array $allowedValues): string
{
    $validatedValue = validateRequiredString($value, $fieldName);
    if (!in_array($validatedValue, $allowedValues, true)) {
        throw new InvalidArgumentException($fieldName . ' contains an invalid value.');
    }

    return $validatedValue;
}
