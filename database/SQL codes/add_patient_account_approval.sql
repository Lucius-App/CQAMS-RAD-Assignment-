-- One-time migration for existing databases. Back up cqams before running.
USE cqams;

ALTER TABLE user_accounts
    ADD COLUMN approval_status ENUM('pending', 'approved', 'rejected')
        NOT NULL DEFAULT 'approved' AFTER role,
    ADD KEY idx_user_accounts_approval (approval_status);

ALTER TABLE patients
    ADD COLUMN user_account_id BIGINT UNSIGNED NULL AFTER patient_id,
    ADD UNIQUE KEY uq_patients_user_account (user_account_id),
    ADD CONSTRAINT fk_patients_user_account
        FOREIGN KEY (user_account_id) REFERENCES user_accounts (user_account_id)
        ON UPDATE CASCADE
        ON DELETE SET NULL;
