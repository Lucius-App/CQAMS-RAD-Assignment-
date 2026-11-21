<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/permissions.php';
require_once dirname(__DIR__) . '/includes/functions.php';

requireRole('patient');
$pageTitle = 'Medical & Consultation History';
require dirname(__DIR__) . '/includes/header.php';
?>
<section class="page-heading">
    <div>
        <p class="eyebrow">Patient Portal</p>
        <h1>Medical & Consultation History</h1>
        <p>Review past clinical consultations, attending doctors, medical diagnoses, and prescribed medications.</p>
    </div>
    <div style="display: flex; gap: 0.5rem;">
        <button class="button button-secondary" type="button" id="refresh-history">Refresh</button>
        <a href="/CQMS/patient/book.php" class="button" style="text-decoration: none;">+ Book Appointment</a>
    </div>
</section>

<!-- Summary statistics -->
<section class="summary-strip" aria-label="Medical history summary">
    <article class="summary-card">
        <span class="summary-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" focusable="false">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                <polyline points="14 2 14 8 20 8"></polyline>
                <line x1="16" y1="13" x2="8" y2="13"></line>
                <line x1="16" y1="17" x2="8" y2="17"></line>
                <polyline points="10 9 9 9 8 9"></polyline>
            </svg>
        </span>
        <div>
            <strong id="stat-total-visits">0</strong>
            <span>Total consultations</span>
        </div>
    </article>
    <article class="summary-card">
        <span class="summary-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" focusable="false">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                <line x1="16" y1="2" x2="16" y2="6"></line>
                <line x1="8" y1="2" x2="8" y2="6"></line>
                <line x1="3" y1="10" x2="21" y2="10"></line>
            </svg>
        </span>
        <div>
            <strong id="stat-last-visit">—</strong>
            <span>Most recent visit</span>
        </div>
    </article>
    <article class="summary-card">
        <span class="summary-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" focusable="false">
                <path d="m10.5 20.5 10-10a4.95 4.95 0 1 0-7-7l-10 10a4.95 4.95 0 1 0 7 7Z"></path>
                <path d="m8.5 8.5 7 7"></path>
            </svg>
        </span>
        <div>
            <strong id="stat-total-prescriptions">0</strong>
            <span>Prescriptions received</span>
        </div>
    </article>
</section>

<div id="patient-history-message" class="message" role="status" hidden></div>

<section class="content-panel">
    <div class="panel-heading">
        <div>
            <h2>Past Consultations & Clinical Records</h2>
            <p class="panel-help">Click "View Details & Meds" on any visit to see complete notes and prescribed medications.</p>
        </div>
        <div style="display: flex; gap: 0.5rem; align-items: center;">
            <input type="search" id="history-search" placeholder="Search by doctor, clinic, or condition..." style="max-width: 280px; padding: 0.4rem 0.75rem; font-size: 0.875rem;">
        </div>
    </div>

    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Visit Date</th>
                    <th>Clinic Branch</th>
                    <th>Attending Doctor</th>
                    <th>Clinical Outcome / Diagnosis</th>
                    <th>Medications</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="patient-history-results">
                <tr><td colspan="6" class="empty-state">Loading your medical history...</td></tr>
            </tbody>
        </table>
    </div>
</section>

<!-- Detail Modal with Prescriptions -->
<dialog class="modal modal-large" id="consultation-detail-dialog">
    <div class="modal-card">
        <div class="modal-heading">
            <div>
                <p class="eyebrow" id="modal-eyebrow">Consultation Details</p>
                <h2 id="modal-title">Clinical Consultation Summary</h2>
            </div>
            <button class="icon-button" type="button" id="close-detail-modal">×</button>
        </div>

        <div id="modal-body" style="display: grid; gap: 1.25rem;">
            <!-- Rendered by JS -->
        </div>

        <div class="modal-actions" style="justify-content: space-between;">
            <button class="button button-secondary" type="button" onclick="window.print()">🖨️ Print Record</button>
            <button class="button" type="button" id="dismiss-detail-modal">Close</button>
        </div>
    </div>
</dialog>

<script src="/CQMS/assets/js/patient-history.js"></script>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
