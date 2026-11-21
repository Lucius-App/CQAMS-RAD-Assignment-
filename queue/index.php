<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/permissions.php';
requireRole('administrator');
$pageTitle = 'Queue Management';
require dirname(__DIR__) . '/includes/header.php';
?>
<section class="page-heading">
    <div><p class="eyebrow">Administrator workspace</p><h1>Queue management</h1><p>Check in scheduled and walk-in patients, then manage today’s queue.</p></div>
    <button class="button" type="button" id="open-checkin">Check in patient</button>
</section>
<section class="content-panel">
    <div class="panel-heading"><div><h2>Today’s queue</h2><p class="panel-help">Queue numbers are generated securely on the server.</p></div><button class="button button-secondary" type="button" id="refresh-queue">Refresh</button></div>
    <div id="queue-message" class="message" role="status" hidden></div>
    <div class="table-wrap"><table class="data-table"><thead><tr><th>Number</th><th>Patient</th><th>Doctor</th><th>Type</th><th>Status</th><th>Check-in</th><th>Actions</th></tr></thead><tbody id="queue-results"><tr><td colspan="7" class="empty-state">Loading queue...</td></tr></tbody></table></div>
</section>
<dialog class="modal" id="checkin-dialog">
    <form class="modal-card" id="checkin-form" method="dialog">
        <div class="modal-heading"><div><p class="eyebrow">Patient check-in</p><h2>Check in patient</h2></div><button class="icon-button" type="button" id="close-checkin">×</button></div>
        <div id="checkin-message" class="message" role="alert" hidden></div>
        <label>Appointment search<input id="appointment-search" type="search" placeholder="Reference or patient name"></label>
        <div id="appointment-results" class="selection-list"></div>
        <p class="form-divider">Or identify a walk-in patient</p>
        <label>Walk-in patient<select id="walkin-patient"><option value="">Select patient</option></select></label>
        <label>Assigned doctor<select id="walkin-doctor"><option value="">Select doctor</option></select></label>
        <div class="modal-actions"><button class="button button-secondary" type="button" id="cancel-checkin">Cancel</button><button class="button" type="submit">Check in</button></div>
    </form>
</dialog>
<script>
window.CQAMS_CSRF_TOKEN = <?= json_encode(getCsrfToken(), JSON_THROW_ON_ERROR) ?>;
</script>
<script src="/CQMS/assets/js/queue.js"></script>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
