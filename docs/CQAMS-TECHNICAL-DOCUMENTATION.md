# CQAMS Technical Documentation

## 1. Project Overview

CQAMS (Clinic Queue and Appointment Management System) is a web-based clinic operations system for managing patient accounts, appointment requests, doctor schedules, daily queues, and consultations. The intended workflow is patient self-registration followed by administrator approval of patient accounts and appointment requests.

The system has three roles: patient, doctor, and administrator.

## 2. Problem Statement

Clinics that manage appointments and waiting lines manually may experience duplicate bookings, unclear queue status, slow patient handling, and limited visibility of daily operations. CQAMS provides a central system for registering patients, booking appointments, checking patients into a daily queue, calling queue numbers, and recording consultation completion.

## 3. Project Objectives

- Provide secure patient and staff authentication with role-based access.
- Allow patients to self-register and require administrator approval before account access.
- Manage doctor profiles, specializations, and schedules.
- Prevent duplicate active appointments for the same doctor, patient, date, and time.
- Generate daily queue numbers.
- Allow patients to request appointments and require administrator approval before appointments become active.
- Support administrator check-in and queue operations through the existing queue module.
- Support the doctor consultation workflow.
- Provide live administrator statistics and operational reports.
- Provide a privacy-safe public queue display.

## 4. System Scope

### Included

- Patient registration and administrator approval of patient accounts.
- Login and logout for approved patients and staff.
- Administrator, patient, and doctor roles.
- Patient profile, clinic branch, and medical history views.
- Patient appointment requests, administrator approval/rejection, appointment viewing, and cancellation.
- Doctor schedules and availability.
- Appointment check-in and walk-in queue records through the existing queue module.
- Queue calling, recall, skip, return, service start, completion, and cancellation.
- Doctor consultation start and completion.
- Staff accounts and doctor profile management.
- Administrator dashboard statistics and reports.
- Public read-only queue display.

### Not included

- Billing and payment processing.
- Pharmacy management.
- Insurance claims.
- Full electronic medical record management.
- AI diagnosis.
- Notifications and external messaging integrations.

## 5. User Roles and Permissions

### Administrator

- View the administrator dashboard.
- Approve or reject patient account registrations and appointment requests.
- Create, update, activate, and deactivate staff accounts.
- Assign staff roles.
- Create and manage doctor profiles.
- Manage doctor specializations and schedules.
- View appointment and queue statistics.
- View operational reports.
- Open the public queue display.

### Doctor

- View only appointments and queue records assigned to the authenticated doctor.
- Start assigned consultations.
- Complete consultations with notes and an outcome.

### Patient

- Register an account; an administrator must approve it before login is enabled.
- Select a clinic, doctor, date, time, and reason category to request an appointment.
- View appointment approval status, cancel an appointment, and view personal medical history.
- After check-in, view their queue ticket, active tickets ahead, and currently called numbers; the private status refreshes every 10 seconds.
- Cannot access another patient's records or administrator functions.

### Public visitor

- View the public queue display without logging in.
- See queue numbers, doctor labels, recent queue activity, waiting count, clinic name, and update time.
- Cannot modify queue records.

All protected page and API permissions are enforced on the server. Frontend navigation is not treated as an authorization control.

## 6. Functional Requirements

1. Staff must be able to authenticate using a username and password.
2. Disabled staff accounts must not authenticate.
3. Staff must only access features permitted by their current role.
4. Patients must be able to register, and the system must keep their accounts inactive until administrator approval.
5. The system must generate unique patient registration numbers.
6. Patients must be able to request appointments using active clinic branches, doctors, and schedules.
7. Patient appointment requests must remain pending until an administrator approves or rejects them.
8. Pending requests must reserve selected time slots; rejected or cancelled requests must release those slots.
9. The system must prevent duplicate active doctor and patient appointment slots.
10. Administrators must be able to configure doctor availability.
11. The system must generate a queue number for a checked-in patient with an approved appointment.
12. Queue transitions must follow valid workflow states.
13. Doctors must only access their assigned approved appointments, queue, and consultation records.
14. Completing a consultation must complete its queue record.
15. Administrators must receive database-backed statistics based on approved appointments.
16. The public queue endpoint must return only safe queue information.
17. Mutating requests must use CSRF protection where required.

## 7. Non-Functional Requirements

- **Security:** Passwords are hashed; PDO prepared statements are used; sessions, CSRF tokens, server-side authorization, and output escaping are implemented.
- **Privacy:** Public queue responses do not include patient names, identification numbers, phone numbers, addresses, or medical information.
- **Maintainability:** Common authentication, validation, response, layout, and database functionality is placed in reusable PHP files.
- **Usability:** The application uses responsive HTML and CSS layouts and provides Fetch API interactions for operational screens.
- **Compatibility:** The application targets PHP 8+, MySQL/MariaDB through PDO, and Apache supplied by XAMPP.
- **Reliability:** Transactions are used for operations such as account creation, queue check-in, queue transitions, and consultation updates.

