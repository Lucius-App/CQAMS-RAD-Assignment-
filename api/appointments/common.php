<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/includes/permissions.php';
require_once dirname(__DIR__, 2) . '/api/common/validation.php';

function requireAppointmentAccess(): array
{
    return requireRole('administrator', 'patient');
}

function requireAppointmentMutation(): void
{
    startSecureSession();
    $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    if (!isValidCsrfToken($csrfToken)) {
        sendJsonError('Your session token is invalid or has expired. Refresh the page and try again.', 419);
    }
}

function getAppointmentIdFromRequest(): int
{
    $value = $_GET['appointment_id'] ?? null;
    $appointmentId = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($appointmentId === false) {
        sendJsonError('A valid appointment ID is required.', 400);
    }

    return (int) $appointmentId;
}

function validateAppointmentDate(mixed $value): string
{
    if (!is_string($value) || $value === '') {
        throw new InvalidArgumentException('Appointment date is required.');
    }

    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    $errors = DateTimeImmutable::getLastErrors();
    $hasErrors = is_array($errors) && ($errors['warning_count'] > 0 || $errors['error_count'] > 0);
    if ($date === false || $hasErrors || $date->format('Y-m-d') !== $value) {
        throw new InvalidArgumentException('Appointment date must use the YYYY-MM-DD format.');
    }

    if ($date < new DateTimeImmutable('today')) {
        throw new InvalidArgumentException('Appointments cannot be booked in the past.');
    }

    return $value;
}

function validateAppointmentTime(mixed $value): string
{
    if (!is_string($value) || !preg_match('/^\d{2}:\d{2}$/', $value)) {
        throw new InvalidArgumentException('Appointment time must use the HH:MM format.');
    }

    $time = DateTimeImmutable::createFromFormat('!H:i', $value);
    if ($time === false || $time->format('H:i') !== $value) {
        throw new InvalidArgumentException('Appointment time is invalid.');
    }

    return $value . ':00';
}

function getDoctorSchedule(PDO $connection, int $doctorId, string $appointmentDate): ?array
{
    $dayOfWeek = (int) (new DateTimeImmutable($appointmentDate))->format('N');
    $statement = $connection->prepare(
        'SELECT doctor_schedule_id, start_time, end_time, slot_duration_minutes
         FROM doctor_schedules
         WHERE doctor_id = :doctor_id
           AND day_of_week = :day_of_week
           AND is_active = 1
         ORDER BY start_time'
    );
    $statement->execute([
        'doctor_id' => $doctorId,
        'day_of_week' => $dayOfWeek,
    ]);
    $schedule = $statement->fetch();

    return is_array($schedule) ? $schedule : null;
}

function getDoctorSchedules(PDO $connection, int $doctorId, string $appointmentDate): array
{
    $dayOfWeek = (int) (new DateTimeImmutable($appointmentDate))->format('N');
    $statement = $connection->prepare(
        'SELECT doctor_schedule_id, start_time, end_time, slot_duration_minutes
         FROM doctor_schedules
         WHERE doctor_id = :doctor_id
           AND day_of_week = :day_of_week
           AND is_active = 1
         ORDER BY start_time'
    );
    $statement->execute([
        'doctor_id' => $doctorId,
        'day_of_week' => $dayOfWeek,
    ]);

    return $statement->fetchAll();
}

function getAppointmentById(PDO $connection, int $appointmentId): ?array
{
    $statement = $connection->prepare(
        'SELECT a.appointment_id, a.clinic_id, a.patient_id, a.doctor_id, a.doctor_schedule_id,
                a.appointment_date, a.start_time, a.end_time, a.reason_category, a.reason, a.status,
                a.approval_status, a.approved_at, a.approval_note,
                a.cancelled_at, a.cancellation_reason, a.created_at, a.updated_at,
                p.registration_number, CONCAT(p.first_name, " ", p.last_name) AS patient_name,
                CONCAT(s.first_name, " ", s.last_name) AS doctor_name,
                d.specialization, COALESCE(c.name, "Central HQ") AS clinic_name,
                c.photo_url AS clinic_photo
         FROM appointments a
         INNER JOIN patients p ON p.patient_id = a.patient_id
         INNER JOIN doctors d ON d.doctor_id = a.doctor_id
         INNER JOIN staff s ON s.staff_id = d.staff_id
         LEFT JOIN clinics c ON c.clinic_id = a.clinic_id
         WHERE a.appointment_id = :appointment_id
         LIMIT 1'
    );
    $statement->execute(['appointment_id' => $appointmentId]);
    $appointment = $statement->fetch();

    return is_array($appointment) ? $appointment : null;
}

