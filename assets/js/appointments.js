(function () {
    'use strict';
    var base = '/CQMS/api/appointments/';
    var token = window.CQAMS_CSRF_TOKEN || '';
    var results = document.getElementById('appointment-results');
    var message = document.getElementById('appointment-message');
    var formMessage = document.getElementById('appointment-form-message');
    var formDialog = document.getElementById('appointment-dialog');
    var cancelDialog = document.getElementById('cancel-dialog');
    var deleteDialog = document.getElementById('delete-appointment-dialog');
    var detailsDialog = document.getElementById('appointment-details-dialog');
    var form = document.getElementById('appointment-form');
    var editingId = null;
    var cancellingId = null;
    var deletingId = null;
    var meta = { doctors: [], patients: [] };

    function escapeHtml(value) { return String(value == null ? '' : value).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;'); }
    function show(element, text, type) { element.textContent = text; element.className = 'message' + (type ? ' message-' + type : ''); element.hidden = false; }
    function clear(element) { element.hidden = true; element.textContent = ''; }
    function request(url, options) {
        options = options || {};
        options.headers = Object.assign({ Accept: 'application/json', 'Content-Type': 'application/json' }, options.headers || {});
        if (options.method && options.method !== 'GET') { options.headers['X-CSRF-Token'] = token; }
        return fetch(url, options).then(function (response) {
            return response.json().then(function (body) {
                if (!response.ok || !body.success) { var error = new Error(body.message || 'Request failed.'); error.body = body; throw error; }
                return body.data || {};
            });
        });
    }
    function renderOptions() {
        document.getElementById('appointment-patient').innerHTML = '<option value="">Select patient</option>' + meta.patients.map(function (p) { return '<option value="' + p.patient_id + '">' + escapeHtml(p.registration_number + ' - ' + p.first_name + ' ' + p.last_name) + '</option>'; }).join('');
        document.getElementById('appointment-doctor').innerHTML = '<option value="">Select doctor</option>' + meta.doctors.map(function (d) { return '<option value="' + d.doctor_id + '">' + escapeHtml(d.doctor_name + ' - ' + d.specialization) + '</option>'; }).join('');
    }
    function loadMeta() { return request(base + 'meta.php').then(function (data) { meta = data; renderOptions(); }); }
    function loadSlots() {
        var doctor = document.getElementById('appointment-doctor').value;
        var date = document.getElementById('appointment-date').value;
        var select = document.getElementById('appointment-time');
        if (!doctor || !date) { select.innerHTML = '<option value="">Select doctor and date first</option>'; return Promise.resolve(); }
        select.innerHTML = '<option value="">Loading...</option>';
        return request(base + 'options.php?doctor_id=' + encodeURIComponent(doctor) + '&date=' + encodeURIComponent(date)).then(function (data) {
            select.innerHTML = '<option value="">Select available time</option>' + data.slots.map(function (slot) { return '<option value="' + slot.time + '"' + (slot.available ? '' : ' disabled') + '>' + slot.time + (slot.available ? '' : ' (unavailable)') + '</option>'; }).join('');
        }).catch(function (error) {
            select.innerHTML = '<option value="">' + escapeHtml(error.message) + '</option>';
            throw error;
        });
    }
    function refreshSlots() { loadSlots().catch(function (error) { show(formMessage, error.message, 'error'); }); }
    function loadAppointments() {
        var query = document.getElementById('appointment-query').value;
        var date = document.getElementById('appointment-date-filter').value;
        var status = document.getElementById('appointment-status-filter').value;
        results.innerHTML = '<tr><td colspan="6" class="empty-state">Loading appointments...</td></tr>';
        request(base + '?q=' + encodeURIComponent(query) + '&date=' + encodeURIComponent(date) + '&status=' + encodeURIComponent(status)).then(function (data) {
            document.getElementById('appointment-count').textContent = data.appointments.length + (data.appointments.length === 1 ? ' record' : ' records');
            if (!data.appointments.length) { results.innerHTML = '<tr><td colspan="6" class="empty-state">No appointments found.</td></tr>'; return; }
            results.innerHTML = data.appointments.map(function (a) {
                var actions = '<button class="button button-small" data-view-id="' + a.appointment_id + '" type="button">View</button> ';
                actions += a.status === 'cancelled' || a.status === 'completed' || a.status === 'no_show' ? '' : '<button class="button button-small" data-edit-id="' + a.appointment_id + '" type="button">Reschedule</button> <button class="button button-small button-danger" data-cancel-id="' + a.appointment_id + '" type="button">Cancel</button>';
                if (Number(a.has_queue_history) !== 1) { actions += ' <button class="button button-small button-danger" data-delete-id="' + a.appointment_id + '" type="button">Delete</button>'; }
                return '<tr><td>' + escapeHtml(a.appointment_date + ' ' + a.start_time.substring(0, 5)) + '</td><td><strong>' + escapeHtml(a.patient_name) + '</strong><br><small>' + escapeHtml(a.registration_number) + '</small></td><td>' + escapeHtml(a.doctor_name) + '</td><td><span class="status-badge status-' + escapeHtml(a.status) + '">' + escapeHtml(a.status.replace('_', ' ')) + '</span></td><td>' + escapeHtml(a.reason || 'Not provided') + '</td><td>' + actions + '</td></tr>';
            }).join('');
        }).catch(function (error) { show(message, error.message, 'error'); });
    }
    function openNew() { editingId = null; form.reset(); document.getElementById('appointment-dialog-title').textContent = 'New appointment'; clear(formMessage); formDialog.showModal(); }
    function openEdit(id) {
        editingId = id; clear(formMessage);
        request(base + 'show.php?appointment_id=' + id).then(function (data) {
            var a = data.appointment;
            document.getElementById('appointment-patient').value = a.patient_id;
            document.getElementById('appointment-doctor').value = a.doctor_id;
            document.getElementById('appointment-date').value = a.appointment_date;
            document.getElementById('appointment-reason').value = a.reason || '';
            document.getElementById('appointment-dialog-title').textContent = 'Reschedule appointment';
            return loadSlots().then(function () { document.getElementById('appointment-time').value = a.start_time.substring(0, 5); formDialog.showModal(); });
        }).catch(function (error) { show(message, error.message, 'error'); });
    }
    document.getElementById('appointment-filter-form').addEventListener('submit', function (e) { e.preventDefault(); loadAppointments(); });
    document.getElementById('open-appointment-form').addEventListener('click', openNew);
    document.getElementById('close-appointment-form').addEventListener('click', function () { formDialog.close(); });
    document.getElementById('cancel-appointment-form').addEventListener('click', function () { formDialog.close(); });
    document.getElementById('appointment-doctor').addEventListener('change', refreshSlots);
    document.getElementById('appointment-date').addEventListener('change', refreshSlots);
    results.addEventListener('click', function (e) {
        if (e.target.dataset.viewId) {
            request(base + 'show.php?appointment_id=' + e.target.dataset.viewId).then(function (data) {
                var a = data.appointment;
                document.getElementById('appointment-details-title').textContent = a.patient_name;
                document.getElementById('appointment-details').innerHTML = [
                    ['Reference', a.registration_number],
                    ['Doctor', a.doctor_name + ' - ' + a.specialization],
                    ['Date and time', a.appointment_date + ' ' + a.start_time.substring(0, 5)],
                    ['Status', a.status.replace('_', ' ')],
                    ['Reason', a.reason || 'Not provided'],
                    ['Cancellation reason', a.cancellation_reason || 'Not applicable']
                ].map(function (item) { return '<div><dt>' + escapeHtml(item[0]) + '</dt><dd>' + escapeHtml(item[1]) + '</dd></div>'; }).join('');
                detailsDialog.showModal();
            }).catch(function (error) { show(message, error.message, 'error'); });
        }
        if (e.target.dataset.editId) { openEdit(e.target.dataset.editId); }
        if (e.target.dataset.cancelId) { cancellingId = e.target.dataset.cancelId; document.getElementById('cancellation-reason').value = ''; cancelDialog.showModal(); }
        if (e.target.dataset.deleteId) {
            deletingId = e.target.dataset.deleteId;
            clear(document.getElementById('delete-appointment-message'));
            deleteDialog.showModal();
        }
    });
    document.getElementById('close-appointment-details').addEventListener('click', function () { detailsDialog.close(); });
    document.getElementById('dismiss-appointment-details').addEventListener('click', function () { detailsDialog.close(); });
    document.getElementById('close-cancel-dialog').addEventListener('click', function () { cancelDialog.close(); });
    document.getElementById('dismiss-cancel').addEventListener('click', function () { cancelDialog.close(); });
    document.getElementById('close-delete-appointment').addEventListener('click', function () { deleteDialog.close(); });
    document.getElementById('dismiss-delete-appointment').addEventListener('click', function () { deleteDialog.close(); });
    document.getElementById('confirm-delete-appointment').addEventListener('click', function () {
        if (!deletingId) return;
        request(base + 'show.php?appointment_id=' + encodeURIComponent(deletingId) + '&permanent=1', { method: 'DELETE' }).then(function () {
            deleteDialog.close();
            deletingId = null;
            show(message, 'Appointment deleted permanently.', 'success');
            loadAppointments();
        }).catch(function (error) {
            show(document.getElementById('delete-appointment-message'), error.message, 'error');
        });
    });
    document.getElementById('cancel-form').addEventListener('submit', function (e) { e.preventDefault(); request(base + 'show.php?appointment_id=' + cancellingId, { method: 'DELETE', body: JSON.stringify({ cancellation_reason: document.getElementById('cancellation-reason').value }) }).then(function () { cancelDialog.close(); show(message, 'Appointment cancelled.', 'success'); loadAppointments(); }).catch(function (error) { show(message, error.message, 'error'); }); });
    form.addEventListener('submit', function (e) {
        e.preventDefault(); clear(formMessage);
        var payload = { patient_id: document.getElementById('appointment-patient').value, doctor_id: document.getElementById('appointment-doctor').value, appointment_date: document.getElementById('appointment-date').value, start_time: document.getElementById('appointment-time').value, reason: document.getElementById('appointment-reason').value };
        request(editingId ? base + 'show.php?appointment_id=' + editingId : base, { method: editingId ? 'PUT' : 'POST', body: JSON.stringify(payload) }).then(function () { formDialog.close(); show(message, editingId ? 'Appointment rescheduled.' : 'Appointment booked.', 'success'); loadAppointments(); }).catch(function (error) { show(formMessage, error.message, 'error'); });
    });
    loadMeta().then(loadAppointments).catch(function (error) { show(message, error.message, 'error'); });
})();
