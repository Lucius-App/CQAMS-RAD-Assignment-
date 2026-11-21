<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/permissions.php';
require_once __DIR__ . '/../common/response.php';
require_once __DIR__ . '/../common/validation.php';

requireRole('administrator');

function requireAdminCsrf(): void
{
    startSecureSession();
    if (!isValidCsrfToken($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
        sendJsonError('Your session token is invalid or has expired. Refresh and try again.', 419);
    }
}

try {
    $db = getDatabaseConnection();
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    if ($method === 'GET') {
        $stmt = $db->query(
            'SELECT clinic_id, clinic_code, name, address, phone, email, photo_url, description, is_active, created_at
             FROM clinics
             ORDER BY clinic_id'
        );
        sendJsonSuccess(['clinics' => $stmt->fetchAll()]);
    }

    if ($method === 'POST') {
        requireAdminCsrf();
        $body = json_decode((string) file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($body)) {
            sendJsonError('Invalid request format.', 422);
        }

        $action = $body['action'] ?? '';

        if ($action === 'create') {
            $code = strtoupper(validateRequiredString($body['clinic_code'] ?? null, 'Clinic Code', 30));
            $name = validateRequiredString($body['name'] ?? null, 'Clinic Name', 120);
            $address = validateRequiredString($body['address'] ?? null, 'Address', 255);
            $phone = validateRequiredString($body['phone'] ?? null, 'Phone Number', 30);
            $email = validateOptionalString($body['email'] ?? null, 'Email', 254);
            $photoUrl = validateOptionalString($body['photo_url'] ?? null, 'Photo URL', 255);
            $description = validateOptionalString($body['description'] ?? null, 'Description', 1000);

            $stmt = $db->prepare(
                'INSERT INTO clinics (clinic_code, name, address, phone, email, photo_url, description, is_active)
                 VALUES (:code, :name, :address, :phone, :email, :photo_url, :description, 1)'
            );
            $stmt->execute([
                'code' => $code,
                'name' => $name,
                'address' => $address,
                'phone' => $phone,
                'email' => $email,
                'photo_url' => $photoUrl ?: '/CQMS/assets/images/clinic_main.svg',
                'description' => $description,
            ]);

            sendJsonSuccess(['clinic_id' => (int) $db->lastInsertId()], 201);
        }

        if ($action === 'update') {
            $clinicId = filter_var($body['clinic_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($clinicId === false) {
                sendJsonError('A valid clinic ID is required.', 422);
            }

            $name = validateRequiredString($body['name'] ?? null, 'Clinic Name', 120);
            $address = validateRequiredString($body['address'] ?? null, 'Address', 255);
            $phone = validateRequiredString($body['phone'] ?? null, 'Phone Number', 30);
            $email = validateOptionalString($body['email'] ?? null, 'Email', 254);
            $photoUrl = validateOptionalString($body['photo_url'] ?? null, 'Photo URL', 255);
            $description = validateOptionalString($body['description'] ?? null, 'Description', 1000);

            $stmt = $db->prepare(
                'UPDATE clinics
                 SET name = :name, address = :address, phone = :phone, email = :email,
                     photo_url = :photo_url, description = :description
                 WHERE clinic_id = :id'
            );
            $stmt->execute([
                'name' => $name,
                'address' => $address,
                'phone' => $phone,
                'email' => $email,
                'photo_url' => $photoUrl,
                'description' => $description,
                'id' => $clinicId,
            ]);

            sendJsonSuccess(['message' => 'Clinic updated successfully.']);
        }

        if ($action === 'delete') {
            $clinicId = filter_var($body['clinic_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($clinicId === false) {
                sendJsonError('A valid clinic ID is required.', 422);
            }

            $db->beginTransaction();
            try {
                $clinic = $db->prepare('SELECT clinic_id FROM clinics WHERE clinic_id = :id FOR UPDATE');
                $clinic->execute(['id' => $clinicId]);
                if ($clinic->fetchColumn() === false) {
                    $db->rollBack();
                    sendJsonError('Clinic branch not found.', 404);
                }

                $references = $db->prepare(
                    'SELECT
                        EXISTS (SELECT 1 FROM staff WHERE clinic_id = :staff_id)
                        OR EXISTS (SELECT 1 FROM doctors WHERE clinic_id = :doctor_id)
                        OR EXISTS (SELECT 1 FROM appointments WHERE clinic_id = :appointment_id)
                        OR EXISTS (SELECT 1 FROM queues WHERE clinic_id = :queue_id)'
                );
                $references->execute([
                    'staff_id' => $clinicId,
                    'doctor_id' => $clinicId,
                    'appointment_id' => $clinicId,
                    'queue_id' => $clinicId,
                ]);
                if ((int) $references->fetchColumn() === 1) {
                    $db->rollBack();
                    sendJsonError(
                        'Cannot delete a branch linked to staff, doctors, appointments, or queue history.',
                        409
                    );
                }

                $stmt = $db->prepare('DELETE FROM clinics WHERE clinic_id = :id');
                $stmt->execute(['id' => $clinicId]);
                if ($stmt->rowCount() !== 1) {
                    $db->rollBack();
                    sendJsonError('Clinic branch was not deleted. Refresh and try again.', 409);
                }
                $db->commit();
            } catch (Throwable $exception) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
                throw $exception;
            }

            sendJsonSuccess(['message' => 'Clinic branch deleted successfully.']);
        }

        sendJsonError('Unknown clinic action.', 422);
    }

    sendJsonError('Method not allowed.', 405);
} catch (Throwable $e) {
    if ($e instanceof PDOException && $e->getCode() === '23000') {
        sendJsonError('A clinic with this unique code already exists.', 409);
    }
    error_log('Admin clinics API error: ' . $e->getMessage());
    sendJsonError('Clinic request could not be completed.', 500);
}