## 8. System Architecture

CQAMS uses a traditional server-rendered PHP architecture:

1. HTML and PHP pages provide the application screens.
2. CSS in `assets/css/app.css` provides shared responsive styling.
3. Vanilla JavaScript in `assets/js/` handles form actions, tables, filtering, and Fetch API requests.
4. PHP API endpoints validate input, verify sessions and permissions, execute business logic, and return JSON.
5. PDO connects PHP to MySQL/MariaDB.
6. MySQL/MariaDB stores users, staff, patients, schedules, appointments, queues, consultations, and queue events.

Important shared components include:

- `config/database.php` — PDO connection configuration.
- `includes/auth.php` — secure session handling, login helpers, authentication, and logout.
- `includes/permissions.php` — server-side authentication and role checks.
- `includes/functions.php` — validation-related shared helpers and output escaping.
- `api/common/response.php` — consistent JSON responses.
- `api/common/validation.php` — shared request validation.
- `includes/header.php`, `includes/sidebar.php`, and `includes/footer.php` — shared page layout.

## 9. Technology Stack

- HTML5
- CSS3
- Vanilla JavaScript
- Native JavaScript Fetch API
- PHP 8+
- PDO
- MySQL or compatible MariaDB
- Apache and MySQL/MariaDB through XAMPP

No frontend framework, PHP framework, Composer package, Node.js application framework, Bootstrap, or Tailwind is required.

## 10. Database Design and ERD Explanation

The database schema is defined in [database/cqams.sql](../database/cqams.sql).

### Main entities

- `user_accounts` stores usernames, password hashes, roles, active status, and login timestamps.
- `staff` stores staff identity and contact information linked to a user account.
- `patients` stores patient registration and contact information.
- `doctors` stores doctor-specific information linked to a staff record.
- `doctor_schedules` stores recurring day-of-week availability and slot duration.
- `appointments` links a patient to a doctor and optional schedule, and stores appointment lifecycle and administrator approval statuses separately.
- `queues` stores the daily queue number and current queue status.
- `consultations` stores doctor consultation status, notes, outcome, and timestamps.
- `queue_events` records queue status transitions and the user who performed each transition.

### Main relationships

- Staff accounts link to staff records; patient accounts link to patient records.
- A staff record may have one doctor profile.
- One doctor may have many schedules.
- One patient may have many appointments.
- One doctor may have many appointments.
- An appointment may create one queue record when checked in.
- One queue record may have consultation records as defined by the schema.
- One queue record may have many queue events.

Foreign keys use InnoDB constraints and preserve historical records. Staff, doctors, patients, appointments, and queue records are not freely deleted where deletion could damage historical information.

The appointment table uses generated active-slot values and unique indexes to prevent duplicate doctor and patient slots. Pending requests reserve slots. Rejected and cancelled requests release slots. Queue numbers are generated within a transaction for the clinic and date.

## 11. Module Descriptions

### Authentication

- [login.php](../login.php) handles staff login.
- [logout.php](../logout.php) destroys the session.
- `includes/auth.php` uses `password_verify()`, regenerates the session ID after login, and revalidates the current account and role.

### Administrator workspace

- [admin/index.php](../admin/index.php) displays live administrator metrics.
- [admin/staff.php](../admin/staff.php) manages staff accounts and roles.
- [admin/doctors.php](../admin/doctors.php) manages doctor profiles and schedules.
- [admin/approvals.php](../admin/approvals.php) reviews patient account registrations.
- [admin/appointment-approvals.php](../admin/appointment-approvals.php) reviews patient appointment requests.
- [admin/reports.php](../admin/reports.php) displays operational reports.

### Patient management

- [register.php](../register.php) accepts patient self-registration; new accounts require administrator approval.
- Pages under `patient/` support appointment requests, approval status, clinic branches, and medical history.
- [patients/index.php](../patients/index.php) provides patient registration, search, details, and editing.
- Patient APIs are located in `api/patients/`.

### Appointment management

- [appointments/index.php](../appointments/index.php) provides appointment booking and appointment management.
- Appointment APIs are located in `api/appointments/`.

### Queue management

- [queue/index.php](../queue/index.php) provides check-in, queue search, and queue actions.
- Queue APIs are located in `api/queue/`.

### Doctor workspace

- [doctor/index.php](../doctor/index.php) displays doctor-owned appointments, queue records, and consultation actions.
- Doctor APIs are located in `api/doctor/`.

