<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/includes/permissions.php';
require_once dirname(__DIR__, 2) . '/api/common/validation.php';

function requirePatientAccess(): array
{
    return requireRole('administrator');
}

function requireMutationRequest(): void
{
    startSecureSession();
    $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    if (!isValidCsrfToken($csrfToken)) {
        sendJsonError('Your session token is invalid or has expired. Refresh the page and try again.', 419);
    }
}

function getPatientIdFromRequest(): int
{
    $value = $_GET['patient_id'] ?? $_POST['patient_id'] ?? null;
    if (!is_string($value) && !is_int($value)) {
        sendJsonError('A valid patient ID is required.', 400);
    }

    $patientId = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($patientId === false) {
        sendJsonError('A valid patient ID is required.', 400);
    }

    return (int) $patientId;
}

function validatePatientData(array $data): array
{
    $firstName = validateRequiredString($data['first_name'] ?? null, 'First name', 80);
    $lastName = validateRequiredString($data['last_name'] ?? null, 'Last name', 80);
    $dateOfBirth = validateOptionalDate($data['date_of_birth'] ?? null);
    $sex = validateEnum(
        $data['sex'] ?? 'undisclosed',
        'Gender',
        ['female', 'male', 'other', 'undisclosed']
    );
    $phone = validateOptionalPhone($data['phone'] ?? null, 'Phone number');
    $email = validateOptionalEmail($data['email'] ?? null);
    $address = validateOptionalString($data['address'] ?? null, 'Address', 255);
    $emergencyContactName = validateOptionalString(
        $data['emergency_contact_name'] ?? null,
        'Emergency contact name',
        160
    );
    $emergencyContactPhone = validateOptionalPhone(
        $data['emergency_contact_phone'] ?? null,
        'Emergency contact phone'
    );
    $nationalId = validateOptionalString($data['national_id'] ?? null, 'National ID', 50);

    return [
        'first_name' => $firstName,
        'last_name' => $lastName,
        'date_of_birth' => $dateOfBirth,
        'sex' => $sex,
        'phone' => $phone,
        'email' => $email,
        'address' => $address,
        'emergency_contact_name' => $emergencyContactName,
        'emergency_contact_phone' => $emergencyContactPhone,
        'national_id' => $nationalId,
    ];
}

function validateOptionalDate(mixed $value): ?string
{
    if ($value === null || $value === '') {
        return null;
    }

    $date = is_string($value)
        ? DateTimeImmutable::createFromFormat('!Y-m-d', $value)
        : false;
    $errors = DateTimeImmutable::getLastErrors();
    $hasErrors = is_array($errors) && ($errors['warning_count'] > 0 || $errors['error_count'] > 0);

    if ($date === false || $hasErrors || $date->format('Y-m-d') !== $value) {
        throw new InvalidArgumentException('Date of birth must use the YYYY-MM-DD format.');
    }

    if ($date > new DateTimeImmutable('today')) {
        throw new InvalidArgumentException('Date of birth cannot be in the future.');
    }

    return $date->format('Y-m-d');
}

function validateOptionalPhone(mixed $value, string $fieldName): ?string
{
    $phone = validateOptionalString($value, $fieldName, 30);
    if ($phone !== null && !preg_match('/^[0-9+().\-\s]{7,30}$/', $phone)) {
        throw new InvalidArgumentException($fieldName . ' contains invalid characters.');
    }

    return $phone;
}

function validateOptionalEmail(mixed $value): ?string
{
    $email = validateOptionalString($value, 'Email', 254);
    if ($email !== null && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        throw new InvalidArgumentException('Email must be valid.');
    }

    return $email;
}

function generatePatientReference(PDO $connection): string
{
    for ($attempt = 0; $attempt < 5; $attempt++) {
        $reference = 'PAT-' . date('Ym') . '-' . strtoupper(bin2hex(random_bytes(3)));
        $statement = $connection->prepare(
            'SELECT 1 FROM patients WHERE registration_number = :registration_number LIMIT 1'
        );
        $statement->execute(['registration_number' => $reference]);
        if ($statement->fetchColumn() === false) {
            return $reference;
        }
    }

    throw new RuntimeException('Unable to generate a unique patient reference.');
}

function findPossibleDuplicate(PDO $connection, array $data, ?int $excludePatientId = null): ?array
{
    $conditions = [];
    $parameters = [];

    if ($data['national_id'] !== null) {
        $conditions[] = 'national_id = :national_id';
        $parameters['national_id'] = $data['national_id'];
    }

    if ($data['phone'] !== null && $data['date_of_birth'] !== null) {
        $conditions[] = '(phone = :phone AND date_of_birth = :date_of_birth AND first_name = :first_name AND last_name = :last_name)';
        $parameters['phone'] = $data['phone'];
        $parameters['date_of_birth'] = $data['date_of_birth'];
        $parameters['first_name'] = $data['first_name'];
        $parameters['last_name'] = $data['last_name'];
    }

    if ($conditions === []) {
        return null;
    }

    $sql = 'SELECT patient_id, registration_number, first_name, last_name
            FROM patients
            WHERE is_active = 1 AND (' . implode(' OR ', $conditions) . ')';
    if ($excludePatientId !== null) {
        $sql .= ' AND patient_id <> :exclude_patient_id';
        $parameters['exclude_patient_id'] = $excludePatientId;
    }
    $sql .= ' LIMIT 1';

    $statement = $connection->prepare($sql);
    $statement->execute($parameters);
    $duplicate = $statement->fetch();

    return is_array($duplicate) ? $duplicate : null;
}

function getPatientById(PDO $connection, int $patientId): ?array
{
    $statement = $connection->prepare(
        'SELECT patient_id, registration_number, national_id, first_name, last_name,
                date_of_birth, sex, phone, email, address, emergency_contact_name,
                emergency_contact_phone, is_active, created_at, updated_at
         FROM patients
         WHERE patient_id = :patient_id AND is_active = 1
         LIMIT 1'
    );
    $statement->execute(['patient_id' => $patientId]);
    $patient = $statement->fetch();

    return is_array($patient) ? $patient : null;
}

function getPatientAppointments(PDO $connection, int $patientId): array
{
    $statement = $connection->prepare(
        'SELECT a.appointment_id, a.appointment_date, a.start_time, a.end_time,
                a.status, a.reason, CONCAT(s.first_name, " ", s.last_name) AS doctor_name
         FROM appointments a
         INNER JOIN doctors d ON d.doctor_id = a.doctor_id
         INNER JOIN staff s ON s.staff_id = d.staff_id
         WHERE a.patient_id = :patient_id
         ORDER BY a.appointment_date DESC, a.start_time DESC
         LIMIT 100'
    );
    $statement->execute(['patient_id' => $patientId]);

    return $statement->fetchAll();
}

function patientApiException(Throwable $exception): never
{
    if ($exception instanceof InvalidArgumentException) {
        sendJsonError($exception->getMessage(), 422);
    }

    if ($exception instanceof PDOException && $exception->getCode() === '23000') {
        sendJsonError('A patient with one of these registration identifiers already exists.', 409);
    }

    error_log('CQAMS patient API error: ' . $exception->getMessage());
    sendJsonError('The patient request could not be completed.', 500);
}
