<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/permissions.php';
require_once dirname(__DIR__) . '/includes/functions.php';

requireRole('patient');
$pageTitle = 'My Appointments';
require dirname(__DIR__) . '/includes/header.php';
?>
<section class="page-heading">
    <div>
        <p class="eyebrow">Patient Portal</p>
        <h1>My Scheduled Appointments</h1>
        <p>View your upcoming consultations, appointment status, and clinic branch locations.</p>
    </div>
    <a href="/CQMS/patient/book.php" class="button" style="text-decoration: none;">+ Book New Appointment</a>
</section>

<div id="patient-appts-message" class="message" role="status" hidden></div>

<section class="content-panel">
    <div class="panel-heading">
        <div>
            <h2>Appointment History & Upcoming Visits</h2>
            <p class="panel-help">Appointments can be cancelled or reviewed with full details.</p>
        </div>
        <span class="status-badge status-badge-muted" id="patient-appts-count">0 visits</span>
    </div>

    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Date & Time</th>
                    <th>Clinic Branch</th>
                    <th>Attending Doctor</th>
                    <th>Reason / Category</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="patient-appts-results">
                <tr><td colspan="6" class="empty-state">Loading your appointments...</td></tr>
            </tbody>
        </table>
    </div>
</section>

<!-- Cancel Confirmation Dialog -->
<dialog class="modal" id="cancel-appt-dialog">
    <form class="modal-card" id="cancel-appt-form" method="dialog">
        <div class="modal-heading">
            <div>
                <p class="eyebrow" style="color: #ef4444;">Cancel Appointment</p>
                <h2>Confirm Cancellation</h2>
            </div>
            <button class="icon-button" type="button" id="close-cancel-modal">×</button>
        </div>
        <p>Are you sure you want to cancel your appointment with <strong id="cancel-doctor-name"></strong>?</p>
        <p style="font-size: 0.875rem; color: var(--color-muted);">This will release your booked time slot for other patients.</p>
        <label>Reason for cancellation (optional):
            <textarea id="cancel-reason-input" rows="3" maxlength="255" placeholder="e.g. Schedule conflict, feeling better, rescheduling..."></textarea>
        </label>
        <div class="modal-actions">
            <button class="button button-secondary" type="button" id="dismiss-cancel-modal">Keep Appointment</button>
            <button class="button button-danger" type="submit">Confirm Cancellation</button>
        </div>
    </form>
</dialog>

<script>
    window.CQAMS_CSRF_TOKEN = <?= json_encode(getCsrfToken(), JSON_THROW_ON_ERROR) ?>;
</script>
<script src="/CQMS/assets/js/patient-appointments.js"></script>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
