<?php
declare(strict_types=1);

require_once __DIR__ . '/common.php';

requirePatientAccess();
$patientId = getPatientIdFromRequest();

try {
    $connection = getDatabaseConnection();

    if (requestMethodIs('GET')) {
        $patient = getPatientById($connection, $patientId);
        if ($patient === null) {
            sendJsonError('Patient not found.', 404);
        }

        sendJsonSuccess([
            'patient' => $patient,
            'appointments' => getPatientAppointments($connection, $patientId),
        ]);
    }

    if (requestMethodIs('PUT') || requestMethodIs('PATCH')) {
        requireMutationRequest();
        $data = validatePatientData(getJsonRequestBody());
        $patient = getPatientById($connection, $patientId);
        if ($patient === null) {
            sendJsonError('Patient not found.', 404);
        }

        $duplicate = findPossibleDuplicate($connection, $data, $patientId);
        if ($duplicate !== null) {
            sendJsonError(
                'The updated information matches another patient record.',
                409,
                ['existing_patient' => $duplicate]
            );
        }

        $statement = $connection->prepare(
            'UPDATE patients
             SET national_id = :national_id, first_name = :first_name,
                 last_name = :last_name, date_of_birth = :date_of_birth,
                 sex = :sex, phone = :phone, email = :email,
                 address = :address, emergency_contact_name = :emergency_contact_name,
                 emergency_contact_phone = :emergency_contact_phone
             WHERE patient_id = :patient_id AND is_active = 1'
        );
        $statement->execute(array_merge($data, ['patient_id' => $patientId]));

        sendJsonSuccess([
            'patient' => getPatientById($connection, $patientId),
        ]);
    }

    sendJsonError('Method not allowed.', 405);
} catch (Throwable $exception) {
    patientApiException($exception);
}
