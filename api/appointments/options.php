<?php
declare(strict_types=1);

require_once __DIR__ . '/common.php';

requireAppointmentAccess();
try {
    $connection = getDatabaseConnection();
    $date = validateAppointmentDate($_GET['date'] ?? null);
    $doctorId = filter_var($_GET['doctor_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $excludeId = filter_var($_GET['exclude_appointment_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

    if ($doctorId === false) {
        sendJsonError('A valid doctor is required.', 422);
    }
    $schedules = getDoctorSchedules($connection, (int) $doctorId, $date);
    if ($schedules === []) {
        sendJsonSuccess(['slots' => []]);
    }

    $sql = 'SELECT start_time, end_time FROM appointments
            WHERE doctor_id = :doctor_id AND appointment_date = :appointment_date
      AND status <> "cancelled" AND approval_status <> "rejected"';
    $params = ['doctor_id' => $doctorId, 'appointment_date' => $date];

    if ($excludeId !== false) {
        $sql .= ' AND appointment_id <> :exclude_id';
        $params['exclude_id'] = $excludeId;
    }

    $appointments = $connection->prepare($sql);
    $appointments->execute($params);
    $booked = $appointments->fetchAll();
    $bookedSlots = [];
    foreach ($booked as $appointment) {
        $bookedSlots[$appointment['start_time']] = true;
    }

    $slots = [];
    foreach ($schedules as $schedule) {
        $cursor = new DateTimeImmutable($date . ' ' . $schedule['start_time']);
        $end = new DateTimeImmutable($date . ' ' . $schedule['end_time']);
        $duration = (int) $schedule['slot_duration_minutes'];
        while ($cursor->modify('+' . $duration . ' minutes') <= $end) {
            $time = $cursor->format('H:i');
            $slots[$time] = [
                'time' => $time,
                'available' => $cursor > new DateTimeImmutable() && !isset($bookedSlots[$time . ':00']),
            ];
            $cursor = $cursor->modify('+' . $duration . ' minutes');
        }
    }
    sendJsonSuccess(['slots' => array_values($slots)]);
} catch (Throwable $exception) {
    appointmentApiException($exception);
}
