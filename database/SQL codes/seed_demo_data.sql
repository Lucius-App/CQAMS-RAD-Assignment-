-- CQAMS development demo data only.
-- Safe to run against an existing cqams database.
-- Does not drop tables or delete existing records.

USE cqams;

-- Ensure demo clinics exist
INSERT IGNORE INTO clinics (clinic_code, name, address, phone, email, photo_url, description)
VALUES
    ('MAIN', 'CQAMS Central Clinic', '123 Healthcare Ave, City Center', '+60 3-8888 1000', 'central@cqams.test', '/CQMS/assets/images/clinic_main.svg', 'Main clinic facility offering multi-specialty outpatient services, acute care, and diagnostic facilities.'),
    ('NORTH', 'CQAMS North Branch', '45 Medical Boulevard, North Suburb', '+60 3-8888 2000', 'north@cqams.test', '/CQMS/assets/images/clinic_north.svg', 'Modern community clinic providing family medicine, paediatrics, and preventative health checks.'),
    ('SOUTH', 'CQAMS South Specialist Centre', '88 Wellness Road, South City', '+60 3-8888 3000', 'south@cqams.test', '/CQMS/assets/images/clinic_south.svg', 'Specialist outpatient care centre focused on chronic disease management and wellness programs.');

-- Ensure demo user accounts exist
INSERT IGNORE INTO user_accounts (username, password_hash, role, approval_status, is_active)
VALUES
    ('admin_demo', '$2y$10$wwyMorFZwPQZNQKJ1oORQOVsEy8mKlztK.P5h0ADP68NeqdZ0k.9S', 'administrator', 'approved', 1),
    ('doctor_demo', '$2y$10$fh7mzGP/N/hCFru6LCy0v.QKEjOlNyHolm.HNErpEk1psq/H6o7ue', 'doctor', 'approved', 1),
    ('doctor2_demo', '$2y$10$fh7mzGP/N/hCFru6LCy0v.QKEjOlNyHolm.HNErpEk1psq/H6o7ue', 'doctor', 'approved', 1),
    ('doctor_north_demo', '$2y$10$fh7mzGP/N/hCFru6LCy0v.QKEjOlNyHolm.HNErpEk1psq/H6o7ue', 'doctor', 'approved', 1),
    ('doctor_south_demo', '$2y$10$fh7mzGP/N/hCFru6LCy0v.QKEjOlNyHolm.HNErpEk1psq/H6o7ue', 'doctor', 'approved', 1),
    ('patient_demo', '$2y$10$OnAgSPLtBuT0WwvF4yItN.ncaXPSUhzqySN2xoCCUigNLHZN63psW', 'patient', 'approved', 1),
    ('pending_patient', '$2y$10$OnAgSPLtBuT0WwvF4yItN.ncaXPSUhzqySN2xoCCUigNLHZN63psW', 'patient', 'pending', 0);

-- Ensure demo staff exist
INSERT IGNORE INTO staff (user_account_id, clinic_id, employee_number, first_name, last_name, phone, email)
SELECT user_account_id, 1, 'EMP-001', 'System', 'Administrator', '0000000000', 'admin@example.test'
FROM user_accounts WHERE username = 'admin_demo';

INSERT IGNORE INTO staff (user_account_id, clinic_id, employee_number, first_name, last_name, phone, email)
SELECT user_account_id, 1, 'EMP-003', 'Doctor', 'Demo', '0000000002', 'doctor@example.test'
FROM user_accounts WHERE username = 'doctor_demo';

INSERT IGNORE INTO staff (user_account_id, clinic_id, employee_number, first_name, last_name, phone, email)
SELECT user_account_id, 2, 'EMP-004', 'Aisha', 'Rahman', '0000000003', 'aisha.doctor@example.test'
FROM user_accounts WHERE username = 'doctor2_demo';

