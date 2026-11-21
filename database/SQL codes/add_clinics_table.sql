-- One-time migration for existing databases that predate clinic support.
-- Back up cqams before running this file.
USE cqams;

CREATE TABLE clinics (
    clinic_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_code VARCHAR(30) NOT NULL,
    name VARCHAR(120) NOT NULL,
    address VARCHAR(255) NOT NULL,
    phone VARCHAR(30) NOT NULL,
    email VARCHAR(254) NULL,
    photo_url VARCHAR(255) NULL,
    description TEXT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (clinic_id),
    UNIQUE KEY uq_clinics_code (clinic_code)
) ENGINE = InnoDB;

INSERT IGNORE INTO clinics (clinic_code, name, address, phone, email, photo_url, description)
VALUES
    ('MAIN', 'CQAMS Central Clinic', '123 Healthcare Ave, City Center', '+60 3-8888 1000', 'central@cqams.test', '/CQMS/assets/images/clinic_main.svg', 'Main clinic facility offering multi-specialty outpatient services, acute care, and diagnostic facilities.'),
    ('NORTH', 'CQAMS North Branch', '45 Medical Boulevard, North Suburb', '+60 3-8888 2000', 'north@cqams.test', '/CQMS/assets/images/clinic_north.svg', 'Modern community clinic providing family medicine, paediatrics, and preventative health checks.'),
    ('SOUTH', 'CQAMS South Specialist Centre', '88 Wellness Road, South City', '+60 3-8888 3000', 'south@cqams.test', '/CQMS/assets/images/clinic_south.svg', 'Specialist outpatient care centre focused on chronic disease management and wellness programs.');

ALTER TABLE staff
    ADD COLUMN clinic_id BIGINT UNSIGNED NULL DEFAULT 1 AFTER user_account_id,
    ADD KEY idx_staff_clinic (clinic_id),
    ADD CONSTRAINT fk_staff_clinic
        FOREIGN KEY (clinic_id) REFERENCES clinics (clinic_id)
        ON UPDATE CASCADE
        ON DELETE SET NULL;

ALTER TABLE doctors
    ADD COLUMN clinic_id BIGINT UNSIGNED NULL DEFAULT 1 AFTER staff_id,
    ADD KEY idx_doctors_clinic (clinic_id),
    ADD CONSTRAINT fk_doctors_clinic
        FOREIGN KEY (clinic_id) REFERENCES clinics (clinic_id)
        ON UPDATE CASCADE
        ON DELETE SET NULL;

ALTER TABLE appointments
    ADD COLUMN clinic_id BIGINT UNSIGNED NULL DEFAULT 1 AFTER appointment_id,
    ADD KEY idx_appointments_clinic (clinic_id),
    ADD CONSTRAINT fk_appointments_clinic
        FOREIGN KEY (clinic_id) REFERENCES clinics (clinic_id)
        ON UPDATE CASCADE
        ON DELETE SET NULL;

ALTER TABLE queues
    ADD COLUMN clinic_id BIGINT UNSIGNED NULL DEFAULT 1 AFTER queue_id,
    ADD KEY idx_queues_clinic (clinic_id),
    ADD CONSTRAINT fk_queues_clinic
        FOREIGN KEY (clinic_id) REFERENCES clinics (clinic_id)
        ON UPDATE CASCADE
        ON DELETE SET NULL;
