# CQAMS Handover Explanation & Partner Guide

CQAMS (Clinic Queue and Appointment Management System) is written as a traditional PHP clinic operations system using:

- **PHP 8+** (strict types enabled across all endpoints)
- **MySQL / MariaDB** (InnoDB storage engine)
- **PDO** (prepared statements with native emulation disabled)
- **HTML5** & **CSS3** (responsive design with custom CSS variables)
- **Vanilla JavaScript** (ES6 Fetch API, native `<dialog>` modals)
- **Apache** through XAMPP

> **Zero External Dependencies:** No React, Vue, Laravel, Bootstrap, Tailwind, Node.js, npm, Composer, or third-party libraries are required. The entire stack runs self-contained out-of-the-box on XAMPP.

The system has three roles: patient, doctor, and administrator. Patients register themselves; administrators approve patient accounts and appointment requests.

---

## 1. Overall System Structure

The application is structured into role-focused modules and shared services:

```text
CQMS/
├── admin/              Administrator workspace (staff, schedules, reports)
├── appointments/       Appointment booking and management interface
├── doctor/             Doctor workspace (assigned appointments & consultations)
├── patients/           Patient registration and search interface
├── patient/            Patient self-service (booking, history, branches)
├── public/             Public queue waiting-area display (TV screen)
├── queue/              Administrator queue operation interface
├── api/                RESTful JSON backend endpoints
├── assets/
│   ├── css/            Shared responsive stylesheet (app.css)
│   └── js/             Vanilla JS modules for each interface
├── config/             Database connection configuration (database.php)
├── includes/           Shared authentication, permissions, layout & helper functions
├── database/           Database schema (cqams.sql) and sample seeds (seed_demo_data.sql)
└── docs/               Technical and handover documentation
```