### Public queue display

- [public/queue.php](../public/queue.php) provides the waiting-area display.
- [api/public/queue.php](../api/public/queue.php) is a public GET-only endpoint.
- `assets/js/public-queue.js` refreshes queue data using Fetch API.

## 12. CRUD Demonstration Coverage

The rubric's two-entity CRUD requirement is demonstrated by clinic branches and appointments:

| Entity | Create | Read | Update | Delete |
|---|---|---|---|---|
| Clinic branches | Administrator adds a branch | Branches appear in the clinic list | Administrator edits branch details | Permanent deletion is allowed only when no staff, doctors, appointments, or queue records reference the branch |
| Appointments | A patient requests a booking or an administrator creates one | Patient and administrator appointment lists and detail views | An administrator reschedules an appointment | An administrator may permanently delete an appointment before it has queue history; cancelling preserves appointments that have service history |

Patient profile deletion is intentionally not offered because patient records may be linked to appointments, queues, consultations, and medical history. Appointment cancellation remains available for preserving those records.

## 13. Main User Workflows

### Patient appointment and consultation workflow

1. An administrator creates or enables a doctor account.
2. The administrator creates the doctor profile and schedule.
3. A patient self-registers; the account remains inactive while awaiting administrator review.
4. The administrator approves the patient account, enabling login.
5. The patient selects a clinic, doctor, schedule slot, and reason category and submits an appointment request.
6. The request reserves the selected slot while pending. The administrator approves or rejects it; rejection releases the slot.
7. On the appointment date, the approved appointment is checked in through the existing queue module.
8. The system creates a daily queue record and generates a queue number.
9. The public display shows safe queue information.
10. The assigned doctor views the patient queue record.
11. The doctor starts the consultation.
12. The doctor completes the consultation with optional notes and outcome.
13. The queue and consultation statuses become `completed`.
14. The administrator can view updated statistics and reports; pending and rejected requests are not counted as approved appointments.

### Other queue transitions

The queue module supports calling, recalling, skipping, returning a skipped patient, starting service, completing, and cancelling where the current status permits the transition.

## 14. Security Measures

- Passwords are stored using `password_hash()` and checked with `password_verify()`.
- Login queries only accept active accounts.
- Login regenerates the PHP session ID.
- Sessions use HTTP-only, SameSite cookies and strict session settings.
- Authenticated requests recheck the account's active status and current role.
- Protected pages and APIs enforce roles server-side.
- Mutating authenticated API requests require a CSRF token.
- User input is validated on the server.
- PDO prepared statements are used for queries containing request values.
- Output is escaped before HTML rendering.
- Database operations that must be atomic use transactions.
- Database foreign keys and unique constraints protect data integrity.
- Public queue output excludes private patient information.
- SQL files and the database directory are blocked from direct Apache access through `.htaccess`.
- API errors return general messages for unexpected server failures while details are logged server-side.

## 15. Installation Instructions Using XAMPP

1. Install XAMPP with Apache, PHP, and MySQL/MariaDB.
2. Copy the project folder to:

   `C:\xampp\htdocs\CQMS`

3. Start Apache and MySQL from the XAMPP Control Panel.
4. Confirm PHP is available by opening:

   `http://localhost/CQMS/test/pdo_connection.php`

5. Open the application:

   `http://localhost/CQMS/login.php`

The default database connection expects:

- Host: `127.0.0.1`
- Port: `3306`
- Database: `cqams`
- Username: `root`
- Password: empty by default in the development setup

The connection can also be changed with the environment variables `CQAMS_DB_HOST`, `CQAMS_DB_PORT`, `CQAMS_DB_NAME`, `CQAMS_DB_USER`, and `CQAMS_DB_PASSWORD`.

## 16. Database Import Instructions

The schema file creates the `cqams` database and its tables:

1. Start MySQL in XAMPP.
2. Open `http://localhost/phpmyadmin`.
3. Select the **Import** tab.
4. Choose `C:\xampp\htdocs\CQMS\database\cqams.sql`.
5. Execute the import.
6. Confirm that the `cqams` database and its tables were created.
7. Open `http://localhost/CQMS/login.php`.

The SQL file drops and recreates CQAMS tables. It is intended for a fresh development database and must not be imported over data that needs to be preserved.

For an existing CQAMS database, use [database/seed_demo_data.sql](../database/seed_demo_data.sql). This separate script inserts the additional development records without dropping tables and is safe to run again because it checks for existing demo records.

The schema includes development sample accounts and sample records. It includes `doctor2_demo` for testing multiple doctor schedules. Change or remove demo accounts before deployment outside the university development environment.

