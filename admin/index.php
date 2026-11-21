<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/permissions.php';

$user = requireRole('administrator');
$pageTitle = 'Administrator Dashboard';
require dirname(__DIR__) . '/includes/header.php';
?>
<section class="page-heading">
    <div>
        <p class="eyebrow">Administrator workspace</p>
        <h1>Good day, <?= escapeHtml($user['username']) ?></h1>
        <p>Manage the clinic configuration and staff access from one place.</p>
    </div>
</section>
<?php
$dashboardCards = [
    ['icon' => '◎', 'title' => 'Staff accounts', 'status' => 'Active', 'description' => 'Create, update, disable, and manage staff access.', 'href' => '/CQMS/admin/staff.php'],
    ['icon' => '◷', 'title' => 'Doctors and schedules', 'status' => 'Active', 'description' => 'Maintain doctor profiles and appointment availability.', 'href' => '/CQMS/admin/doctors.php'],
    ['icon' => '▤', 'title' => 'Reports', 'status' => 'Active', 'description' => 'Generate appointment and queue activity reports.', 'href' => '/CQMS/admin/reports.php'],
    ['icon' => '▥', 'title' => 'Public queue display', 'status' => 'Active', 'description' => 'Open the read-only waiting-area display.', 'href' => '/CQMS/public/queue.php'],
    ['icon' => '✓', 'title' => 'Access control', 'status' => 'Active', 'description' => 'Your administrator permissions are enforced on the server.'],
];
require dirname(__DIR__) . '/includes/dashboard-cards.php';
?>
<section class="content-panel workspace-panel">
    <div class="panel-heading"><div><h2>Today’s operational statistics</h2><p class="panel-help">Values are loaded from current appointment and queue records.</p></div></div>
    <div id="admin-statistics-message" class="message" role="status" hidden></div>
    <div class="dashboard-cards" id="admin-statistics"><article class="dashboard-card"><div class="card-content"><p>Loading statistics...</p></div></article></div>
</section>
<script src="/CQMS/assets/js/admin-dashboard.js"></script>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
