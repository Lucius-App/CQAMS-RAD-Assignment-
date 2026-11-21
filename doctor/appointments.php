<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/permissions.php';

$user = requireRole('doctor');
$pageTitle = 'My Appointments';
require dirname(__DIR__) . '/includes/header.php';
?>
<section class="page-heading">
    <div><p class="eyebrow">Doctor workspace</p><h1>My appointments</h1><p>Review appointments assigned to your doctor profile.</p></div>
    <button class="button button-secondary" id="refresh-doctor" type="button">Refresh</button>
</section>
<div id="doctor-message" class="message" role="status" hidden></div>
<section class="content-panel workspace-panel" id="doctor-appointments-panel">
    <div class="panel-heading"><div><h2>Today’s appointments</h2><p class="panel-help">Patient information is limited to your assigned records.</p></div></div>
    <div class="table-wrap"><table class="data-table"><thead><tr><th>Date and time</th><th>Patient</th><th>Status</th><th>Reason</th></tr></thead><tbody id="doctor-appointments"><tr><td colspan="4" class="empty-state">Loading appointments...</td></tr></tbody></table></div>
</section>
<script>window.CQAMS_CSRF_TOKEN = <?= json_encode(getCsrfToken(), JSON_THROW_ON_ERROR) ?>;</script>
<script src="/CQMS/assets/js/doctor.js"></script>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
