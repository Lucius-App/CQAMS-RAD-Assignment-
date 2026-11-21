<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/permissions.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

$user = requireRole('patient');
$pageTitle = 'Patient Portal Dashboard';

$db = getDatabaseConnection();
$patientId = getAuthenticatedPatientId($db, (int) $user['user_account_id']);
$patient = null;
$stats = [
    'upcoming' => 0,
    'completed' => 0,
    'prescriptions' => 0,
];

if ($patientId !== null) {
    $pStmt = $db->prepare('SELECT * FROM patients WHERE patient_id = :id');
    $pStmt->execute(['id' => $patientId]);
    $patient = $pStmt->fetch();

    // Stats
    $today = (new DateTimeImmutable('today'))->format('Y-m-d');
    $upStmt = $db->prepare('SELECT COUNT(*) FROM appointments WHERE patient_id = :id AND appointment_date >= :today AND status IN ("scheduled", "confirmed")');
    $upStmt->execute(['id' => $patientId, 'today' => $today]);
    $stats['upcoming'] = (int) $upStmt->fetchColumn();

    $compStmt = $db->prepare('SELECT COUNT(*) FROM consultations c INNER JOIN queues q ON q.queue_id = c.queue_id WHERE q.patient_id = :id AND c.status = "completed"');
    $compStmt->execute(['id' => $patientId]);
    $stats['completed'] = (int) $compStmt->fetchColumn();

    $rxStmt = $db->prepare('SELECT COUNT(*) FROM prescriptions p INNER JOIN consultations c ON c.consultation_id = p.consultation_id INNER JOIN queues q ON q.queue_id = c.queue_id WHERE q.patient_id = :id');
    $rxStmt->execute(['id' => $patientId]);
    $stats['prescriptions'] = (int) $rxStmt->fetchColumn();

}

require dirname(__DIR__) . '/includes/header.php';
?>
<section class="page-heading">
    <div>
        <p class="eyebrow">Patient Portal</p>
        <h1>Welcome, <?= escapeHtml($patient['first_name'] ?? $user['username']) ?>!</h1>
        <p>Patient Reference: <strong><?= escapeHtml($patient['registration_number'] ?? 'PAT-NEW') ?></strong> &middot; Manage your doctor appointments and view your medical records.</p>
    </div>
    <a href="/CQMS/patient/book.php" class="button" style="text-decoration: none;">+ Book New Appointment</a>
</section>

<section class="content-panel workspace-panel" style="margin-bottom: 1.5rem;" aria-labelledby="patient-queue-heading">
    <div class="panel-heading">
        <div>
            <h2 id="patient-queue-heading">Live Queue Status</h2>
            <p id="patient-queue-message" class="panel-help" role="status" aria-live="polite">Checking today's queue status...</p>
        </div>
        <a href="/CQMS/public/queue.php" target="_blank" class="button button-small button-secondary" style="text-decoration: none;">View Live Queue ↗</a>
    </div>
    <div id="patient-queue-details" hidden>
        <div class="summary-strip">
            <article class="summary-card">
                <div>
                    <strong id="patient-queue-ticket">—</strong>
                    <span>Your ticket</span>
                </div>
            </article>
            <article class="summary-card">
                <div>
                    <strong id="patient-queue-status" class="queue-status-value">—</strong>
                    <span>Current status</span>
                </div>
            </article>
            <article class="summary-card">
                <div>
                    <strong id="patient-queue-ahead">—</strong>
                    <span>Active people ahead</span>
                </div>
            </article>
        </div>
        <p id="patient-queue-now-calling" class="panel-help"></p>
        <p id="patient-queue-assignment" class="panel-help"></p>
    </div>
    <p id="patient-queue-updated" class="panel-help">Updates automatically every 10 seconds.</p>
</section>

<section class="summary-strip" aria-label="Patient workspace summary">
    <article class="summary-card">
        <span class="summary-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" focusable="false">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                <line x1="16" y1="2" x2="16" y2="6"></line>
                <line x1="8" y1="2" x2="8" y2="6"></line>
                <line x1="3" y1="10" x2="21" y2="10"></line>
            </svg>
        </span>
        <div>
            <strong><?= $stats['upcoming'] ?></strong>
            <span>Upcoming appointments</span>
        </div>
    </article>
    <article class="summary-card">
        <span class="summary-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" focusable="false">
                <path d="M6 3v5a4 4 0 0 0 8 0V3"></path>
                <path d="M4 3h4m4 0h4M10 12v2a4 4 0 0 0 8 0v-1"></path>
                <circle cx="18" cy="11" r="2"></circle>
            </svg>
        </span>
        <div>
            <strong><?= $stats['completed'] ?></strong>
            <span>Completed consultations</span>
        </div>
    </article>
    <article class="summary-card">
        <span class="summary-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" focusable="false">
                <path d="m10.5 20.5 10-10a4.95 4.95 0 1 0-7-7l-10 10a4.95 4.95 0 1 0 7 7Z"></path>
                <path d="m8.5 8.5 7 7"></path>
            </svg>
        </span>
        <div>
            <strong><?= $stats['prescriptions'] ?></strong>
            <span>Prescribed medications</span>
        </div>
    </article>
</section>

<section class="content-panel workspace-panel">
    <div class="panel-heading">
        <div>
            <h2>Quick Actions</h2>
            <p class="panel-help">Select an option below to manage your clinic care.</p>
        </div>
    </div>
    <div class="dashboard-cards" style="grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));">
        <a class="dashboard-card dashboard-card-link" href="/CQMS/patient/book.php">
            <span class="card-icon">▣</span>
            <span class="card-content">
                <span class="card-heading">
                    <h2>Book Appointment</h2>
                    <span class="status-badge status-badge-muted">Open</span>
                </span>
                <p>Choose clinic branch, preferred specialist, date, and reason with live availability.</p>
            </span>
        </a>

        <a class="dashboard-card dashboard-card-link" href="/CQMS/patient/appointments.php">
            <span class="card-icon">◷</span>
            <span class="card-content">
                <span class="card-heading">
                    <h2>My Appointments</h2>
                    <span class="status-badge status-badge-muted">Active</span>
                </span>
                <p>Review your scheduled consultations, timings, status, or cancel an upcoming visit.</p>
            </span>
        </a>

        <a class="dashboard-card dashboard-card-link" href="/CQMS/patient/history.php">
            <span class="card-icon">✚</span>
            <span class="card-content">
                <span class="card-heading">
                    <h2>Medical History & Meds</h2>
                    <span class="status-badge status-badge-muted">Records</span>
                </span>
                <p>View your past consultation outcomes, diagnosis notes, and prescribed medications.</p>
            </span>
        </a>

        <a class="dashboard-card dashboard-card-link" href="/CQMS/patient/clinics.php">
            <span class="card-icon">🏥</span>
            <span class="card-content">
                <span class="card-heading">
                    <h2>Clinic Branches</h2>
                    <span class="status-badge status-badge-muted">Locations</span>
                </span>
                <p>Explore facilities, addresses, contact numbers, and maps across our healthcare network.</p>
            </span>
        </a>
    </div>
</section>

<script src="/CQMS/assets/js/patient-queue.js"></script>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
