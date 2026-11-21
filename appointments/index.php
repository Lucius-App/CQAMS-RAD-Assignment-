<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/permissions.php';

requireRole('administrator');
$pageTitle = 'Appointments';
require dirname(__DIR__) . '/includes/header.php';
?>
<section class="page-heading">
    <div>
        <p class="eyebrow">Appointment management</p>
        <h1>Appointments</h1>
        <p>Book scheduled visits separately from check-in and queue operations.</p>
    </div>
    <button class="button" type="button" id="open-appointment-form">New appointment</button>
</section>
<section class="content-panel">
    <div class="panel-heading">
        <div><h2>Appointment list</h2><p class="panel-help">Search by patient or doctor and filter by date or status.</p></div>
        <span class="status-badge status-badge-muted" id="appointment-count">0 records</span>
    </div>
    <form class="filter-grid" id="appointment-filter-form">
        <label>Search<input id="appointment-query" type="search" placeholder="Patient or doctor"></label>
        <label>Date<input id="appointment-date-filter" type="date"></label>
        <label>Status<select id="appointment-status-filter"><option value="">All statuses</option><option value="scheduled">Scheduled</option><option value="confirmed">Confirmed</option><option value="cancelled">Cancelled</option><option value="completed">Completed</option><option value="no_show">No show</option></select></label>
        <button class="button" type="submit">Apply filters</button>
    </form>
    <div id="appointment-message" class="message" role="status" hidden></div>
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>Date and time</th><th>Patient</th><th>Doctor</th><th>Status</th><th>Reason</th><th>Actions</th></tr></thead>
            <tbody id="appointment-results"><tr><td colspan="6" class="empty-state">Loading appointments...</td></tr></tbody>
        </table>
    </div>
</section>
<dialog class="modal" id="appointment-dialog">
    <form class="modal-card" id="appointment-form" method="dialog">
        <div class="modal-heading"><div><p class="eyebrow">Appointment</p><h2 id="appointment-dialog-title">New appointment</h2></div><button class="icon-button" type="button" id="close-appointment-form">×</button></div>
        <div id="appointment-form-message" class="message" role="alert" hidden></div>
        <div class="form-grid">
            <label>Patient<select id="appointment-patient" required></select></label>
            <label>Doctor<select id="appointment-doctor" required></select></label>
            <label>Date<input id="appointment-date" type="date" required></label>
            <label>Available time<select id="appointment-time" required><option value="">Select doctor and date first</option></select></label>
            <label class="form-span-2">Reason<textarea id="appointment-reason" maxlength="255" rows="2"></textarea></label>
        </div>
        <div class="modal-actions"><button class="button button-secondary" type="button" id="cancel-appointment-form">Cancel</button><button class="button" type="submit">Save appointment</button></div>
    </form>
</dialog>
<dialog class="modal" id="cancel-dialog">
    <form class="modal-card" id="cancel-form" method="dialog">
        <div class="modal-heading"><div><p class="eyebrow">Appointment action</p><h2>Cancel appointment</h2></div><button class="icon-button" type="button" id="close-cancel-dialog">×</button></div>
        <label>Cancellation reason<textarea id="cancellation-reason" maxlength="255" rows="3"></textarea></label>
        <div class="modal-actions"><button class="button button-secondary" type="button" id="dismiss-cancel">Keep appointment</button><button class="button" type="submit">Cancel appointment</button></div>
    </form>
</dialog>
<dialog class="modal" id="delete-appointment-dialog">
    <div class="modal-card">
        <div class="modal-heading"><div><p class="eyebrow">Permanent action</p><h2>Delete appointment</h2></div><button class="icon-button" type="button" id="close-delete-appointment">×</button></div>
        <p>This permanently removes the selected appointment and frees its time slot. Appointments with queue history cannot be deleted; cancel them instead to preserve service records.</p>
        <div id="delete-appointment-message" class="message" role="alert" hidden></div>
        <div class="modal-actions"><button class="button button-secondary" type="button" id="dismiss-delete-appointment">Keep appointment</button><button class="button button-danger" type="button" id="confirm-delete-appointment">Delete permanently</button></div>
    </div>
</dialog>
<dialog class="modal" id="appointment-details-dialog">
    <div class="modal-card">
        <div class="modal-heading"><div><p class="eyebrow">Appointment details</p><h2 id="appointment-details-title">Appointment</h2></div><button class="icon-button" type="button" id="close-appointment-details">×</button></div>
        <div class="details-grid" id="appointment-details"></div>
        <div class="modal-actions"><button class="button button-secondary" type="button" id="dismiss-appointment-details">Close</button></div>
    </div>
</dialog>
<script>
    window.CQAMS_CSRF_TOKEN = <?= json_encode(getCsrfToken(), JSON_THROW_ON_ERROR) ?>;
</script>
<script src="/CQMS/assets/js/appointments.js?v=<?= filemtime(__DIR__ . '/../assets/js/appointments.js') ?>"></script>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
