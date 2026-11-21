<?php
declare(strict_types=1);

$dashboardCards = $dashboardCards ?? [];
?>
<section class="dashboard-cards" aria-label="Workspace modules">
    <?php foreach ($dashboardCards as $card): ?>
        <article class="dashboard-card">
            <div class="card-icon" aria-hidden="true"><?= escapeHtml($card['icon']) ?></div>
            <div class="card-content">
                <div class="card-heading">
                    <h2><?= escapeHtml($card['title']) ?></h2>
                    <span class="status-badge status-badge-muted"><?= escapeHtml($card['status']) ?></span>
                </div>
                <p><?= escapeHtml($card['description']) ?></p>
                <?php if (!empty($card['href'])): ?>
                    <a class="button button-small" href="<?= escapeHtml($card['href']) ?>">Open module</a>
                <?php endif; ?>
            </div>
        </article>
    <?php endforeach; ?>
</section>
