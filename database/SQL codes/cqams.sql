-- CQAMS database schema
-- MySQL 8.0+ / InnoDB

CREATE DATABASE IF NOT EXISTS cqams
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE cqams;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS queue_events;
DROP TABLE IF EXISTS prescriptions;
DROP TABLE IF EXISTS consultations;
DROP TABLE IF EXISTS queues;
DROP TABLE IF EXISTS appointments;
DROP TABLE IF EXISTS doctor_schedules;
DROP TABLE IF EXISTS doctors;
DROP TABLE IF EXISTS patients;
DROP TABLE IF EXISTS staff;
DROP TABLE IF EXISTS clinics;
DROP TABLE IF EXISTS user_accounts;

SET FOREIGN_KEY_CHECKS = 1;

-- 1. Clinics / Branches table
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

-- 2. User Accounts table (Administrator, Doctor, Patient)
CREATE TABLE user_accounts (
    user_account_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    username VARCHAR(80) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('administrator', 'doctor', 'patient') NOT NULL,
    approval_status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'approved',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    last_login_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (user_account_id),
    UNIQUE KEY uq_user_accounts_username (username),
    KEY idx_user_accounts_role_active (role, is_active),
    KEY idx_user_accounts_approval (approval_status)
) ENGINE = InnoDB;

