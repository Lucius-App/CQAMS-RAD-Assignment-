<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

$pageTitle = $pageTitle ?? 'CQAMS';
$currentUser = getAuthenticatedUser();
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= escapeHtml($pageTitle) ?> | CQAMS</title>
    <link rel="stylesheet" href="/CQMS/assets/css/app.css">
</head>
<body>
<header class="site-header">
    <button class="menu-toggle" type="button" aria-controls="app-sidebar" aria-expanded="false">
        <span class="sr-only">Open navigation</span>
        <svg aria-hidden="true" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round">
            <path d="M4 6h16M4 12h16M4 18h16"/>
        </svg>
    </button>
    <a class="brand" href="/CQMS/">
        <span class="brand-mark" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round">
                <path d="M9 6.5h11M9 12h11M9 17.5h7"/>
                <circle cx="5" cy="6.5" r="1" fill="currentColor" stroke="none"/>
                <circle cx="5" cy="12" r="1" fill="currentColor" stroke="none"/>
                <circle cx="5" cy="17.5" r="1" fill="currentColor" stroke="none"/>
            </svg>
        </span>
        <span>CQAMS</span>
    </a>
    <div class="header-account">
        <?php if ($currentUser !== null): ?>
            <span class="account-name"><?= escapeHtml($currentUser['username']) ?></span>
            <a class="button button-ghost button-small" href="/CQMS/logout.php">Log out</a>
        <?php endif; ?>
    </div>
</header>
<div class="app-shell">
    <?php require __DIR__ . '/sidebar.php'; ?>
    <main class="main-content">
