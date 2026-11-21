-- One-time migration for an existing CQAMS database.
-- Back up the database before running this file.
USE cqams;

ALTER TABLE appointments
    DROP INDEX uq_appointments_active_doctor_slot,
    DROP INDEX uq_appointments_active_patient_slot;

ALTER TABLE appointments
    ADD COLUMN approval_status ENUM('pending', 'approved', 'rejected')
        NOT NULL DEFAULT 'approved' AFTER reason,
    ADD COLUMN approved_by_user_account_id BIGINT UNSIGNED NULL AFTER approval_status,
    ADD COLUMN approved_at DATETIME NULL AFTER approved_by_user_account_id,
    ADD COLUMN approval_note VARCHAR(255) NULL AFTER approved_at,
    ADD KEY idx_appointments_approval_date (approval_status, appointment_date);

ALTER TABLE appointments
    MODIFY COLUMN active_doctor_slot VARCHAR(180)
        GENERATED ALWAYS AS (
            IF(
                status = 'cancelled' OR approval_status = 'rejected',
                NULL,
                CONCAT(doctor_id, '|', appointment_date, '|', start_time)
            )
        ) STORED,
    MODIFY COLUMN active_patient_slot VARCHAR(180)
        GENERATED ALWAYS AS (
            IF(
                status = 'cancelled' OR approval_status = 'rejected',
                NULL,
                CONCAT(patient_id, '|', appointment_date, '|', start_time)
            )
        ) STORED;

ALTER TABLE appointments
    ADD UNIQUE KEY uq_appointments_active_doctor_slot (active_doctor_slot),
    ADD UNIQUE KEY uq_appointments_active_patient_slot (active_patient_slot),
    ADD CONSTRAINT fk_appointments_approved_by
        FOREIGN KEY (approved_by_user_account_id) REFERENCES user_accounts (user_account_id)
        ON UPDATE CASCADE
        ON DELETE SET NULL;
