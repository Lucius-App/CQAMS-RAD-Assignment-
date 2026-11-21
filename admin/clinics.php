<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/permissions.php';
require_once dirname(__DIR__) . '/includes/functions.php';

requireRole('administrator');
$pageTitle = 'Clinic Branches Management';
require dirname(__DIR__) . '/includes/header.php';
?>
<section class="page-heading">
    <div>
        <p class="eyebrow">Facility Administration</p>
        <h1>Clinic Branches & Facilities</h1>
        <p>Manage physical clinic locations, branch details, and showcase images across the system.</p>
    </div>
    <button class="button" type="button" id="open-clinic-modal">Add New Branch</button>
</section>

<div id="clinic-message" class="message" role="status" hidden></div>

<section class="content-panel">
    <div class="panel-heading">
        <div>
            <h2>Active Clinic Network</h2>
            <p class="panel-help">Patients can select from these facilities when booking outpatient doctor appointments.</p>
        </div>
    </div>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Photo</th>
                    <th>Branch Code</th>
                    <th>Branch Name</th>
                    <th>Address</th>
                    <th>Contact Phone</th>
                    <th>Email</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="clinics-results">
                <tr><td colspan="7" class="empty-state">Loading clinic branches...</td></tr>
            </tbody>
        </table>
    </div>
</section>

<!-- Clinic Modal Dialog (Create & Edit) -->
<dialog class="modal" id="clinic-dialog">
    <form class="modal-card" id="clinic-form" method="dialog">
        <div class="modal-heading">
            <div>
                <p class="eyebrow">Clinic Facility</p>
                <h2 id="clinic-dialog-title">Add Clinic Branch</h2>
            </div>
            <button class="icon-button" type="button" id="close-clinic-modal">×</button>
        </div>
        <div id="clinic-form-message" class="message" role="alert" hidden></div>
        <input type="hidden" id="clinic-id">
        <div class="form-grid">
            <label>Branch Code * (e.g. WEST)
                <input id="clinic-code" maxlength="30" required pattern="[A-Za-z0-9_\-]{2,30}" placeholder="e.g. MAIN, NORTH">
            </label>
            <label>Branch Name *
                <input id="clinic-name" maxlength="120" required placeholder="e.g. CQAMS West Medical Centre">
            </label>
            <label class="form-span-2">Physical Address *
                <input id="clinic-address" maxlength="255" required placeholder="Full street address and city">
            </label>
            <label>Contact Phone Number *
                <input id="clinic-phone" maxlength="30" type="tel" required placeholder="+60 3-8888 1000">
            </label>
            <label>Official Email
                <input id="clinic-email" maxlength="254" type="email" placeholder="branch@cqams.test">
            </label>
            <label class="form-span-2">Photo / Illustration URL
                <input id="clinic-photo" maxlength="255" placeholder="/CQMS/assets/images/clinic_main.svg">
            </label>
            <label class="form-span-2">Description & Facilities
                <textarea id="clinic-desc" rows="3" maxlength="1000" placeholder="Overview of services offered at this branch"></textarea>
            </label>
        </div>
        <div class="modal-actions">
            <button class="button button-secondary" type="button" id="cancel-clinic-modal">Cancel</button>
            <button class="button" type="submit">Save Branch</button>
        </div>
    </form>
</dialog>

<!-- Delete Confirmation Dialog -->
<dialog class="modal" id="delete-clinic-dialog">
    <div class="modal-card">
        <div class="modal-heading">
            <div>
                <p class="eyebrow" style="color: #ef4444;">Danger Zone</p>
                <h2>Confirm Branch Deletion</h2>
            </div>
            <button class="icon-button" type="button" id="close-delete-dialog">×</button>
        </div>
        <p>Are you sure you want to delete clinic branch <strong id="delete-clinic-name"></strong>?</p>
        <p style="font-size: 0.875rem; color: var(--color-muted);">This action is permanent. Branches linked to staff, doctors, appointments, or queue history cannot be deleted.</p>
        <div class="modal-actions">
            <button class="button button-secondary" type="button" id="cancel-delete-modal">Keep Branch</button>
            <button class="button button-danger" type="button" id="confirm-delete-btn">Delete Branch</button>
        </div>
    </div>
</dialog>

<script>
    window.CQAMS_CSRF_TOKEN = <?= json_encode(getCsrfToken(), JSON_THROW_ON_ERROR) ?>;
</script>
<script src="/CQMS/assets/js/clinics.js"></script>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
