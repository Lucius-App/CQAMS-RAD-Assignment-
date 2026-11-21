<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/permissions.php';
require_once dirname(__DIR__) . '/includes/functions.php';

requireRole('administrator');
$pageTitle = 'Patients';
require dirname(__DIR__) . '/includes/header.php';
?>
<section class="page-heading">
    <div>
        <p class="eyebrow">Patient management</p>
        <h1>Patients</h1>
        <p>Register new patients, find existing records, and review their history.</p>
    </div>
    <button class="button" type="button" id="open-patient-form">Register patient</button>
</section>

<section class="content-panel" aria-labelledby="patient-search-heading">
    <div class="panel-heading">
        <div>
            <h2 id="patient-search-heading">Find a patient</h2>
            <p class="panel-help">Search by reference number, national ID, name, or phone.</p>
        </div>
        <span class="status-badge status-badge-muted" id="patient-count">0 records</span>
    </div>
    <form class="search-bar" id="patient-search-form">
        <label class="sr-only" for="patient-search">Search patients</label>
        <input id="patient-search" name="q" type="search" maxlength="80" placeholder="Search patient records">
        <button class="button" type="submit">Search</button>
    </form>
    <div id="patient-message" class="message" role="status" hidden></div>
    <div class="table-wrap">
        <table class="data-table">
            <caption class="sr-only">Patient records</caption>
            <thead>
            <tr>
                <th scope="col">Reference</th>
                <th scope="col">Patient</th>
                <th scope="col">Date of birth</th>
                <th scope="col">Phone</th>
                <th scope="col">Registered</th>
                <th scope="col"><span class="sr-only">Actions</span></th>
            </tr>
            </thead>
            <tbody id="patient-results">
            <tr><td colspan="6" class="empty-state">Search for a patient to begin.</td></tr>
            </tbody>
        </table>
    </div>
</section>

<dialog class="modal" id="patient-dialog">
    <form class="modal-card" id="patient-form" method="dialog">
        <div class="modal-heading">
            <div>
                <p class="eyebrow">Patient record</p>
                <h2 id="patient-dialog-title">Register patient</h2>
            </div>
            <button class="icon-button" type="button" id="close-patient-form" aria-label="Close dialog">×</button>
        </div>
        <div id="form-message" class="message" role="alert" hidden></div>
        <input type="hidden" name="patient_id" id="patient-id">
        <div class="form-grid">
            <label>First name<input name="first_name" id="first-name" maxlength="80" required></label>
            <label>Last name<input name="last_name" id="last-name" maxlength="80" required></label>
            <label>Date of birth<input name="date_of_birth" id="date-of-birth" type="date"></label>
            <label>Gender
                <select name="sex" id="sex">
                    <option value="undisclosed">Prefer not to say</option>
                    <option value="female">Female</option>
                    <option value="male">Male</option>
                    <option value="other">Other</option>
                </select>
            </label>
            <label>Phone<input name="phone" id="phone" type="tel" maxlength="30"></label>
            <label>Email<input name="email" id="email" type="email" maxlength="254"></label>
            <label class="form-span-2">National ID<input name="national_id" id="national-id" maxlength="50"></label>
            <label class="form-span-2">Address<textarea name="address" id="address" maxlength="255" rows="2"></textarea></label>
            <label>Emergency contact name<input name="emergency_contact_name" id="emergency-contact-name" maxlength="160"></label>
            <label>Emergency contact phone<input name="emergency_contact_phone" id="emergency-contact-phone" type="tel" maxlength="30"></label>
        </div>
        <div class="modal-actions">
            <button class="button button-secondary" type="button" id="cancel-patient-form">Cancel</button>
            <button class="button" type="submit" id="save-patient">Save patient</button>
        </div>
    </form>
</dialog>

<dialog class="modal" id="patient-details-dialog">
    <div class="modal-card">
        <div class="modal-heading">
            <div>
                <p class="eyebrow">Patient details</p>
                <h2 id="details-name">Patient</h2>
            </div>
            <button class="icon-button" type="button" id="close-patient-details" aria-label="Close dialog">×</button>
        </div>
        <div id="patient-details" class="details-grid"></div>
        <h3>Appointment history</h3>
        <div class="table-wrap">
            <table class="data-table compact-table">
                <thead><tr><th>Date</th><th>Doctor</th><th>Status</th><th>Reason</th></tr></thead>
                <tbody id="appointment-history"></tbody>
            </table>
        </div>
        <div class="modal-actions">
            <button class="button" type="button" id="edit-patient">Edit patient</button>
        </div>
    </div>
</dialog>
<script>
    window.CQAMS_CSRF_TOKEN = <?= json_encode(getCsrfToken(), JSON_THROW_ON_ERROR) ?>;
</script>
<script src="/CQMS/assets/js/patients.js"></script>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
