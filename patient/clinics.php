<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/permissions.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/config/database.php';

requireRole('patient');
$pageTitle = 'Clinic Branches & Locations';

$db = getDatabaseConnection();
$stmt = $db->query(
    'SELECT clinic_id, clinic_code, name, address, phone, email, photo_url, description, is_active
     FROM clinics
     WHERE is_active = 1
     ORDER BY clinic_id ASC'
);
$clinics = $stmt->fetchAll();

require dirname(__DIR__) . '/includes/header.php';
?>
<section class="page-heading">
    <div>
        <p class="eyebrow">CQAMS Network</p>
        <h1>Our Clinic Branches</h1>
        <p>Explore our healthcare branch locations, contact details, and specialized medical facilities.</p>
    </div>
    <a href="/CQMS/patient/book.php" class="button" style="text-decoration: none;">Book an Appointment</a>
</section>

<div class="clinic-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: var(--space-5); margin-top: var(--space-4);">
    <?php foreach ($clinics as $clinic): ?>
        <article class="content-panel clinic-card" style="display: flex; flex-direction: column; overflow: hidden; padding: 0; border: 1px solid var(--color-border); border-radius: var(--radius-lg); background: var(--color-surface); box-shadow: 0 4px 12px var(--color-shadow); transition: transform 0.2s ease, box-shadow 0.2s ease;">
            <div style="width: 100%; height: 180px; overflow: hidden; background: #e0f2fe; display: flex; align-items: center; justify-content: center;">
                <img src="<?= escapeHtml($clinic['photo_url'] ?: '/CQMS/assets/images/clinic_main.svg') ?>" 
                     alt="<?= escapeHtml($clinic['name']) ?>" 
                     style="width: 100%; height: 100%; object-fit: cover;">
            </div>
            
            <div style="padding: var(--space-5); display: flex; flex-direction: column; flex-grow: 1;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.5rem;">
                    <h2 style="font-size: 1.25rem; margin: 0; color: var(--color-ink); font-weight: 700;">
                        <?= escapeHtml($clinic['name']) ?>
                    </h2>
                    <span class="status-badge status-badge-muted" style="font-weight: 700; text-transform: uppercase;">
                        <?= escapeHtml($clinic['clinic_code']) ?>
                    </span>
                </div>
                
                <p style="color: var(--color-muted); font-size: 0.9rem; margin: 0 0 1rem; line-height: 1.5; flex-grow: 1;">
                    <?= escapeHtml($clinic['description'] ?: 'Complete outpatient consultations, preventive care, and queue-managed clinical services.') ?>
                </p>
                
                <div style="display: grid; gap: 0.5rem; font-size: 0.875rem; margin-bottom: 1.5rem; padding: 0.75rem; background: var(--color-soft); border-radius: var(--radius-sm);">
                    <div style="display: flex; align-items: flex-start; gap: 0.5rem;">
                        <span aria-hidden="true" style="color: var(--color-primary);">📍</span>
                        <span style="color: var(--color-ink);"><?= escapeHtml($clinic['address']) ?></span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <span aria-hidden="true" style="color: var(--color-primary);">📞</span>
                        <span style="color: var(--color-ink);"><?= escapeHtml($clinic['phone']) ?></span>
                    </div>
                    <?php if (!empty($clinic['email'])): ?>
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <span aria-hidden="true" style="color: var(--color-primary);">✉️</span>
                            <span style="color: var(--color-ink);"><?= escapeHtml($clinic['email']) ?></span>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div style="display: flex; gap: 0.5rem;">
                    <a href="/CQMS/patient/book.php?clinic_id=<?= (int) $clinic['clinic_id'] ?>" 
                       class="button" 
                       style="flex: 1; text-align: center; text-decoration: none; justify-content: center;">
                        Book at this Branch →
                    </a>
                </div>
            </div>
        </article>
    <?php endforeach; ?>
</div>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
