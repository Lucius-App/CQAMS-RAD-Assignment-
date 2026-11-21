<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/permissions.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$user = requireRole('administrator');
$pageTitle = 'Patient Approvals';
require dirname(__DIR__) . '/includes/header.php';
?>
<section class="page-heading">
    <div>
        <p class="eyebrow">Patient Administration</p>
        <h1>Patient Account Approvals</h1>
        <p>Review and verify newly registered patient accounts before granting clinic access.</p>
    </div>
    <div style="display: flex; gap: 0.5rem;">
        <button class="button button-secondary" type="button" id="refresh-approvals">Refresh List</button>
    </div>
</section>

<section class="content-panel">
    <div class="panel-heading">
        <div>
            <h2>Registered Patient Applications</h2>
            <p class="panel-help">Approved patients can immediately sign in and book doctor appointments.</p>
        </div>
        <div style="display: flex; gap: 0.5rem; align-items: center;">
            <label for="status-filter" style="margin: 0; font-size: 0.875rem;">Status:</label>
            <select id="status-filter" style="padding: 0.35rem 0.75rem; border-radius: var(--radius-sm); border: 1px solid var(--color-border);">
                <option value="pending" selected>Pending Verification</option>
                <option value="approved">Approved</option>
                <option value="rejected">Rejected</option>
                <option value="all">All Applications</option>
            </select>
        </div>
    </div>

    <div id="approval-message" class="message" role="status" hidden></div>

    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Registration No.</th>
                    <th>Patient Name</th>
                    <th>Username</th>
                    <th>National ID</th>
                    <th>Contact</th>
                    <th>Registered At</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="approvals-results">
                <tr><td colspan="8" class="empty-state">Loading patient applications...</td></tr>
            </tbody>
        </table>
    </div>
</section>

<script>
    window.CQAMS_CSRF_TOKEN = <?= json_encode(getCsrfToken(), JSON_THROW_ON_ERROR) ?>;
</script>
<script src="/CQMS/assets/js/approvals.js"></script>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
