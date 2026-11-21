<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

$sidebarUser = getAuthenticatedUser();
$role = $sidebarUser['role'] ?? '';
$navigation = [
    'administrator' => [
        ['label' => 'Overview', 'href' => '/CQMS/admin/', 'icon' => '▦'],
        ['label' => 'Patient approvals', 'href' => '/CQMS/admin/approvals.php', 'icon' => '✓'],
        ['label' => 'Appointment requests', 'href' => '/CQMS/admin/appointment-approvals.php', 'icon' => '◷'],
        ['label' => 'Clinics & branches', 'href' => '/CQMS/admin/clinics.php', 'icon' => '🏥'],
        ['label' => 'Patients', 'href' => '/CQMS/patients/', 'icon' => '♙'],
        ['label' => 'Appointments', 'href' => '/CQMS/appointments/', 'icon' => '▣'],
        ['label' => 'Check-in and queue', 'href' => '/CQMS/queue/', 'icon' => '☷'],
        ['label' => 'Staff accounts', 'href' => '/CQMS/admin/staff.php', 'icon' => '◎'],
        ['label' => 'Doctors and schedules', 'href' => '/CQMS/admin/doctors.php', 'icon' => '◷'],
        ['label' => 'Reports & export', 'href' => '/CQMS/admin/reports.php', 'icon' => '▤'],
    ],
    'doctor' => [
        ['label' => 'Overview', 'href' => '/CQMS/doctor/', 'icon' => '▦'],
        ['label' => 'My appointments', 'href' => '/CQMS/doctor/appointments.php', 'icon' => '▣'],
        ['label' => 'Consultation workspace', 'href' => '/CQMS/doctor/consultations.php', 'icon' => '✚'],
    ],
    'patient' => [
        ['label' => 'Patient dashboard', 'href' => '/CQMS/patient/', 'icon' => '▦'],
        ['label' => 'Book appointment', 'href' => '/CQMS/patient/book.php', 'icon' => '▣'],
        ['label' => 'My appointments', 'href' => '/CQMS/patient/appointments.php', 'icon' => '◷'],
        ['label' => 'Medical history', 'href' => '/CQMS/patient/history.php', 'icon' => '✚'],
        ['label' => 'Clinic branches', 'href' => '/CQMS/patient/clinics.php', 'icon' => '🏥'],
    ],
];
?>
<aside class="sidebar" id="app-sidebar" aria-label="Main navigation">
    <div class="sidebar-heading">
        <span class="eyebrow"><?= escapeHtml(ucfirst($role)) ?> Workspace</span>
        <button class="sidebar-close menu-toggle" type="button" aria-label="Close navigation">×</button>
    </div>
    <nav class="sidebar-nav">
        <?php foreach ($navigation[$role] ?? [] as $item): ?>
            <?php if (!empty($item['disabled'])): ?>
                <span class="nav-link nav-link-disabled" aria-disabled="true">
            <?php else: ?>
                <a class="nav-link" href="<?= escapeHtml($item['href']) ?>">
            <?php endif; ?>
                    <span class="nav-icon" aria-hidden="true"><?= escapeHtml($item['icon']) ?></span>
                    <span><?= escapeHtml($item['label']) ?></span>
                    <?php if (!empty($item['disabled'])): ?>
                        <span class="nav-badge">Soon</span>
                    <?php endif; ?>
            <?php if (!empty($item['disabled'])): ?>
                </span>
            <?php else: ?>
                </a>
            <?php endif; ?>
        <?php endforeach; ?>
    </nav>
    <div class="sidebar-note">
        <span class="status-dot"></span>
        <div>
            <strong>Online session</strong>
            <small>Signed in as <?= escapeHtml($sidebarUser['username'] ?? 'User') ?></small>
        </div>
    </div>
</aside>