The comprehensive technical specification is available in:
[`docs/CQAMS-TECHNICAL-DOCUMENTATION.md`](file:///c:/xampp/htdocs/CQMS/docs/CQAMS-TECHNICAL-DOCUMENTATION.md)

---

## 2. Database Connection

The database connection is managed by:
[`config/database.php`](file:///c:/xampp/htdocs/CQMS/config/database.php)

Default development configuration:

```text
Host:     127.0.0.1
Port:     3306
Database: cqams
Username: root
Password: (empty by default in XAMPP)
```

The connection initializes PDO as follows:

```php
$connection = new PDO($dsn, $username, $password, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
]);
```

Key characteristics:
- **Exceptions:** All SQL errors throw `PDOException`.
- **Associative Arrays:** Rows are fetched as associative arrays by default.
- **True Prepared Statements:** Emulation is turned off, ensuring native database-side parameter binding to prevent SQL injection.
- **Environment Overrides:** Configuration can be customized without touching code using environment variables (`CQAMS_DB_HOST`, `CQAMS_DB_PORT`, `CQAMS_DB_NAME`, `CQAMS_DB_USER`, `CQAMS_DB_PASSWORD`).

---

## 3. Database Design & Tables

The schema is defined in [`database/cqams.sql`](file:///c:/xampp/htdocs/CQMS/database/cqams.sql). All tables use InnoDB with foreign keys and cascade rules.

### Main Entities

1. **`user_accounts`**
   - Stores `username`, `password_hash`, `role` (`administrator`, `doctor`, or `patient`), account approval state, `is_active`, `last_login_at`, and timestamps.
   - Passwords are encrypted using PHP `password_hash()` (bcrypt).
2. **`staff`**
   - Links 1:1 with `user_accounts`.
   - Stores `employee_number`, `first_name`, `last_name`, `phone`, `email`.
3. **`patients`**
   - Stores `registration_number` (unique), `national_id`, `first_name`, `last_name`, `date_of_birth`, `sex`, `phone`, `email`, `address`, `emergency_contact_name`, `emergency_contact_phone`.
4. **`doctors`**
   - Links 1:1 with `staff`.
   - Stores `specialization`, `license_number`.
5. **`doctor_schedules`**
   - Stores recurring availability: `day_of_week` (1–7), `start_time`, `end_time`, `slot_duration_minutes`, `is_active`.
   - Enforces `UNIQUE KEY (doctor_id, day_of_week, start_time, end_time)`.
6. **`appointments`**
   - Links `patient_id`, `doctor_id`, `doctor_schedule_id`.
   - Stores appointment lifecycle `status` separately from `approval_status` (`pending`, `approved`, `rejected`).
   - Pending requests reserve their time slot; rejected and cancelled appointments release it.
   - Uses stored generated columns (`active_doctor_slot` and `active_patient_slot`) with unique indexes to prevent duplicate active doctor or patient slots.
7. **`queues`**
   - Stores daily queue tokens: `clinic_code` (`MAIN`), `queue_date`, `queue_number`, `patient_id`, `appointment_id` (NULL for walk-ins), `doctor_id`, `status` (`waiting`, `called`, `in_consultation`, `completed`, `skipped`, `cancelled`), `checked_in_at`, `called_at`, `completed_at`.
   - Enforces unique daily queue numbering per clinic and date.
8. **`consultations`**
   - Links 1:1 to `queues`.
   - Stores `doctor_id`, `status` (`in_progress`, `completed`, `referred`, `cancelled`), `started_at`, `ended_at`, `notes`, `outcome`.
9. **`queue_events`**
   - Audit trail storing `queue_id`, `previous_status`, `new_status`, `changed_by_user_account_id`, `event_note`, `created_at`.

---

## 4. Authentication Architecture

Handled by:
- [`includes/auth.php`](file:///c:/xampp/htdocs/CQMS/includes/auth.php)
- [`login.php`](file:///c:/xampp/htdocs/CQMS/login.php)
- [`logout.php`](file:///c:/xampp/htdocs/CQMS/logout.php)

### Login Flow:
1. An approved patient, doctor, or administrator enters credentials in `login.php`.
2. Backend queries `user_accounts` where `is_active = 1`.
3. `password_verify()` evaluates the submitted password against the stored bcrypt hash.
4. `session_regenerate_id(true)` creates a fresh session identifier to prevent session fixation attacks.
5. Session cookie is configured with `httponly = true`, `samesite = Lax`, and `path = /`.
6. User identity is stored in `$_SESSION['user']`, and a 32-byte cryptographic CSRF token is generated in `$_SESSION['csrf_token']`.
7. User is routed to their role-specific dashboard:
   - Administrator $\rightarrow$ `/CQMS/admin/`
   - Doctor $\rightarrow$ `/CQMS/doctor/`
   - Patient $\rightarrow$ `/CQMS/patient/`

### Live Session Validation:
Every authenticated request invokes `getAuthenticatedUser()`, which performs a live SQL query to verify:
- The user account exists in the database.
- The account remains `is_active = 1`.
- The username and role have not changed.
If an administrator deactivates an account while that user is logged in, their very next click logs them out immediately.

---

## 5. Authorization & Role-Based Access Control (RBAC)

Enforced server-side in [`includes/permissions.php`](file:///c:/xampp/htdocs/CQMS/includes/permissions.php):

```php
requireRole('administrator');
requireRole('doctor');
requireRole('patient');
```

- **Browser UI is not trusted:** Hiding sidebar items is purely for user navigation. Direct API or page requests verify permissions on the PHP side.
- **Doctor Scoping:** Endpoints in `api/doctor/` map the authenticated user account ID to `doctors.doctor_id` and strictly filter all appointments and queue records by `WHERE doctor_id = :doctor_id`. Doctors cannot inspect or alter another doctor's patients or consultations.
- **CSRF Token Validation:** Every mutating HTTP request (`POST`, `PUT`, `DELETE`) requires the `X-CSRF-Token` header.

Standard HTTP responses:
- `401 Unauthorized` — No active session.
- `403 Forbidden` — Active session, but insufficient role permissions.
- `405 Method Not Allowed` — Incorrect HTTP verb used.
- `409 Conflict` — Duplicate slot, already checked-in patient, or invalid state transition.
- `419 Authentication Timeout` — Expired or invalid CSRF token.
- `422 Unprocessable Entity` — Missing or invalid input payload.

---

## 6. Workspaces & Modules

### A. Administrator Workspace (`/CQMS/admin/`)
- **Dashboard (`index.php`):** Displays live counters for today's appointments, queue entries, active queue, and active doctors.
- **Patient Approvals (`approvals.php`):** Approve or reject patient self-registration before login is enabled.
- **Appointment Requests (`appointment-approvals.php`):** Approve or reject patient requests. Rejection releases the reserved slot.
- **Staff Accounts (`staff.php`):** Add, update, enable, or disable doctor and administrator accounts. Passwords require a minimum of 12 characters.
- **Doctors & Schedules (`doctors.php`):** Link staff accounts to doctor specializations and configure daily working hours and slot durations (5–480 min). Rejects overlapping active schedules.
- **Reports (`reports.php`):** Query activity by date range (`from` and `to`), displaying appointment volumes, completed/cancelled counts, and queue outcomes.

### B. Doctor Workspace (`/CQMS/doctor/`)
- **Overview (`index.php`):** High-level metrics showing today's assigned appointments, queue patients, and completed consultations.
- **My Appointments (`appointments.php`):** Focused view of today's booked appointments assigned to the logged-in doctor.
- **Consultation Workspace (`consultations.php`):** Shows today's assigned queue. Doctor clicks **Start consultation** on a called patient, opening a consultation record (`in_progress`). When finished, clicks **Complete consultation** to enter clinical outcome and consultation notes.

### C. Patient Workspace (`/CQMS/patient/`)
- Patients register at `/CQMS/register.php`. Their accounts remain inactive until an administrator approves them.
- Approved patients can request a clinic, doctor, date, time, and reason category in `book.php`.
- Requests remain pending until approved. Pending slots are reserved to prevent block-booking; rejected requests release their slots.
- Patients can review approval status, cancel appointments, browse clinic branches, and view their own medical history.
- Patient-initiated rescheduling is not implemented yet; selecting the `reschedule` reason does not change an existing appointment.

### D. Public Queue Display (`/CQMS/public/queue.php`)
- Designed for waiting-room TV displays.
- Requires no login. Accepts only `GET` requests.
- Displays clinic name, live digital clock, "Now Serving" number with assigned doctor label, recently called queue numbers, and waiting patient count.
- **Privacy Safe:** Excludes patient names, identification numbers, contact information, and medical notes.
- Auto-refreshes every 10 seconds via native Fetch API.

---

## 7. Verified Demo Accounts

| Role | Username | Password |
|---|---|---|
| **Administrator** | `admin_demo` | `CQAMS-Admin-2026!` |
| **Doctor** | `doctor_demo` | `CQAMS-Doctor-2026!` |
| **Second Doctor** | `doctor2_demo` | `CQAMS-Doctor-2026!` |
| **North Branch Doctor** | `doctor_north_demo` | `CQAMS-Doctor-2026!` |
| **South Branch Doctor** | `doctor_south_demo` | `CQAMS-Doctor-2026!` |
| **Patient** | `patient_demo` | `CQAMS-Patient-2026!` |

*(All password hashes verified with PHP `password_verify()` against `database/cqams.sql`).*

---

## 8. Step-by-Step Setup on XAMPP

1. **Install XAMPP** with Apache, PHP 8+, and MySQL/MariaDB.
2. **Place Project Directory:** Ensure the folder is located at:
   ```text
   C:\xampp\htdocs\CQMS
   ```
3. **Start Services:** Launch Apache and MySQL from the XAMPP Control Panel.
4. **Database Import:**
   - Navigate to `http://localhost/phpmyadmin`.
   - Click the **Import** tab.
   - Choose `C:\xampp\htdocs\CQMS\database\cqams.sql` and execute.
   - *(Optional for additional demo data: also import `database/seed_demo_data.sql`)*.
5. **Verify Connection:** Open `http://localhost/CQMS/test/pdo_connection.php` in your browser.
6. **Launch System:** Navigate to `http://localhost/CQMS/login.php`.

---

## 9. Recommended Live Demonstration Order

When demonstrating the system to evaluators or partners, follow this chronological clinic workflow:

1. **Show CRUD and Search/Filter:**
   - In Clinic Branches, create a temporary unused branch, view it in the table, edit its details, and delete it with the confirmation dialog.
   - In Appointments, show the appointment list, search/filter records, create or select a demo appointment, reschedule it, then delete only if it has no queue history.
   - Explain that appointments linked to queue history must be cancelled, not permanently deleted, to preserve service records.
2. **Admin Setup:**
   - Log in as `admin_demo`.
   - Show Staff Accounts (`admin/staff.php`) and Doctor Schedules (`admin/doctors.php`).
   - Review pending patient accounts and approve one.
3. **Patient Booking:**
   - Register a patient at `/CQMS/register.php` and wait for administrator approval.
   - Log in as the approved patient, submit an appointment request, and note that its status is pending.
   - Return to the administrator account and approve the appointment request.
4. **Check-in and Queue:**
   - Go to Check-in & Queue (`queue/index.php`) and select the approved appointment.
   - Note the newly generated queue number (e.g. `#4`).
   - On the patient dashboard, verify the ticket, active queue positions ahead, and currently called number refresh automatically.
5. **Public Display:**
   - Open `public/queue.php` in a separate browser tab or window.
   - Observe the waiting patient count increase.
6. **Queue Calling:**
   - In the Queue page (`queue/index.php`), click **Call** on the patient's queue number.
   - Look at the Public Display tab: the number immediately appears under **Now Serving** with the doctor's name.
7. **Doctor Consultation:**
   - Log in as `doctor_demo` (e.g. in an incognito window or separate browser).
   - Navigate to Consultation Workspace (`doctor/consultations.php`).
   - Click **Start consultation** for the called patient.
   - Click **Complete consultation**, type outcome notes (e.g. *"Prescribed standard treatment"*), and submit.
8. **Administrator Operational Reporting:**
   - Log back into `admin_demo`.
   - Navigate to Operational Reports (`admin/reports.php`).
   - Run the report for today and observe the completed queue and updated operational statistics.

Use demo records for the presentation. Do not permanently delete patients or appointments that have queue/consultation history.

---

## 10. Important Technical Notes & Known Edge Cases

1. **URL Case Sensitivity:** Always access via `/CQMS/` in uppercase to match Apache's folder mapping.
2. **Database Import Notice:** `database/cqams.sql` uses `DROP TABLE IF EXISTS` and drops existing tables upon fresh import. Use `database/seed_demo_data.sql` when adding records to an existing database.
   - For an existing schema, back it up and run `database/add_patient_account_approval.sql`, `database/add_appointment_approval.sql`, and `database/remove_receptionist_role.sql` once each. Do not run these migrations against a fresh schema or run them repeatedly.
   - If the existing database lacks the prescriptions table, run `database/add_prescriptions_table.sql`. This migration is safe to repeat and is not needed for a fresh schema.
   - If the existing database predates clinic support, back it up and run `database/add_clinics_table.sql` once. It adds the clinic table and clinic links to existing staff, doctors, appointments, and queues. This migration has been applied to the configured database.
   - If the existing database lacks visit reason categories, back it up and run `database/add_appointment_reason_category.sql` once. This migration has been applied to the configured database.
   - Run `database/seed_demo_data.sql` to add missing demo accounts and branch schedules. It is safe to run again.
   - The receptionist migration deletes old receptionist accounts and staff rows. It stops if one of those staff rows is linked to a doctor.
3. **Schedule Day Mapping:** In the UI and PHP, days of the week are ISO-8601 (`1 = Monday ... 7 = Sunday`). When manually testing schedules, ensure the doctor has an active schedule for the chosen day of the week.
4. **Queue State Integrity:** Always progress patients through the standard workflow (`waiting` $\rightarrow$ `called` $\rightarrow$ `in_consultation` $\rightarrow$ `completed`). Avoid marking a queue complete if a doctor is actively in consultation with that patient.
5. **Remaining scope:** Patient rescheduling requests, patient profile photo upload, and PDF export are not implemented. Reports currently support CSV export.
