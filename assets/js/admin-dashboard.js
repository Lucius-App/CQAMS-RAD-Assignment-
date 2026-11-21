(function () {
    'use strict';
    var cards = document.getElementById('admin-statistics');
    var message = document.getElementById('admin-statistics-message');
    function load() {
        fetch('/CQMS/api/admin/metrics.php', { headers: { Accept: 'application/json' } })
            .then(function (response) { return response.json().then(function (body) { if (!response.ok || !body.success) throw new Error(body.message || 'Statistics could not be loaded.'); return body.data; }); })
            .then(function (data) {
                var today = data.today;
                cards.innerHTML = [
                    ['Appointments today', today.appointments],
                    ['Queue entries today', today.queue_total],
                    ['Active queue patients', today.queue_active],
                    ['Active doctors', today.active_doctors]
                ].map(function (item) { return '<article class="dashboard-card"><div class="card-content"><div class="card-heading"><h2>' + item[0] + '</h2><span class="status-badge status-badge-muted">Today</span></div><p class="stat-value">' + item[1] + '</p></div></article>'; }).join('');
            })
            .catch(function (error) { message.textContent = error.message; message.className = 'message message-error'; message.hidden = false; });
    }
    load();
})();