-- 3. Staff table (linked to user_accounts and clinics)
CREATE TABLE staff (
    staff_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_account_id BIGINT UNSIGNED NOT NULL,
    clinic_id BIGINT UNSIGNED NULL DEFAULT 1,
    employee_number VARCHAR(50) NULL,
    first_name VARCHAR(80) NOT NULL,
    last_name VARCHAR(80) NOT NULL,
    phone VARCHAR(30) NULL,
    email VARCHAR(254) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (staff_id),
    UNIQUE KEY uq_staff_user_account (user_account_id),
    UNIQUE KEY uq_staff_employee_number (employee_number),
    KEY idx_staff_name (last_name, first_name),
    CONSTRAINT fk_staff_user_account
        FOREIGN KEY (user_account_id) REFERENCES user_accounts (user_account_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT fk_staff_clinic
        FOREIGN KEY (clinic_id) REFERENCES clinics (clinic_id)
        ON UPDATE CASCADE
        ON DELETE SET NULL
) ENGINE = InnoDB;

-- 4. Patients table (linked optionally to a patient user_account)
CREATE TABLE patients (
    patient_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_account_id BIGINT UNSIGNED NULL,
    registration_number VARCHAR(50) NOT NULL,
    national_id VARCHAR(50) NULL,
    first_name VARCHAR(80) NOT NULL,
    last_name VARCHAR(80) NOT NULL,
    date_of_birth DATE NULL,
    sex ENUM('female', 'male', 'other', 'undisclosed') NOT NULL DEFAULT 'undisclosed',
    phone VARCHAR(30) NULL,
    email VARCHAR(254) NULL,
    address VARCHAR(255) NULL,
    emergency_contact_name VARCHAR(160) NULL,
    emergency_contact_phone VARCHAR(30) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (patient_id),
    UNIQUE KEY uq_patients_registration_number (registration_number),
    UNIQUE KEY uq_patients_national_id (national_id),
    UNIQUE KEY uq_patients_user_account (user_account_id),
    KEY idx_patients_name (last_name, first_name),
    KEY idx_patients_phone (phone),
    CONSTRAINT fk_patients_user_account
        FOREIGN KEY (user_account_id) REFERENCES user_accounts (user_account_id)
        ON UPDATE CASCADE
        ON DELETE SET NULL
) ENGINE = InnoDB;

-- 5. Doctors table (linked to staff and clinics)
CREATE TABLE doctors (
    doctor_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    staff_id BIGINT UNSIGNED NOT NULL,
    clinic_id BIGINT UNSIGNED NULL DEFAULT 1,
    specialization VARCHAR(120) NOT NULL,
    license_number VARCHAR(80) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (doctor_id),
    UNIQUE KEY uq_doctors_staff (staff_id),
    UNIQUE KEY uq_doctors_license_number (license_number),
    KEY idx_doctors_specialization (specialization),
    CONSTRAINT fk_doctors_staff
        FOREIGN KEY (staff_id) REFERENCES staff (staff_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT fk_doctors_clinic
        FOREIGN KEY (clinic_id) REFERENCES clinics (clinic_id)
        ON UPDATE CASCADE
        ON DELETE SET NULL
) ENGINE = InnoDB;

-- 6. Doctor Schedules table
CREATE TABLE doctor_schedules (
    doctor_schedule_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    doctor_id BIGINT UNSIGNED NOT NULL,
    day_of_week TINYINT UNSIGNED NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    slot_duration_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 30,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (doctor_schedule_id),
    UNIQUE KEY uq_doctor_schedule_slot (doctor_id, day_of_week, start_time, end_time),
    KEY idx_doctor_schedules_lookup (doctor_id, day_of_week, is_active),
    CONSTRAINT fk_doctor_schedules_doctor
        FOREIGN KEY (doctor_id) REFERENCES doctors (doctor_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT chk_doctor_schedules_day
        CHECK (day_of_week BETWEEN 1 AND 7),
    CONSTRAINT chk_doctor_schedules_time
        CHECK (end_time > start_time),
    CONSTRAINT chk_doctor_schedules_slot_duration
        CHECK (slot_duration_minutes BETWEEN 5 AND 480)
) ENGINE = InnoDB;

-- 7. Appointments table
CREATE TABLE appointments (
    appointment_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id BIGINT UNSIGNED NULL DEFAULT 1,
    patient_id BIGINT UNSIGNED NOT NULL,
    doctor_id BIGINT UNSIGNED NOT NULL,
    doctor_schedule_id BIGINT UNSIGNED NULL,
    appointment_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NULL,
    reason_category ENUM('routine', 'followup', 'emergency', 'refill', 'reschedule', 'other') NOT NULL DEFAULT 'routine',
    reason VARCHAR(255) NULL,
    approval_status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'approved',
    approved_by_user_account_id BIGINT UNSIGNED NULL,
    approved_at DATETIME NULL,
    approval_note VARCHAR(255) NULL,
    status ENUM('scheduled', 'confirmed', 'completed', 'cancelled', 'no_show') NOT NULL DEFAULT 'scheduled',
    created_by_user_account_id BIGINT UNSIGNED NULL,
    cancelled_by_user_account_id BIGINT UNSIGNED NULL,
    cancelled_at DATETIME NULL,
    cancellation_reason VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (appointment_id),
    active_doctor_slot VARCHAR(180)
        GENERATED ALWAYS AS (
            IF(
                status = 'cancelled' OR approval_status = 'rejected',
                NULL,
                CONCAT(doctor_id, '|', appointment_date, '|', start_time)
            )
        ) STORED,
    active_patient_slot VARCHAR(180)
        GENERATED ALWAYS AS (
            IF(
                status = 'cancelled' OR approval_status = 'rejected',
                NULL,
                CONCAT(patient_id, '|', appointment_date, '|', start_time)
            )
        ) STORED,
    UNIQUE KEY uq_appointments_active_doctor_slot (active_doctor_slot),
    UNIQUE KEY uq_appointments_active_patient_slot (active_patient_slot),
    KEY idx_appointments_date_status (appointment_date, status),
    KEY idx_appointments_approval_date (approval_status, appointment_date),
    KEY idx_appointments_patient_date (patient_id, appointment_date),
    KEY idx_appointments_doctor_date (doctor_id, appointment_date),
    CONSTRAINT fk_appointments_clinic
        FOREIGN KEY (clinic_id) REFERENCES clinics (clinic_id)
        ON UPDATE CASCADE
        ON DELETE SET NULL,
    CONSTRAINT fk_appointments_patient
        FOREIGN KEY (patient_id) REFERENCES patients (patient_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT fk_appointments_doctor
        FOREIGN KEY (doctor_id) REFERENCES doctors (doctor_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT fk_appointments_schedule
        FOREIGN KEY (doctor_schedule_id) REFERENCES doctor_schedules (doctor_schedule_id)
        ON UPDATE CASCADE
        ON DELETE SET NULL,
    CONSTRAINT fk_appointments_created_by
        FOREIGN KEY (created_by_user_account_id) REFERENCES user_accounts (user_account_id)
        ON UPDATE CASCADE
        ON DELETE SET NULL,
    CONSTRAINT fk_appointments_cancelled_by
        FOREIGN KEY (cancelled_by_user_account_id) REFERENCES user_accounts (user_account_id)
        ON UPDATE CASCADE
        ON DELETE SET NULL,
    CONSTRAINT fk_appointments_approved_by
        FOREIGN KEY (approved_by_user_account_id) REFERENCES user_accounts (user_account_id)
        ON UPDATE CASCADE
        ON DELETE SET NULL,
    CONSTRAINT chk_appointments_end_time
        CHECK (end_time IS NULL OR end_time > start_time),
    CONSTRAINT chk_appointments_cancellation
        CHECK (
            (status <> 'cancelled' AND cancelled_at IS NULL)
            OR (status = 'cancelled' AND cancelled_at IS NOT NULL)
        )
) ENGINE = InnoDB;

-- 8. Queues table
CREATE TABLE queues (
    queue_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id BIGINT UNSIGNED NULL DEFAULT 1,
    clinic_code VARCHAR(30) NOT NULL DEFAULT 'MAIN',
    queue_date DATE NOT NULL,
    queue_number INT UNSIGNED NOT NULL,
    patient_id BIGINT UNSIGNED NOT NULL,
    appointment_id BIGINT UNSIGNED NULL,
    doctor_id BIGINT UNSIGNED NOT NULL,
    status ENUM('waiting', 'called', 'in_consultation', 'completed', 'skipped', 'cancelled') NOT NULL DEFAULT 'waiting',
    checked_in_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    called_at DATETIME NULL,
    completed_at DATETIME NULL,
    checked_in_by_user_account_id BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (queue_id),
    UNIQUE KEY uq_queues_daily_number (clinic_code, queue_date, queue_number),
    UNIQUE KEY uq_queues_appointment (appointment_id),
    UNIQUE KEY uq_queues_patient_day (clinic_code, queue_date, patient_id),
    KEY idx_queues_display (clinic_code, queue_date, status, queue_number),
    KEY idx_queues_doctor_status (doctor_id, queue_date, status),
    CONSTRAINT fk_queues_clinic
        FOREIGN KEY (clinic_id) REFERENCES clinics (clinic_id)
        ON UPDATE CASCADE
        ON DELETE SET NULL,
    CONSTRAINT fk_queues_patient
        FOREIGN KEY (patient_id) REFERENCES patients (patient_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT fk_queues_appointment
        FOREIGN KEY (appointment_id) REFERENCES appointments (appointment_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT fk_queues_doctor
        FOREIGN KEY (doctor_id) REFERENCES doctors (doctor_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT fk_queues_checked_in_by
        FOREIGN KEY (checked_in_by_user_account_id) REFERENCES user_accounts (user_account_id)
        ON UPDATE CASCADE
        ON DELETE SET NULL,
    CONSTRAINT chk_queues_number
        CHECK (queue_number > 0),
    CONSTRAINT chk_queues_called_time
        CHECK (status IN ('waiting', 'skipped', 'cancelled') OR called_at IS NOT NULL),
    CONSTRAINT chk_queues_completed_time
        CHECK (status NOT IN ('completed') OR completed_at IS NOT NULL)
) ENGINE = InnoDB;

-- 9. Consultations table
CREATE TABLE consultations (
    consultation_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    queue_id BIGINT UNSIGNED NOT NULL,
    doctor_id BIGINT UNSIGNED NOT NULL,
    status ENUM('in_progress', 'completed', 'referred', 'cancelled') NOT NULL DEFAULT 'in_progress',
    started_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ended_at DATETIME NULL,
    notes TEXT NULL,
    outcome VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (consultation_id),
    UNIQUE KEY uq_consultations_queue (queue_id),
    KEY idx_consultations_doctor_status (doctor_id, status),
    CONSTRAINT fk_consultations_queue
        FOREIGN KEY (queue_id) REFERENCES queues (queue_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT fk_consultations_doctor
        FOREIGN KEY (doctor_id) REFERENCES doctors (doctor_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT chk_consultations_end_time
        CHECK (
            (status = 'in_progress' AND ended_at IS NULL)
            OR (status <> 'in_progress' AND ended_at IS NOT NULL)
        )
) ENGINE = InnoDB;

-- 10. Prescriptions table (Medications prescribed during consultations)
CREATE TABLE prescriptions (
    prescription_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    consultation_id BIGINT UNSIGNED NOT NULL,
    medicine_name VARCHAR(120) NOT NULL,
    dosage VARCHAR(80) NOT NULL,
    frequency VARCHAR(120) NOT NULL,
    duration VARCHAR(80) NOT NULL,
    instructions VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (prescription_id),
    KEY idx_prescriptions_consultation (consultation_id),
    CONSTRAINT fk_prescriptions_consultation
        FOREIGN KEY (consultation_id) REFERENCES consultations (consultation_id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE = InnoDB;

-- 11. Queue Events audit trail table
CREATE TABLE queue_events (
    queue_event_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    queue_id BIGINT UNSIGNED NOT NULL,
    previous_status ENUM('waiting', 'called', 'in_consultation', 'completed', 'skipped', 'cancelled') NULL,
    new_status ENUM('waiting', 'called', 'in_consultation', 'completed', 'skipped', 'cancelled') NOT NULL,
    changed_by_user_account_id BIGINT UNSIGNED NULL,
    event_note VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (queue_event_id),
    KEY idx_queue_events_queue_time (queue_id, created_at),
    CONSTRAINT fk_queue_events_queue
        FOREIGN KEY (queue_id) REFERENCES queues (queue_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT fk_queue_events_changed_by
        FOREIGN KEY (changed_by_user_account_id) REFERENCES user_accounts (user_account_id)
        ON UPDATE CASCADE
        ON DELETE SET NULL
) ENGINE = InnoDB;

-- ============================================================================
-- SEED DATA
-- ============================================================================

-- Clinics
INSERT INTO clinics (clinic_code, name, address, phone, email, photo_url, description)
VALUES
    ('MAIN', 'CQAMS Central Clinic', '123 Healthcare Ave, City Center', '+60 3-8888 1000', 'central@cqams.test', '/CQMS/assets/images/clinic_main.svg', 'Main clinic facility offering multi-specialty outpatient services, acute care, and diagnostic facilities.'),
    ('NORTH', 'CQAMS North Branch', '45 Medical Boulevard, North Suburb', '+60 3-8888 2000', 'north@cqams.test', '/CQMS/assets/images/clinic_north.svg', 'Modern community clinic providing family medicine, paediatrics, and preventative health checks.'),
    ('SOUTH', 'CQAMS South Specialist Centre', '88 Wellness Road, South City', '+60 3-8888 3000', 'south@cqams.test', '/CQMS/assets/images/clinic_south.svg', 'Specialist outpatient care centre focused on chronic disease management and wellness programs.');

-- User Accounts
INSERT INTO user_accounts (username, password_hash, role, approval_status, is_active)
VALUES
    ('admin_demo', '$2y$10$wwyMorFZwPQZNQKJ1oORQOVsEy8mKlztK.P5h0ADP68NeqdZ0k.9S', 'administrator', 'approved', 1),
    ('doctor_demo', '$2y$10$fh7mzGP/N/hCFru6LCy0v.QKEjOlNyHolm.HNErpEk1psq/H6o7ue', 'doctor', 'approved', 1),
    ('doctor2_demo', '$2y$10$fh7mzGP/N/hCFru6LCy0v.QKEjOlNyHolm.HNErpEk1psq/H6o7ue', 'doctor', 'approved', 1),
    ('patient_demo', '$2y$10$OnAgSPLtBuT0WwvF4yItN.ncaXPSUhzqySN2xoCCUigNLHZN63psW', 'patient', 'approved', 1),
    ('pending_patient', '$2y$10$OnAgSPLtBuT0WwvF4yItN.ncaXPSUhzqySN2xoCCUigNLHZN63psW', 'patient', 'pending', 0);

-- Staff Members
INSERT INTO staff (user_account_id, clinic_id, employee_number, first_name, last_name, phone, email)
SELECT user_account_id, 1, 'EMP-001', 'System', 'Administrator', '0000000000', 'admin@example.test'
FROM user_accounts WHERE username = 'admin_demo'
UNION ALL
SELECT user_account_id, 1, 'EMP-003', 'Doctor', 'Demo', '0000000002', 'doctor@example.test'
FROM user_accounts WHERE username = 'doctor_demo'
UNION ALL
SELECT user_account_id, 2, 'EMP-004', 'Aisha', 'Rahman', '0000000003', 'aisha.doctor@example.test'
FROM user_accounts WHERE username = 'doctor2_demo';

-- Doctors
INSERT INTO doctors (staff_id, clinic_id, specialization, license_number)
SELECT staff_id, 1, 'General Medicine', 'LIC-DEMO-001'
FROM staff WHERE employee_number = 'EMP-003'
UNION ALL
SELECT staff_id, 2, 'Paediatrics', 'LIC-DEMO-002'
FROM staff WHERE employee_number = 'EMP-004';

-- Patients
INSERT INTO patients (
    user_account_id, registration_number, national_id, first_name, last_name,
    date_of_birth, sex, phone, email, address
)
VALUES
    ((SELECT user_account_id FROM user_accounts WHERE username = 'patient_demo'),
     'PAT-000001', 'DEMO-ID-001', 'Sample', 'Patient', '2000-01-15',
     'undisclosed', '0000000010', 'patient@example.test', 'Demo address 1'),
    (NULL, 'PAT-000002', 'DEMO-ID-002', 'Nurul', 'Hassan', '1995-03-22',
     'female', '0000000011', 'nurul@example.test', 'Demo address 2'),
    (NULL, 'PAT-000003', 'DEMO-ID-003', 'Jason', 'Lee', '1988-07-09',
     'male', '0000000012', 'jason@example.test', 'Demo address 3'),
    (NULL, 'PAT-000004', 'DEMO-ID-004', 'Mei', 'Wong', '2012-11-18',
     'female', '0000000013', 'mei@example.test', 'Demo address 4'),
    ((SELECT user_account_id FROM user_accounts WHERE username = 'pending_patient'),
     'PAT-000005', 'DEMO-ID-005', 'Farid', 'Aziz', '1979-01-30',
     'male', '0000000014', 'farid@example.test', 'Demo address 5');

-- Doctor Schedules (ISO weekday: 1=Mon, 2=Tue, 3=Wed, 4=Thu, 5=Fri, 6=Sat, 7=Sun)
INSERT IGNORE INTO doctor_schedules (doctor_id, day_of_week, start_time, end_time, slot_duration_minutes)
SELECT doctor_id, 1, '08:00:00', '17:00:00', 30 FROM doctors WHERE license_number = 'LIC-DEMO-001'
UNION ALL
SELECT doctor_id, 2, '08:00:00', '17:00:00', 30 FROM doctors WHERE license_number = 'LIC-DEMO-001'
UNION ALL
SELECT doctor_id, 3, '08:00:00', '17:00:00', 30 FROM doctors WHERE license_number = 'LIC-DEMO-001'
UNION ALL
SELECT doctor_id, 4, '08:00:00', '17:00:00', 30 FROM doctors WHERE license_number = 'LIC-DEMO-001'
UNION ALL
SELECT doctor_id, 5, '08:00:00', '17:00:00', 30 FROM doctors WHERE license_number = 'LIC-DEMO-001'
UNION ALL
SELECT doctor_id, 6, '09:00:00', '13:00:00', 30 FROM doctors WHERE license_number = 'LIC-DEMO-001';

INSERT IGNORE INTO doctor_schedules (doctor_id, day_of_week, start_time, end_time, slot_duration_minutes)
SELECT doctor_id, 1, '08:00:00', '16:00:00', 30 FROM doctors WHERE license_number = 'LIC-DEMO-002'
UNION ALL
SELECT doctor_id, 2, '08:00:00', '16:00:00', 30 FROM doctors WHERE license_number = 'LIC-DEMO-002'
UNION ALL
SELECT doctor_id, 3, '08:00:00', '16:00:00', 30 FROM doctors WHERE license_number = 'LIC-DEMO-002'
UNION ALL
SELECT doctor_id, 4, '08:00:00', '16:00:00', 30 FROM doctors WHERE license_number = 'LIC-DEMO-002'
UNION ALL
SELECT doctor_id, 5, '08:00:00', '16:00:00', 30 FROM doctors WHERE license_number = 'LIC-DEMO-002';

-- Appointments
INSERT INTO appointments (
    clinic_id, patient_id, doctor_id, doctor_schedule_id, appointment_date,
    start_time, end_time, reason_category, reason, status, created_by_user_account_id
)
SELECT 1, p.patient_id, d.doctor_id, s.doctor_schedule_id, CURDATE(),
       '09:00:00', '09:30:00', 'routine', 'Routine health check-up', 'completed', u.user_account_id
FROM patients p
INNER JOIN doctors d ON d.license_number = 'LIC-DEMO-001'
LEFT JOIN doctor_schedules s ON s.doctor_id = d.doctor_id AND s.day_of_week = (WEEKDAY(CURDATE()) + 1)
INNER JOIN user_accounts u ON u.username = 'admin_demo'
WHERE p.registration_number = 'PAT-000001'
LIMIT 1;

INSERT INTO appointments (
    clinic_id, patient_id, doctor_id, doctor_schedule_id, appointment_date,
    start_time, end_time, reason_category, reason, status, created_by_user_account_id
)
SELECT 2, p.patient_id, d.doctor_id, s.doctor_schedule_id, CURDATE(),
       '09:30:00', '10:00:00', 'followup', 'Paediatric follow-up consultation', 'confirmed', u.user_account_id
FROM patients p
INNER JOIN doctors d ON d.license_number = 'LIC-DEMO-002'
LEFT JOIN doctor_schedules s ON s.doctor_id = d.doctor_id AND s.day_of_week = (WEEKDAY(CURDATE()) + 1)
INNER JOIN user_accounts u ON u.username = 'admin_demo'
WHERE p.registration_number = 'PAT-000002'
LIMIT 1;

INSERT INTO appointments (
    clinic_id, patient_id, doctor_id, doctor_schedule_id, appointment_date,
    start_time, end_time, reason_category, reason, status, created_by_user_account_id
)
SELECT 2, p.patient_id, d.doctor_id, s.doctor_schedule_id, CURDATE(),
       '10:00:00', '10:30:00', 'emergency', 'Fever and acute symptoms review', 'scheduled', u.user_account_id
FROM patients p
INNER JOIN doctors d ON d.license_number = 'LIC-DEMO-002'
LEFT JOIN doctor_schedules s ON s.doctor_id = d.doctor_id AND s.day_of_week = (WEEKDAY(CURDATE()) + 1)
INNER JOIN user_accounts u ON u.username = 'admin_demo'
WHERE p.registration_number = 'PAT-000003'
LIMIT 1;

INSERT INTO appointments (
    clinic_id, patient_id, doctor_id, doctor_schedule_id, appointment_date,
    start_time, end_time, reason_category, reason, status, created_by_user_account_id
)
SELECT 1, p.patient_id, d.doctor_id, s.doctor_schedule_id, DATE_ADD(CURDATE(), INTERVAL 1 DAY),
       '09:00:00', '09:30:00', 'refill', 'Regular prescription refill and review', 'scheduled', u.user_account_id
FROM patients p
INNER JOIN doctors d ON d.license_number = 'LIC-DEMO-001'
LEFT JOIN doctor_schedules s ON s.doctor_id = d.doctor_id AND s.day_of_week = (WEEKDAY(DATE_ADD(CURDATE(), INTERVAL 1 DAY)) + 1)
INNER JOIN user_accounts u ON u.username = 'admin_demo'
WHERE p.registration_number = 'PAT-000004'
LIMIT 1;

-- Queues
INSERT INTO queues (
    clinic_id, clinic_code, queue_date, queue_number, patient_id, appointment_id, doctor_id,
    status, checked_in_at, called_at, completed_at, checked_in_by_user_account_id
)
SELECT 1, 'MAIN', CURDATE(), 1, p.patient_id, a.appointment_id, a.doctor_id,
       'completed', CURRENT_TIMESTAMP - INTERVAL 2 HOUR,
       CURRENT_TIMESTAMP - INTERVAL 90 MINUTE, CURRENT_TIMESTAMP - INTERVAL 60 MINUTE,
       u.user_account_id
FROM patients p
INNER JOIN appointments a ON a.patient_id = p.patient_id AND a.appointment_date = CURDATE() AND a.start_time = '09:00:00'
INNER JOIN user_accounts u ON u.username = 'admin_demo'
WHERE p.registration_number = 'PAT-000001'
LIMIT 1;

INSERT INTO queues (
    clinic_id, clinic_code, queue_date, queue_number, patient_id, appointment_id, doctor_id,
    status, checked_in_at, called_at, checked_in_by_user_account_id
)
SELECT 2, 'NORTH', CURDATE(), 2, p.patient_id, a.appointment_id, a.doctor_id,
       'called', CURRENT_TIMESTAMP - INTERVAL 30 MINUTE,
       CURRENT_TIMESTAMP - INTERVAL 5 MINUTE, u.user_account_id
FROM patients p
INNER JOIN appointments a ON a.patient_id = p.patient_id AND a.appointment_date = CURDATE() AND a.start_time = '09:30:00'
INNER JOIN user_accounts u ON u.username = 'admin_demo'
WHERE p.registration_number = 'PAT-000002'
LIMIT 1;

INSERT INTO queues (
    clinic_id, clinic_code, queue_date, queue_number, patient_id, appointment_id, doctor_id,
    status, checked_in_at, checked_in_by_user_account_id
)
SELECT 2, 'NORTH', CURDATE(), 3, p.patient_id, a.appointment_id, a.doctor_id,
       'waiting', CURRENT_TIMESTAMP - INTERVAL 10 MINUTE, u.user_account_id
FROM patients p
INNER JOIN appointments a ON a.patient_id = p.patient_id AND a.appointment_date = CURDATE() AND a.start_time = '10:00:00'
INNER JOIN user_accounts u ON u.username = 'admin_demo'
WHERE p.registration_number = 'PAT-000003'
LIMIT 1;

-- Consultations
INSERT INTO consultations (
    queue_id, doctor_id, status, started_at, ended_at, notes, outcome
)
SELECT q.queue_id, q.doctor_id, 'completed',
       CURRENT_TIMESTAMP - INTERVAL 90 MINUTE, CURRENT_TIMESTAMP - INTERVAL 60 MINUTE,
       'Patient presented with mild respiratory symptoms and seasonal allergies. Vital signs normal, chest clear. Prescribed antihistamine and standard analgesics. Advised adequate hydration.',
       'Recovering well - Follow-up in 1 week if symptoms persist'
FROM queues q
WHERE q.queue_date = CURDATE() AND q.queue_number = 1
LIMIT 1;

-- Prescriptions
INSERT INTO prescriptions (consultation_id, medicine_name, dosage, frequency, duration, instructions)
SELECT c.consultation_id, 'Amoxicillin 500mg', '1 capsule', '3 times daily after food', '7 days', 'Complete full course of antibiotics.'
FROM consultations c LIMIT 1;

INSERT INTO prescriptions (consultation_id, medicine_name, dosage, frequency, duration, instructions)
SELECT c.consultation_id, 'Paracetamol 500mg', '2 tablets', 'Every 6 hours as needed', '3 days', 'Take for relief of fever or mild headache.'
FROM consultations c LIMIT 1;

INSERT INTO prescriptions (consultation_id, medicine_name, dosage, frequency, duration, instructions)
SELECT c.consultation_id, 'Cetirizine 10mg', '1 tablet', 'Once daily at bedtime', '5 days', 'May cause mild drowsiness.'
FROM consultations c LIMIT 1;

-- Queue Events
INSERT INTO queue_events (
    queue_id, previous_status, new_status, changed_by_user_account_id, event_note
)
SELECT q.queue_id, NULL, 'waiting', u.user_account_id, 'Initial check-in'
FROM queues q
INNER JOIN user_accounts u ON u.username = 'admin_demo'
WHERE q.queue_date = CURDATE() AND q.queue_number IN (1, 2, 3);

INSERT INTO queue_events (
    queue_id, previous_status, new_status, changed_by_user_account_id, event_note
)
SELECT q.queue_id, 'waiting', 'called', u.user_account_id, 'Called to consultation room'
FROM queues q
INNER JOIN user_accounts u ON u.username = 'admin_demo'
WHERE q.queue_date = CURDATE() AND q.queue_number IN (1, 2);

INSERT INTO queue_events (
    queue_id, previous_status, new_status, changed_by_user_account_id, event_note
)
SELECT q.queue_id, 'called', 'in_consultation', u.user_account_id, 'Consultation started'
FROM queues q
INNER JOIN user_accounts u ON u.username = 'doctor_demo'
WHERE q.queue_date = CURDATE() AND q.queue_number = 1;

INSERT INTO queue_events (
    queue_id, previous_status, new_status, changed_by_user_account_id, event_note
)
SELECT q.queue_id, 'in_consultation', 'completed', u.user_account_id, 'Consultation and medication prescription completed'
FROM queues q
INNER JOIN user_accounts u ON u.username = 'doctor_demo'
WHERE q.queue_date = CURDATE() AND q.queue_number = 1;
