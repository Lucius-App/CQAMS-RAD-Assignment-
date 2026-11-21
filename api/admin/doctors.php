<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/permissions.php';
require_once __DIR__ . '/../common/response.php';
require_once __DIR__ . '/../common/validation.php';

requireRole('administrator');

function requireDoctorAdminCsrf(): void
{
    startSecureSession();
    if (!isValidCsrfToken($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
        sendJsonError('Your session token is invalid or has expired. Refresh the page and try again.', 419);
    }
}

function validateTime(mixed $value, string $field): string
{
    if (!is_string($value) || !preg_match('/^\d{2}:\d{2}$/', $value)) {
        throw new InvalidArgumentException($field . ' must use HH:MM format.');
    }
    $time = DateTimeImmutable::createFromFormat('!H:i', $value);
    if ($time === false || $time->format('H:i') !== $value) {
        throw new InvalidArgumentException($field . ' is invalid.');
    }
    return $value . ':00';
}

function doctorApiError(Throwable $exception): never
{
    if ($exception instanceof InvalidArgumentException || $exception instanceof DomainException) {
        sendJsonError($exception->getMessage(), $exception instanceof DomainException ? 409 : 422);
    }
    if ($exception instanceof PDOException && $exception->getCode() === '23000') {
        sendJsonError('The doctor profile, license number, or schedule already exists.', 409);
    }
    error_log('CQAMS doctor administration error: ' . $exception->getMessage());
    sendJsonError('The doctor administration request could not be completed.', 500);
}

function protectFutureAppointments(PDO $db, int $scheduleId): void
{
    $statement = $db->prepare(
        'SELECT COUNT(*)
         FROM appointments
         WHERE doctor_schedule_id = :schedule_id
           AND appointment_date >= :today
           AND status <> "cancelled"'
    );
    $statement->execute([
        'schedule_id' => $scheduleId,
        'today' => (new DateTimeImmutable('today'))->format('Y-m-d'),
    ]);
    if ((int) $statement->fetchColumn() > 0) {
        throw new DomainException(
            'This schedule has future appointments. Cancel or reschedule them before changing the schedule.'
        );
    }
}

try {
    $db = getDatabaseConnection();
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $doctors = $db->query(
            'SELECT d.doctor_id, d.staff_id, d.specialization, d.license_number,
                    s.user_account_id, s.employee_number, s.first_name, s.last_name,
                    s.email, u.is_active
             FROM doctors d
             INNER JOIN staff s ON s.staff_id = d.staff_id
             INNER JOIN user_accounts u ON u.user_account_id = s.user_account_id
             ORDER BY s.last_name, s.first_name'
        )->fetchAll();
        $schedules = $db->query(
            'SELECT doctor_schedule_id, doctor_id, day_of_week, start_time, end_time,
                    slot_duration_minutes, is_active
             FROM doctor_schedules ORDER BY doctor_id, day_of_week, start_time'
        )->fetchAll();
        $staff = $db->query(
            'SELECT s.staff_id, s.user_account_id, s.first_name, s.last_name
             FROM staff s INNER JOIN user_accounts u ON u.user_account_id = s.user_account_id
             LEFT JOIN doctors d ON d.staff_id = s.staff_id
             WHERE u.role = "doctor" AND u.is_active = 1 AND d.doctor_id IS NULL
             ORDER BY s.last_name, s.first_name'
        )->fetchAll();
        sendJsonSuccess(['doctors' => $doctors, 'schedules' => $schedules, 'available_staff' => $staff]);
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendJsonError('Method not allowed.', 405);
    }
    requireDoctorAdminCsrf();
    try {
        $body = json_decode((string) file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException) {
        sendJsonError('Request body must contain valid JSON.', 422);
    }
    if (!is_array($body)) {
        sendJsonError('Request body must contain a JSON object.', 422);
    }
    $action = $body['action'] ?? '';

    if ($action === 'create_profile' || $action === 'update_profile') {
        $staffId = filter_var($body['staff_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($staffId === false) {
            throw new InvalidArgumentException('A valid doctor staff account is required.');
        }
        $specialization = validateRequiredString($body['specialization'] ?? null, 'Specialization', 120);
        $license = validateOptionalString($body['license_number'] ?? null, 'License number', 80);
        $db->beginTransaction();
        if ($action === 'create_profile') {
            $check = $db->prepare(
                'SELECT s.staff_id FROM staff s INNER JOIN user_accounts u ON u.user_account_id = s.user_account_id
                 LEFT JOIN doctors d ON d.staff_id = s.staff_id
                 WHERE s.staff_id = :staff_id AND u.role = "doctor" AND u.is_active = 1 AND d.doctor_id IS NULL'
            );
            $check->execute(['staff_id' => $staffId]);
            if ($check->fetchColumn() === false) {
                throw new DomainException('The selected staff account is not an available doctor account.');
            }
            $stmt = $db->prepare('INSERT INTO doctors (staff_id, specialization, license_number) VALUES (:staff_id, :specialization, :license_number)');
            $stmt->execute(['staff_id' => $staffId, 'specialization' => $specialization, 'license_number' => $license]);
        } else {
            $doctorId = filter_var($body['doctor_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($doctorId === false) {
                throw new InvalidArgumentException('A valid doctor profile is required.');
            }
            $stmt = $db->prepare('UPDATE doctors SET specialization = :specialization, license_number = :license_number WHERE doctor_id = :doctor_id');
            $stmt->execute(['specialization' => $specialization, 'license_number' => $license, 'doctor_id' => $doctorId]);
            if ($stmt->rowCount() !== 1) {
                throw new DomainException('Doctor profile not found or unchanged.');
            }
        }
        $db->commit();
        sendJsonSuccess();
    }

    if (str_starts_with((string) $action, 'schedule_')) {
        $doctorId = filter_var($body['doctor_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($doctorId === false) {
            throw new InvalidArgumentException('A valid doctor is required.');
        }
        if ($action === 'schedule_toggle') {
            $scheduleId = filter_var($body['doctor_schedule_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($scheduleId === false) {
                throw new InvalidArgumentException('A valid schedule is required.');
            }
            $current = $db->prepare(
                'SELECT is_active FROM doctor_schedules
                 WHERE doctor_schedule_id = :schedule_id AND doctor_id = :doctor_id'
            );
            $current->execute(['schedule_id' => $scheduleId, 'doctor_id' => $doctorId]);
            $isActive = $current->fetchColumn();
            if ($isActive === false) {
                sendJsonError('Schedule not found.', 404);
            }
            if ((int) $isActive === 1) {
                protectFutureAppointments($db, $scheduleId);
            }
            $stmt = $db->prepare('UPDATE doctor_schedules SET is_active = CASE WHEN is_active = 1 THEN 0 ELSE 1 END WHERE doctor_schedule_id = :schedule_id AND doctor_id = :doctor_id');
            $stmt->execute(['schedule_id' => $scheduleId, 'doctor_id' => $doctorId]);
            sendJsonSuccess();
        }
        $day = filter_var($body['day_of_week'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 7]]);
        $duration = filter_var($body['slot_duration_minutes'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 5, 'max_range' => 480]]);
        if ($day === false || $duration === false) {
            throw new InvalidArgumentException('Day and slot duration are invalid.');
        }
        $start = validateTime($body['start_time'] ?? null, 'Start time');
        $end = validateTime($body['end_time'] ?? null, 'End time');
        if ($end <= $start) {
            throw new InvalidArgumentException('End time must be after start time.');
        }
        $scheduleId = filter_var($body['doctor_schedule_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $overlap = 'SELECT doctor_schedule_id FROM doctor_schedules
                    WHERE doctor_id = :doctor_id AND day_of_week = :day AND is_active = 1
                      AND start_time < :end_time AND end_time > :start_time';
        $parameters = ['doctor_id' => $doctorId, 'day' => $day, 'end_time' => $end, 'start_time' => $start];
        if ($scheduleId !== false) {
            $overlap .= ' AND doctor_schedule_id <> :schedule_id';
            $parameters['schedule_id'] = $scheduleId;
        }
        $check = $db->prepare($overlap . ' LIMIT 1');
        $check->execute($parameters);
        if ($check->fetchColumn() !== false) {
            throw new DomainException('The schedule overlaps another active schedule.');
        }
        if ($action === 'schedule_create') {
            $stmt = $db->prepare('INSERT INTO doctor_schedules (doctor_id, day_of_week, start_time, end_time, slot_duration_minutes) VALUES (:doctor_id, :day, :start_time, :end_time, :duration)');
        } elseif ($action === 'schedule_update' && $scheduleId !== false) {
            protectFutureAppointments($db, $scheduleId);
            $stmt = $db->prepare('UPDATE doctor_schedules SET day_of_week = :day, start_time = :start_time, end_time = :end_time, slot_duration_minutes = :duration WHERE doctor_schedule_id = :schedule_id AND doctor_id = :doctor_id');
            $parameters['schedule_id'] = $scheduleId;
        } else {
            throw new InvalidArgumentException('A valid schedule action is required.');
        }
        $stmt->execute(array_merge($parameters, ['duration' => $duration]));
        sendJsonSuccess();
    }
    throw new InvalidArgumentException('Unknown doctor administration action.');
} catch (Throwable $exception) {
    if (isset($db) && $db instanceof PDO && $db->inTransaction()) {
        $db->rollBack();
    }
    doctorApiError($exception);
}
