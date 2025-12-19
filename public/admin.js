// EasyAdmin custom JS (loaded via DashboardController::configureAssets()).

(function () {
    if (window.__appointmentStatusInlineInit) return;
    window.__appointmentStatusInlineInit = true;

    function badgeClassForStatus(status) {
        switch (status) {
            case 'PENDING':
                return 'badge badge-warning';
            case 'CONFIRMED':
                return 'badge badge-success';
            case 'COMPLETED':
                return 'badge badge-info';
            case 'CANCELED':
                return 'badge badge-danger';
            default:
                return 'badge badge-secondary';
        }
    }

    function refreshChecks(wrap) {
        const badge = wrap && wrap.querySelector ? wrap.querySelector('.js-appointment-status-badge') : null;
        if (!wrap || !badge) return;
        const current = badge.dataset.current || (badge.textContent ? badge.textContent.trim() : '') || '';
        wrap.querySelectorAll('.js-appointment-status-check').forEach(el => {
            el.textContent = (el.dataset.status === current) ? '✓' : '';
        });
    }

    // When opening dropdown, refresh checkmarks to match current status
    document.addEventListener('click', (e) => {
        const badge = e.target.closest('.js-appointment-status-badge');
        if (!badge) return;
        const wrap = badge.closest('.js-appointment-status-wrap');
        // let bootstrap toggle the menu, then refresh
        setTimeout(() => refreshChecks(wrap), 0);
    });

    document.addEventListener('click', async (e) => {
        const item = e.target.closest('.js-appointment-status-item');
        if (!item) return;
        e.preventDefault();

        const wrap = item.closest('.js-appointment-status-wrap');
        const badge = wrap ? wrap.querySelector('.js-appointment-status-badge') : null;
        if (!wrap || !badge) return;

        const url = badge.dataset.url;
        const token = badge.dataset.token;
        const status = item.dataset.status;
        const prev = badge.dataset.current || 'PENDING';

        // optimistic UI
        badge.textContent = status;
        badge.className = 'js-appointment-status-badge dropdown-toggle border-0 ' + badgeClassForStatus(status);

        try {
            const res = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ status, _token: token })
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok || !data.success) {
                throw new Error(data.error || 'Request failed');
            }

            badge.dataset.current = data.status;
            badge.textContent = data.status;
            badge.className = 'js-appointment-status-badge dropdown-toggle border-0 ' + badgeClassForStatus(data.status);
            refreshChecks(wrap);
        } catch (err) {
            badge.dataset.current = prev;
            badge.textContent = prev;
            badge.className = 'js-appointment-status-badge dropdown-toggle border-0 ' + badgeClassForStatus(prev);
            refreshChecks(wrap);
            alert('Չհաջողվեց փոխել status-ը։');
        }
    });
})();


