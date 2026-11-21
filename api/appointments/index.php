<?php
declare(strict_types=1);

require_once __DIR__ . '/common.php';

requireRole('administrator');
try {
    $connection = getDatabaseConnection();
    if (requestMethodIs('GET')) {
        $date = trim((string) ($_GET['date'] ?? ''));
        $status = trim((string) ($_GET['status'] ?? ''));
        $query = trim((string) ($_GET['q'] ?? ''));
        $clinicId = filter_var($_GET['clinic_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        $sql = 'SELECT a.appointment_id, a.clinic_id, a.appointment_date, a.start_time, a.end_time,
                       a.reason_category, a.reason, a.status, a.approval_status, p.registration_number,
                       EXISTS (
                           SELECT 1 FROM queues q WHERE q.appointment_id = a.appointment_id
                       ) AS has_queue_history,
                       CONCAT(p.first_name, " ", p.last_name) AS patient_name,
                       CONCAT(s.first_name, " ", s.last_name) AS doctor_name,
                       COALESCE(c.name, "Central HQ") AS clinic_name
                FROM appointments a
                INNER JOIN patients p ON p.patient_id = a.patient_id
                INNER JOIN doctors d ON d.doctor_id = a.doctor_id
                INNER JOIN staff s ON s.staff_id = d.staff_id
                LEFT JOIN clinics c ON c.clinic_id = a.clinic_id
                WHERE (:date = "" OR a.appointment_date = :appointment_date)
                  AND (:status = "" OR a.status = :status_value)
                  AND (
                       :query = ""
                       OR p.registration_number LIKE :search_reference
                       OR p.first_name LIKE :search_first_name
                       OR p.last_name LIKE :search_last_name
                       OR s.first_name LIKE :search_doctor_first
                       OR s.last_name LIKE :search_doctor_last
                  )';

        $search = '%' . $query . '%';
        $params = [
            'date' => $date,
            'appointment_date' => $date,
            'status' => $status,
            'status_value' => $status,
            'query' => $query,
            'search_reference' => $search,
            'search_first_name' => $search,
            'search_last_name' => $search,
            'search_doctor_first' => $search,
            'search_doctor_last' => $search,
        ];

        if ($clinicId !== false) {
            $sql .= ' AND a.clinic_id = :clinic_id';
            $params['clinic_id'] = $clinicId;
        }

        $sql .= ' ORDER BY a.appointment_date DESC, a.start_time DESC LIMIT 200';

        $statement = $connection->prepare($sql);
        $statement->execute($params);
        sendJsonSuccess(['appointments' => $statement->fetchAll()]);
    }

    if (requestMethodIs('POST')) {
        requireAppointmentMutation();
        $connection->beginTransaction();
        try {
            $booking = validateBooking($connection, getJsonRequestBody());
            $statement = $connection->prepare(
                'INSERT INTO appointments (
                    clinic_id, patient_id, doctor_id, doctor_schedule_id, appointment_date,
                    start_time, end_time, reason_category, reason, created_by_user_account_id
                 ) VALUES (
                    :clinic_id, :patient_id, :doctor_id, :doctor_schedule_id, :appointment_date,
                    :start_time, :end_time, :reason_category, :reason, :created_by_user_account_id
                 )'
            );
            $user = getAuthenticatedUser();
            $statement->execute(array_merge($booking, [
                'created_by_user_account_id' => $user['user_account_id'],
            ]));
            $appointment = getAppointmentById($connection, (int) $connection->lastInsertId());
            $connection->commit();
            sendJsonSuccess(['appointment' => $appointment], 201);
        } catch (Throwable $exception) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }
            throw $exception;
        }
    }

    sendJsonError('Method not allowed.', 405);
} catch (Throwable $exception) {
    appointmentApiException($exception);
}
