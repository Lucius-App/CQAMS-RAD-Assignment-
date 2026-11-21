<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/permissions.php';
require_once dirname(__DIR__) . '/includes/functions.php';

requireRole('administrator');
$pageTitle = 'Operational Reports & Export';
$today = (new DateTimeImmutable('today'))->format('Y-m-d');
require dirname(__DIR__) . '/includes/header.php';
?>
<section class="page-heading">
    <div>
        <p class="eyebrow">Administration</p>
        <h1>Operational reports & data export</h1>
        <p>Review approved appointment and queue activity for a selected date range, and export reports to CSV.</p>
    </div>
</section>
<section class="content-panel">
    <form class="filter-grid" id="report-filter-form">
        <label>From Date
            <input id="report-from" type="date" value="<?= escapeHtml($today) ?>" required>
        </label>
        <label>To Date
            <input id="report-to" type="date" value="<?= escapeHtml($today) ?>" required>
        </label>
        <div style="display: flex; gap: 0.5rem; align-items: flex-end;">
            <button class="button" type="submit">Generate Report</button>
            <button class="button button-secondary" type="button" id="export-csv-btn" style="display: flex; align-items: center; gap: 0.35rem;">
                📥 Export CSV
            </button>
        </div>
    </form>
    <div id="report-message" class="message" role="status" hidden></div>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Appointments</th>
                    <th>Completed appointments</th>
                    <th>Cancelled appointments</th>
                    <th>Queue entries</th>
                    <th>Completed queue</th>
                    <th>Skipped queue</th>
                </tr>
            </thead>
            <tbody id="report-results">
                <tr><td colspan="7" class="empty-state">Choose a date range to generate a report.</td></tr>
            </tbody>
        </table>
    </div>
</section>
<script>
    document.getElementById('export-csv-btn').addEventListener('click', function() {
        var from = document.getElementById('report-from').value;
        var to = document.getElementById('report-to').value;
        window.location.href = '/CQMS/api/admin/export_reports.php?from=' + encodeURIComponent(from) + '&to=' + encodeURIComponent(to);
    });
</script>
<script src="/CQMS/assets/js/reports.js"></script>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
