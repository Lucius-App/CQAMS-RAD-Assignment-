(function () {
    'use strict';

    var api = '/CQMS/api/admin/appointment_approvals.php';
    var token = window.CQAMS_CSRF_TOKEN || '';
    var results = document.getElementById('appointment-approval-results');
    var message = document.getElementById('appointment-approval-message');
    var filter = document.getElementById('appointment-approval-filter');

    function esc(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function show(text, type) {
        message.textContent = text;
        message.className = 'message' + (type ? ' message-' + type : '');
        message.hidden = false;
    }

    function request(url, options) {
        options = options || {};
        options.headers = Object.assign({
            Accept: 'application/json',
            'Content-Type': 'application/json'
        }, options.headers || {});
        if (options.method && options.method !== 'GET') {
            options.headers['X-CSRF-Token'] = token;
        }

        return fetch(url, options).then(function (response) {
            return response.json().then(function (body) {
                if (!response.ok || !body.success) {
                    throw new Error(body.message || 'The request could not be completed.');
                }
                return body.data || {};
            });
        });
    }

    function render(items) {
        if (!items.length) {
            results.innerHTML = '<tr><td colspan="6" class="empty-state">No appointment requests for this filter.</td></tr>';
            return;
        }

        results.innerHTML = items.map(function (item) {
            var statusClass = item.approval_status === 'approved'
                ? 'status-approval-approved'
                : item.approval_status === 'rejected'
                    ? 'status-approval-rejected'
                    : 'status-approval-pending';
            var reviewable = item.approval_status === 'pending' && item.status === 'scheduled';
            var actions = reviewable
                ? '<button class="button button-small" type="button" data-approve="' + item.appointment_id + '">Approve</button> ' +
                  '<button class="button button-small button-danger" type="button" data-reject="' + item.appointment_id + '">Reject</button>'
                : esc(item.approval_note || (item.status === 'cancelled' ? 'Cancelled by patient' : '—'));
            var statusLabel = item.status === 'cancelled' ? 'cancelled' : item.approval_status;

            return '<tr>' +
                '<td><strong>' + esc(item.patient_name) + '</strong><br><small>' + esc(item.registration_number) + '</small></td>' +
                '<td>' + esc(item.clinic_name || 'Clinic not set') + '<br><small>Dr. ' + esc(item.doctor_name) + ' · ' + esc(item.specialization) + '</small></td>' +
                '<td>' + esc(item.appointment_date) + '<br><small>' + esc(item.start_time.substring(0, 5)) + '</small></td>' +
                '<td><strong>' + esc(item.reason_category) + '</strong><br><small>' + esc(item.reason || 'No details') + '</small></td>' +
                '<td><span class="status-badge ' + (item.status === 'cancelled' ? 'status-cancelled' : statusClass) + '">' + esc(statusLabel) + '</span></td>' +
                '<td>' + actions + '</td>' +
                '</tr>';
        }).join('');
    }

    function load() {
        results.innerHTML = '<tr><td colspan="6" class="empty-state">Loading appointment requests...</td></tr>';
        request(api + '?status=' + encodeURIComponent(filter.value))
            .then(function (data) { render(data.appointments || []); })
            .catch(function (error) {
                show(error.message, 'error');
                results.innerHTML = '<tr><td colspan="6" class="empty-state">Requests could not be loaded.</td></tr>';
            });
    }

    function decide(id, action) {
        var note = '';
        if (action === 'reject') {
            var enteredNote = window.prompt('Optional reason for rejecting this appointment request:');
            if (enteredNote === null) {
                return;
            }
            note = enteredNote;
            if (!window.confirm('Reject this appointment request? Its time slot will become available again.')) {
                return;
            }
        }

        request(api, {
            method: 'POST',
            body: JSON.stringify({
                action: action,
                appointment_id: id,
                approval_note: note
            })
        }).then(function () {
            show(action === 'approve' ? 'Appointment request approved.' : 'Appointment request rejected; its slot is released.', 'success');
            load();
        }).catch(function (error) {
            show(error.message, 'error');
        });
    }

    results.addEventListener('click', function (event) {
        var approveId = event.target.dataset.approve;
        var rejectId = event.target.dataset.reject;
        if (approveId) {
            decide(approveId, 'approve');
        } else if (rejectId) {
            decide(rejectId, 'reject');
        }
    });

    filter.addEventListener('change', load);
    document.getElementById('refresh-appointment-approvals').addEventListener('click', load);
    load();
})();