function validateBooking(PDO $connection, array $data, ?int $excludeAppointmentId = null): array
{
    $patientId = filter_var($data['patient_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $doctorId = filter_var($data['doctor_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $clinicId = filter_var($data['clinic_id'] ?? 1, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

    if ($patientId === false || $doctorId === false || $clinicId === false) {
        throw new InvalidArgumentException('A registered patient, doctor, and clinic branch are required.');
    }

    $date = validateAppointmentDate($data['appointment_date'] ?? null);
    $startTime = validateAppointmentTime($data['start_time'] ?? null);
    $reason = validateOptionalString($data['reason'] ?? null, 'Reason', 255);
    $reasonCategory = validateEnum(
        $data['reason_category'] ?? 'routine',
        'Reason category',
        ['routine', 'followup', 'emergency', 'refill', 'reschedule', 'other']
    );

    $patientStatement = $connection->prepare(
        'SELECT patient_id FROM patients WHERE patient_id = :patient_id AND is_active = 1'
    );
    $patientStatement->execute(['patient_id' => $patientId]);
    if ($patientStatement->fetchColumn() === false) {
        throw new InvalidArgumentException('The selected patient does not exist.');
    }

    $clinicStatement = $connection->prepare(
        'SELECT clinic_id FROM clinics WHERE clinic_id = :clinic_id AND is_active = 1'
    );
    $clinicStatement->execute(['clinic_id' => $clinicId]);
    if ($clinicStatement->fetchColumn() === false) {
        throw new InvalidArgumentException('The selected clinic branch is unavailable.');
    }

    $doctorStatement = $connection->prepare(
        'SELECT d.doctor_id
         FROM doctors d
         INNER JOIN staff s ON s.staff_id = d.staff_id
         INNER JOIN user_accounts u ON u.user_account_id = s.user_account_id
         WHERE d.doctor_id = :doctor_id AND u.is_active = 1
           AND (d.clinic_id = :clinic_id OR d.clinic_id IS NULL)'
    );
    $doctorStatement->execute(['doctor_id' => $doctorId, 'clinic_id' => $clinicId]);
    if ($doctorStatement->fetchColumn() === false) {
        throw new InvalidArgumentException('The selected doctor does not exist.');
    }

    $schedules = getDoctorSchedules($connection, $doctorId, $date);
    if ($schedules === []) {
        throw new InvalidArgumentException('The doctor is not scheduled to work on this date.');
    }

    $start = new DateTimeImmutable($date . ' ' . $startTime);
    $schedule = null;
    foreach ($schedules as $candidate) {
        $candidateEnd = $start->modify('+' . (int) $candidate['slot_duration_minutes'] . ' minutes');
        if (
            $start >= new DateTimeImmutable($date . ' ' . $candidate['start_time'])
            && $candidateEnd <= new DateTimeImmutable($date . ' ' . $candidate['end_time'])
        ) {
            $schedule = $candidate;
            break;
        }
    }
    if ($schedule === null) {
        throw new InvalidArgumentException('The selected time is outside the doctor schedule.');
    }
    $end = $start->modify('+' . (int) $schedule['slot_duration_minutes'] . ' minutes');
    if ($start <= new DateTimeImmutable()) {
        throw new InvalidArgumentException('Appointments must be booked for a future time.');
    }

    // Check doctor slot conflict
    $conflictSql = 'SELECT appointment_id
                    FROM appointments
                    WHERE status <> "cancelled"
                      AND approval_status <> "rejected"
                      AND doctor_id = :doctor_id
                      AND appointment_date = :appointment_date
                      AND start_time < :end_time
                      AND COALESCE(end_time, start_time) > :start_time';
    $conflictParameters = [
        'doctor_id' => $doctorId,
        'appointment_date' => $date,
        'end_time' => $end->format('H:i:s'),
        'start_time' => $start->format('H:i:s'),
    ];
    if ($excludeAppointmentId !== null) {
        $conflictSql .= ' AND appointment_id <> :exclude_appointment_id';
        $conflictParameters['exclude_appointment_id'] = $excludeAppointmentId;
    }
    $conflictSql .= ' LIMIT 1';
    $conflictStatement = $connection->prepare($conflictSql);
    $conflictStatement->execute($conflictParameters);
    if ($conflictStatement->fetchColumn() !== false) {
        throw new DomainException('The selected doctor slot is already booked. Please choose another time.');
    }

    // Check patient conflict (cannot book 2 doctors at the exact same time)
    $patientConflictSql = 'SELECT appointment_id
                           FROM appointments
                           WHERE status <> "cancelled"
                             AND approval_status <> "rejected"
                             AND patient_id = :patient_id
                             AND appointment_date = :appointment_date
                             AND start_time < :end_time
                             AND COALESCE(end_time, start_time) > :start_time';
    $patientConflictParams = [
        'patient_id' => $patientId,
        'appointment_date' => $date,
        'end_time' => $end->format('H:i:s'),
        'start_time' => $start->format('H:i:s'),
    ];
    if ($excludeAppointmentId !== null) {
        $patientConflictSql .= ' AND appointment_id <> :exclude_appointment_id';
        $patientConflictParams['exclude_appointment_id'] = $excludeAppointmentId;
    }
    $patientConflictSql .= ' LIMIT 1';
    $patientConflictStmt = $connection->prepare($patientConflictSql);
    $patientConflictStmt->execute($patientConflictParams);
    if ($patientConflictStmt->fetchColumn() !== false) {
        throw new DomainException('You already have another active appointment at this time.');
    }

    return [
        'clinic_id' => (int) $clinicId,
        'patient_id' => (int) $patientId,
        'doctor_id' => (int) $doctorId,
        'appointment_date' => $date,
        'start_time' => $start->format('H:i:s'),
        'end_time' => $end->format('H:i:s'),
        'reason_category' => $reasonCategory,
        'reason' => $reason,
        'doctor_schedule_id' => (int) $schedule['doctor_schedule_id'],
    ];
}

function appointmentApiException(Throwable $exception): never
{
    if ($exception instanceof InvalidArgumentException || $exception instanceof DomainException) {
        sendJsonError($exception->getMessage(), $exception instanceof DomainException ? 409 : 422);
    }
    if ($exception instanceof PDOException && $exception->getCode() === '23000') {
        sendJsonError('The selected appointment slot is no longer available. Please choose another time.', 409);
    }

    error_log('CQAMS appointment API error: ' . $exception->getMessage());
    sendJsonError('The appointment request could not be completed.', 500);
}
