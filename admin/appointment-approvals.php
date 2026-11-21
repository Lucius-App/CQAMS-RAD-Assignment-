<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/permissions.php';

requireRole('administrator');
$pageTitle = 'Appointment Approvals';
require dirname(__DIR__) . '/includes/header.php';
?>
<section class="page-heading">
    <div>
        <p class="eyebrow">Administration</p>
        <h1>Appointment requests</h1>
        <p>Review patient booking requests. Pending requests temporarily reserve their selected time slot.</p>
    </div>
    <button class="button button-secondary" type="button" id="refresh-appointment-approvals">Refresh</button>
</section>

<section class="content-panel">
    <div class="panel-heading">
        <div>
            <h2>Booking requests</h2>
            <p class="panel-help">Rejecting a request releases its time slot. Approved requests become active appointments.</p>
        </div>
        <label class="approval-filter">Show
            <select id="appointment-approval-filter">
                <option value="pending">Pending</option>
                <option value="approved">Approved</option>
                <option value="rejected">Rejected</option>
                <option value="all">All requests</option>
            </select>
        </label>
    </div>
    <div id="appointment-approval-message" class="message" role="status" hidden></div>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Patient</th>
                    <th>Clinic / Doctor</th>
                    <th>Date & time</th>
                    <th>Reason</th>
                    <th>Status</th>
                    <th>Decision</th>
                </tr>
            </thead>
            <tbody id="appointment-approval-results">
                <tr><td colspan="6" class="empty-state">Loading appointment requests...</td></tr>
            </tbody>
        </table>
    </div>
</section>

<script>
window.CQAMS_CSRF_TOKEN = <?= json_encode(getCsrfToken(), JSON_THROW_ON_ERROR) ?>;
</script>
<script src="/CQMS/assets/js/appointment-approvals.js"></script>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
