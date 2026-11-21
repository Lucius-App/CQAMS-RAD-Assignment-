<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/permissions.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$user = requireRole('administrator');
$pageTitle = 'Staff Accounts';
require dirname(__DIR__) . '/includes/header.php';
?>
<section class="page-heading">
    <div>
        <p class="eyebrow">Administration</p>
        <h1>Staff accounts</h1>
        <p>Create and maintain staff access without exposing account credentials.</p>
    </div>
    <button class="button" type="button" id="open-staff-form">Add staff account</button>
</section>
<section class="content-panel">
    <div id="staff-message" class="message" role="status" hidden></div>
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>Staff member</th><th>Username</th><th>Role</th><th>Status</th><th>Last login</th><th>Actions</th></tr></thead>
            <tbody id="staff-results"><tr><td colspan="6" class="empty-state">Loading staff accounts...</td></tr></tbody>
        </table>
    </div>
</section>
<dialog class="modal" id="staff-dialog">
    <form class="modal-card" id="staff-form" method="dialog">
        <div class="modal-heading"><div><p class="eyebrow">Staff account</p><h2 id="staff-dialog-title">Add staff account</h2></div><button class="icon-button" type="button" id="close-staff-form">×</button></div>
        <div id="staff-form-message" class="message" role="alert" hidden></div>
        <input type="hidden" name="user_account_id" id="staff-id">
        <div class="form-grid">
            <label>First name<input name="first_name" id="staff-first-name" maxlength="80" required></label>
            <label>Last name<input name="last_name" id="staff-last-name" maxlength="80" required></label>
            <label>Username<input name="username" id="staff-username" maxlength="80" pattern="[A-Za-z0-9._-]{3,80}" required></label>
            <label>Role<select name="role" id="staff-role" required><option value="doctor">Doctor</option><option value="administrator">Administrator</option></select></label>
            <label>Employee number<input name="employee_number" id="staff-employee-number" maxlength="50"></label>
            <label>Phone<input name="phone" id="staff-phone" maxlength="30" type="tel"></label>
            <label class="form-span-2">Email<input name="email" id="staff-email" maxlength="254" type="email"></label>
            <label class="form-span-2">Password <span id="password-help">(minimum 12 characters)</span><input name="password" id="staff-password" type="password" minlength="12" autocomplete="new-password"></label>
        </div>
        <div class="modal-actions"><button class="button button-secondary" type="button" id="cancel-staff-form">Cancel</button><button class="button" type="submit">Save account</button></div>
    </form>
</dialog>
<script>window.CQAMS_CSRF_TOKEN = <?= json_encode(getCsrfToken(), JSON_THROW_ON_ERROR) ?>;</script>
<script src="/CQMS/assets/js/staff.js"></script>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
