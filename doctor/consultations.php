<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/permissions.php';

$user = requireRole('doctor');
$pageTitle = 'Consultation Workspace';
require dirname(__DIR__) . '/includes/header.php';
?>
<section class="page-heading">
    <div><p class="eyebrow">Doctor workspace</p><h1>Consultation workspace</h1><p>Work through your assigned queue and record consultation outcomes.</p></div>
    <button class="button button-secondary" id="refresh-doctor" type="button">Refresh</button>
</section>
<div id="doctor-message" class="message" role="status" hidden></div>
<section class="content-panel workspace-panel" id="doctor-queue-panel">
    <div class="panel-heading"><div><h2>Today’s assigned queue</h2><p class="panel-help">Only patients assigned to your doctor profile are shown.</p></div></div>
    <div class="table-wrap"><table class="data-table"><thead><tr><th>Queue</th><th>Patient</th><th>Appointment</th><th>Status</th><th>Patient information</th><th>Action</th></tr></thead><tbody id="doctor-queue"><tr><td colspan="6" class="empty-state">Loading assigned queue...</td></tr></tbody></table></div>
</section>
<section class="content-panel workspace-panel">
    <div class="panel-heading"><div><h2>Consultation history</h2><p class="panel-help">Previous records assigned to you.</p></div></div>
    <div class="table-wrap"><table class="data-table"><thead><tr><th>Date</th><th>Queue</th><th>Patient</th><th>Status</th><th>Outcome</th></tr></thead><tbody id="doctor-history"><tr><td colspan="5" class="empty-state">Loading consultation history...</td></tr></tbody></table></div>
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
