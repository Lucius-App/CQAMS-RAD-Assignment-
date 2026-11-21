<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Public Queue Display | CQAMS</title>
    <link rel="stylesheet" href="/CQMS/assets/css/app.css">
</head>
<body class="public-queue-page">
<main class="public-queue-shell">
    <header class="public-queue-header">
        <div>
            <p class="eyebrow">Clinic queue</p>
            <h1 id="public-clinic-name">CQAMS Clinic</h1>
        </div>
        <div class="public-queue-clock" id="public-queue-clock" aria-label="Current time"></div>
    </header>
    <div id="public-queue-message" class="message message-error" role="alert" hidden></div>
    <section class="public-current-card" aria-labelledby="current-queue-heading">
        <p class="eyebrow" id="current-queue-heading">Now serving</p>
        <div class="public-current-number" id="public-current-number">--</div>
        <p class="public-current-doctor" id="public-current-doctor">Please wait for the next call</p>
    </section>
    <section class="public-queue-grid">
        <article class="public-panel">
            <p class="eyebrow">Recently called</p>
            <div class="public-recent-list" id="public-recent-list">
                <p class="public-empty">No recent calls.</p>
            </div>
        </article>
        <article class="public-panel public-waiting-panel">
            <p class="eyebrow">Patients waiting</p>
            <div class="public-waiting-count" id="public-waiting-count">0</div>
            <p>Please remain in the waiting area.</p>
        </article>
    </section>
    <footer class="public-queue-footer">
        <span>Keep your queue number available.</span>
        <span id="public-queue-updated">Updating...</span>
    </footer>
</main>
<script src="/CQMS/assets/js/public-queue.js"></script>
</body>
</html>
