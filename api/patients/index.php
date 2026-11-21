<?php
declare(strict_types=1);

require_once __DIR__ . '/common.php';

requirePatientAccess();

try {
    $connection = getDatabaseConnection();

    if (requestMethodIs('GET')) {
        $query = trim((string) ($_GET['q'] ?? ''));
        $statement = $connection->prepare(
            'SELECT patient_id, registration_number, first_name, last_name,
                    date_of_birth, sex, phone, email, created_at
             FROM patients
             WHERE is_active = 1
               AND (
                    :query = ""
                    OR registration_number LIKE :search_registration
                    OR national_id LIKE :search_national
                    OR first_name LIKE :search_first_name
                    OR last_name LIKE :search_last_name
                    OR phone LIKE :search_phone
               )
             ORDER BY last_name, first_name
             LIMIT 100'
        );
        $statement->execute([
            'query' => $query,
            'search_registration' => '%' . $query . '%',
            'search_national' => '%' . $query . '%',
            'search_first_name' => '%' . $query . '%',
            'search_last_name' => '%' . $query . '%',
            'search_phone' => '%' . $query . '%',
        ]);
        sendJsonSuccess(['patients' => $statement->fetchAll()]);
    }

    if (requestMethodIs('POST')) {
        requireMutationRequest();
        $data = validatePatientData(getJsonRequestBody());
        $duplicate = findPossibleDuplicate($connection, $data);
        if ($duplicate !== null) {
            sendJsonError(
                'A possible matching patient already exists. Verify the patient before registering again.',
                409,
                ['existing_patient' => $duplicate]
            );
        }

        $reference = generatePatientReference($connection);
        $statement = $connection->prepare(
            'INSERT INTO patients (
                registration_number, national_id, first_name, last_name,
                date_of_birth, sex, phone, email, address,
                emergency_contact_name, emergency_contact_phone
             ) VALUES (
                :registration_number, :national_id, :first_name, :last_name,
                :date_of_birth, :sex, :phone, :email, :address,
                :emergency_contact_name, :emergency_contact_phone
             )'
        );
        $statement->execute(array_merge(
            ['registration_number' => $reference],
            $data
        ));

        sendJsonSuccess([
            'patient' => getPatientById($connection, (int) $connection->lastInsertId()),
        ], 201);
    }

    sendJsonError('Method not allowed.', 405);
} catch (Throwable $exception) {
    patientApiException($exception);
}