INSERT IGNORE INTO staff (user_account_id, clinic_id, employee_number, first_name, last_name, phone, email)
SELECT user_account_id, 2, 'EMP-005', 'Amira', 'Hassan', '0000000004', 'amira.north@example.test'
FROM user_accounts WHERE username = 'doctor_north_demo';

INSERT IGNORE INTO staff (user_account_id, clinic_id, employee_number, first_name, last_name, phone, email)
SELECT user_account_id, 3, 'EMP-006', 'Daniel', 'Lim', '0000000005', 'daniel.south@example.test'
FROM user_accounts WHERE username = 'doctor_south_demo';

-- Ensure demo doctors exist
INSERT IGNORE INTO doctors (staff_id, clinic_id, specialization, license_number)
SELECT staff_id, 1, 'General Medicine', 'LIC-DEMO-001'
FROM staff WHERE employee_number = 'EMP-003';

INSERT IGNORE INTO doctors (staff_id, clinic_id, specialization, license_number)
SELECT staff_id, 2, 'Paediatrics', 'LIC-DEMO-002'
FROM staff WHERE employee_number = 'EMP-004';

INSERT IGNORE INTO doctors (staff_id, clinic_id, specialization, license_number)
SELECT staff_id, 2, 'Family Medicine', 'LIC-DEMO-003'
FROM staff WHERE employee_number = 'EMP-005';

INSERT IGNORE INTO doctors (staff_id, clinic_id, specialization, license_number)
SELECT staff_id, 3, 'General Practice', 'LIC-DEMO-004'
FROM staff WHERE employee_number = 'EMP-006';

-- Ensure demo patients exist
INSERT IGNORE INTO patients
    (user_account_id, registration_number, national_id, first_name, last_name, date_of_birth, sex, phone, email, address)
VALUES
    ((SELECT user_account_id FROM user_accounts WHERE username = 'patient_demo'),
     'PAT-000001', 'DEMO-ID-001', 'Sample', 'Patient', '2000-01-15', 'undisclosed', '0000000010', 'patient@example.test', 'Demo address 1'),
    (NULL, 'PAT-000002', 'DEMO-ID-002', 'Nurul', 'Hassan', '1995-03-22', 'female', '0000000011', 'nurul@example.test', 'Demo address 2'),
    (NULL, 'PAT-000003', 'DEMO-ID-003', 'Jason', 'Lee', '1988-07-09', 'male', '0000000012', 'jason@example.test', 'Demo address 3'),
    (NULL, 'PAT-000004', 'DEMO-ID-004', 'Mei', 'Wong', '2012-11-18', 'female', '0000000013', 'mei@example.test', 'Demo address 4'),
    ((SELECT user_account_id FROM user_accounts WHERE username = 'pending_patient'),
     'PAT-000005', 'DEMO-ID-005', 'Farid', 'Aziz', '1979-01-30', 'male', '0000000014', 'farid@example.test', 'Demo address 5');

INSERT IGNORE INTO patients
    (user_account_id, registration_number, national_id, first_name, last_name, date_of_birth, sex, phone, email, address)
SELECT user_account_id, 'PAT-DEMO-BOOKING', 'DEMO-BOOKING-001', 'Demo', 'Booking Patient',
       '1990-01-01', 'undisclosed', '0000000020', 'booking.patient@example.test', 'Demo address'
FROM user_accounts
WHERE username = 'patient_demo';

-- Ensure recurring doctor schedules exist
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

INSERT IGNORE INTO doctor_schedules (doctor_id, day_of_week, start_time, end_time, slot_duration_minutes)
SELECT d.doctor_id, schedule_days.day_of_week, '09:00:00', '17:00:00', 30
FROM doctors d
JOIN (
    SELECT 1 AS day_of_week
    UNION ALL SELECT 2
    UNION ALL SELECT 3
    UNION ALL SELECT 4
    UNION ALL SELECT 5
    UNION ALL SELECT 6
    UNION ALL SELECT 7
) AS schedule_days
WHERE d.license_number IN ('LIC-DEMO-003', 'LIC-DEMO-004');
