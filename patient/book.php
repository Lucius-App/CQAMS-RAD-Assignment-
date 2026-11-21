<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/permissions.php';
require_once dirname(__DIR__) . '/includes/functions.php';

requireRole('patient');
$pageTitle = 'Book Doctor Appointment';

$selectedClinicId = filter_var($_GET['clinic_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: null;

require dirname(__DIR__) . '/includes/header.php';
?>
<section class="page-heading">
    <div>
        <p class="eyebrow">Patient Booking</p>
        <h1>Schedule a Doctor Appointment</h1>
        <p>Select your preferred clinic branch, specialist physician, and suitable consultation time slot.</p>
    </div>
    <a href="/CQMS/patient/appointments.php" class="button button-secondary" style="text-decoration: none;">View My Appointments</a>
</section>

<div id="booking-message" class="message" role="status" hidden></div>

<section class="content-panel" style="max-width: 800px; margin: 0 auto;">
    <div class="panel-heading">
        <div>
            <h2>Appointment Details</h2>
            <p class="panel-help">Real-time availability is verified automatically upon slot selection.</p>
        </div>
    </div>

    <!-- Branch preview card -->
    <div id="branch-card" style="display: flex; gap: 1rem; align-items: center; background: #f8fafc; border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: 1rem; margin-bottom: 1.5rem;">
        <img id="branch-img" src="/CQMS/assets/images/clinic_main.svg" alt="" style="width: 100px; height: 65px; object-fit: cover; border-radius: 6px; border: 1px solid #cbd5e1;">
        <div>
            <strong id="branch-name" style="font-size: 1.05rem; color: var(--color-ink);">Central HQ Clinic</strong>
            <div id="branch-addr" style="font-size: 0.85rem; color: var(--color-muted);">123 Healthcare Ave, City Center</div>
            <div id="branch-phone" style="font-size: 0.85rem; color: var(--color-primary); font-weight: 500;">Tel: +60 3-8888 1000</div>
        </div>
    </div>

    <form id="patient-booking-form">
        <div class="form-grid">
            <label class="form-span-2">Clinic Branch *
                <select id="book-clinic" required>
                    <option value="">Loading clinic branches...</option>
                </select>
            </label>

            <label>Attending Doctor *
                <select id="book-doctor" required>
                    <option value="">Select clinic branch first</option>
                </select>
            </label>

            <label>Appointment Date *
                <input id="book-date" type="date" required min="<?= (new DateTimeImmutable('tomorrow'))->format('Y-m-d') ?>">
            </label>

            <label class="form-span-2">Available Consultation Time *
                <select id="book-time" required>
                    <option value="">Select doctor and date to load open slots</option>
                </select>
            </label>

            <label class="form-span-2">Reason for Visit * (Required by Lecturer)
                <select id="book-reason-category" required>
                    <option value="routine" selected>Routine Consultation / General Check-up</option>
                    <option value="followup">Normal Follow-up Consultation</option>
                    <option value="emergency">Emergency / Acute Symptoms to Seek Doctor</option>
                    <option value="refill">Medication Refill / Prescription Review</option>
                    <option value="reschedule">Change Appointment Time / Rescheduled Visit</option>
                    <option value="other">Other Medical Concern</option>
                </select>
            </label>

            <label class="form-span-2">Symptoms or Additional Details (Optional)
                <textarea id="book-reason-notes" rows="3" maxlength="255" placeholder="Briefly describe any specific symptoms or health concerns..."></textarea>
            </label>
        </div>

        <div style="margin-top: 2rem; display: flex; justify-content: flex-end; gap: 1rem;">
            <a href="/CQMS/patient/" class="button button-secondary" style="text-decoration: none;">Cancel</a>
            <button class="button" type="submit" id="submit-booking-btn" style="min-width: 180px;">Request Appointment →</button>
        </div>
    </form>
</section>

<!-- Duplicate / Conflict Warning Dialog (Required by Lecturer) -->
<dialog class="modal" id="conflict-dialog">
    <div class="modal-card" style="max-width: 480px; text-align: center;">
        <div style="font-size: 3rem; margin-bottom: 0.5rem;" aria-hidden="true">⚠️</div>
        <h2 style="margin: 0 0 0.5rem; color: #b91c1c;">Slot Unavailable</h2>
        <p id="conflict-message" style="font-size: 1.05rem; color: var(--color-ink); margin: 0 0 1rem; line-height: 1.5;">
            This appointment slot is already booked. Please choose another time.
        </p>
        <p style="font-size: 0.875rem; color: var(--color-muted); margin: 0 0 1.5rem;">
            Our anti-double-booking policy ensures doctors are not assigned overlapping appointments.
        </p>
        <button class="button" type="button" id="close-conflict-dialog" style="width: 100%;">Choose Another Time</button>
    </div>
</dialog>

<script>
    window.CQAMS_CSRF_TOKEN = <?= json_encode(getCsrfToken(), JSON_THROW_ON_ERROR) ?>;
    window.CQAMS_PRESELECTED_CLINIC = <?= json_encode($selectedClinicId, JSON_THROW_ON_ERROR) ?>;
</script>
<script src="/CQMS/assets/js/patient-book.js"></script>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