The repeatable demo seed also adds test doctors and weekly schedules for the North and South branches. Existing databases that predate visit reason categories need [database/add_appointment_reason_category.sql](../database/add_appointment_reason_category.sql) run once; this migration has been applied to the configured CQAMS database.

For an existing database, back it up first and run [database/add_patient_account_approval.sql](../database/add_patient_account_approval.sql), [database/add_appointment_approval.sql](../database/add_appointment_approval.sql), and [database/remove_receptionist_role.sql](../database/remove_receptionist_role.sql) once each. These migrations have been applied to the configured CQAMS database.

If an existing database is missing the prescriptions table, run [database/add_prescriptions_table.sql](../database/add_prescriptions_table.sql). It uses `CREATE TABLE IF NOT EXISTS` and has been applied to the configured database.

For an existing database that predates clinic support, back it up and run [database/add_clinics_table.sql](../database/add_clinics_table.sql) once. It creates the clinic table and links existing staff, doctors, appointments, and queues to the central clinic. This migration has been applied to the configured CQAMS database.

The receptionist migration deletes receptionist accounts and their staff rows; it stops if any such staff row is linked to a doctor profile.

## 17. Test Cases and Results

The following tests were executed during development and integration testing:

| Test | Result |
|---|---|
| Invalid username/password login | Passed |
| Disabled account login | Passed |
| Session ID regeneration after login | Passed |
| Existing session rejected after account deactivation | Passed |
| Public queue POST mutation attempt | Passed with HTTP 405 |
| PHP syntax validation for project PHP files | Passed |
| Patient registration | Passed |
| Duplicate patient prevention and validation | Passed |
| Appointment creation and duplicate-slot protection | Passed |
| Appointment rescheduling through the administrator UI | Passed; branch and visit category preserved |
| Appointment permanent deletion without queue history | Passed; test appointment deleted and booking slot released |
| Permanent deletion blocked for an appointment with queue history | Passed; HTTP 409, historical appointment preserved |
| Clinic branch create/read/update/delete with a temporary unused branch | Passed; test branch removed afterward |
| Clinic deletion blocked when staff, doctors, appointments, or queues reference it | Passed; HTTP 409 |
| Fresh schema import on isolated MariaDB 10.4.32 | Passed |
| Fresh schema roles and seed accounts | Passed; only administrator, doctor, and patient |
| Approval migration on a minimal pre-change schema | Passed |
| Receptionist removal migration on isolated MariaDB | Passed |
| Existing database migrations and role cleanup | Passed; backup taken first |
| Demo administrator and doctor authentication | Passed |
| Pending slot reservation, rejection release, and approval reservation | Passed |
| Patient booking request, pending status, and administrator approval | Passed in XAMPP using demo accounts; temporary test appointment removed afterward |
| Appointment check-in | Passed |
| Daily queue number generation | Passed |
| Queue call and transition validation | Passed |
| Doctor-scoped queue access | Passed |
| Consultation start and completion | Passed |
| Final queue and consultation status update | Passed |
| Public queue privacy response | Passed |
| Administrator live metrics and report query | Passed |
| Direct SQL dump access | Blocked with HTTP 403 |

The verified workflow covers patient appointment requests, administrator approval, appointment check-in, queue calling, doctor consultation, completion, and administrator reporting. The clinic and appointment CRUD paths were exercised with temporary test records, which were removed afterward. Permanent appointment deletion was blocked for a record with queue history.

No performance benchmark, formal penetration test, or cross-device browser compatibility certification was performed.

## 18. Known Limitations

- Patient-requested appointment rescheduling is not implemented yet; `reschedule` is currently only a reason category.
- Patient profile photo upload is not implemented.
- PDF report export is not implemented; reports currently export as CSV.
- The public display currently uses the configured `MAIN` clinic code and fixed clinic label.
- Notifications are not implemented.
- The application has no billing, pharmacy, insurance, or advanced medical-record module.
- Production-grade deployment configuration, HTTPS certificates, rate limiting, and centralized audit monitoring are outside the current university-project scope.
- The SQL schema is documented as MySQL 8.0+ and was also exercised with a compatible MariaDB development server; deployment should use a compatible tested version.
- The database import script is destructive because it recreates tables.
- The public display depends on the availability of the PHP API and database connection.

## 19. Future Enhancements

- Add patient-requested appointment rescheduling with administrator review.
- Add patient profile photo upload if required.
- Add configurable clinic branding and multiple clinic locations.
- Add email or SMS appointment reminders.
- Add calendar-based schedule exceptions and holidays.
- Add PDF report export only if an approved library or permitted native approach is selected.
- Add automated browser tests for responsive layouts and network failures.
- Add database backup and restore procedures.
- Add configurable login rate limiting and expanded audit reporting.
