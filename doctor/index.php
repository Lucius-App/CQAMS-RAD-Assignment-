<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/permissions.php';

$user = requireRole('doctor');
$pageTitle = 'Doctor Dashboard';
require dirname(__DIR__) . '/includes/header.php';
?>
<section class="page-heading">
    <div>
        <p class="eyebrow">Doctor workspace</p>
        <h1>Good day, <?= escapeHtml($user['username']) ?></h1>
        <p>Review assigned work and continue consultations from this workspace.</p>
    </div>
</section>
<section class="summary-strip" aria-label="Doctor workspace summary">
    <article class="summary-card"><span class="summary-icon" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><circle cx="12" cy="12" r="8"></circle><path d="M12 7v5l3 2"></path></svg></span><div><strong id="doctor-today-count">0</strong><span>Appointments today</span></div></article>
    <article class="summary-card"><span class="summary-icon" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><path d="M5 6h2M10 6h9M5 12h2M10 12h9M5 18h2M10 18h9"></path></svg></span><div><strong id="doctor-queue-count">0</strong><span>Queue records today</span></div></article>
    <article class="summary-card"><span class="summary-icon" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><path d="m5 12 4 4L19 6"></path></svg></span><div><strong id="doctor-completed-count">0</strong><span>Completed consultations</span></div></article>
</section>
<div id="doctor-message" class="message" role="status" hidden></div>
<section class="content-panel workspace-panel">
    <div class="panel-heading"><div><h2>Continue your work</h2><p class="panel-help">Open a focused workspace instead of managing every task on one screen.</p></div></div>
    <div class="dashboard-cards doctor-links">
        <a class="dashboard-card dashboard-card-link" href="/CQMS/doctor/appointments.php"><span class="card-icon">▣</span><span class="card-content"><span class="card-heading"><h2>My appointments</h2><span class="status-badge status-badge-muted">Open</span></span><p>Review today’s assigned appointments and patient details.</p></span></a>
        <a class="dashboard-card dashboard-card-link" href="/CQMS/doctor/consultations.php"><span class="card-icon">✚</span><span class="card-content"><span class="card-heading"><h2>Consultation workspace</h2><span class="status-badge status-badge-muted">Open</span></span><p>Manage the queue assigned to you and complete consultations.</p></span></a>
    </div>
</section>
<dialog class="modal" id="consultation-dialog">
    <form class="modal-card" id="consultation-form" method="dialog">
        <div class="modal-heading"><div><p class="eyebrow">Consultation</p><h2>Complete consultation</h2></div><button class="icon-button" type="button" id="close-consultation">×</button></div>
        <p id="consultation-patient"></p>
        <label>Outcome<input id="consultation-outcome" maxlength="255"></label>
        <label>Notes<textarea id="consultation-notes" maxlength="10000" rows="5"></textarea></label>
        <div id="consultation-message" class="message" role="alert" hidden></div>
        <div class="modal-actions"><button class="button button-secondary" type="button" id="cancel-consultation">Cancel</button><button class="button" type="submit">Complete consultation</button></div>
    </form>
</dialog>
<script>window.CQAMS_CSRF_TOKEN = <?= json_encode(getCsrfToken(), JSON_THROW_ON_ERROR) ?>;</script>
<script src="/CQMS/assets/js/doctor.js"></script>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
