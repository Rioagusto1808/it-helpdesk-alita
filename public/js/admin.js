// Panel admin: drawer menu di HP/tablet. Dimuat tanpa defer supaya class "js" terpasang sebelum render.
(() => {
    'use strict';

    document.documentElement.classList.add('js');

    // Dashboard: angka kartu diperbarui tiap 60 detik, berhenti saat tab tersembunyi.
    function refreshDashboard() {
        const board = document.querySelector('[data-dashboard]');
        if (!board) return;
        const announce = document.querySelector('[data-dashboard-announce]');
        let timer = null;

        const load = async () => {
            try {
                const res = await fetch(board.dataset.url, { headers: { Accept: 'application/json' } });
                if (!res.ok) return;
                const { data } = await res.json();
                board.querySelectorAll('[data-stat]').forEach((el) => {
                    const to = String(data[el.dataset.stat] ?? 0);
                    if (to === el.dataset.countTo) return;
                    if (el.dataset.stat === 'baru' && Number(to) > Number(el.dataset.countTo) && announce) {
                        announce.textContent = `${Number(to) - Number(el.dataset.countTo)} tiket baru masuk`;
                    }
                    el.dataset.countFrom = el.dataset.countTo;
                    el.dataset.countTo = to;
                    window.AlitaMotion ? window.AlitaMotion.countUp(el) : (el.textContent = to);
                });
            } catch {
                // Gagal sementara: angka lama tetap tampil, dicoba lagi di putaran berikutnya.
            }
        };
        const schedule = () => { clearInterval(timer); timer = document.hidden ? null : setInterval(load, 60000); };

        document.addEventListener('visibilitychange', () => { if (!document.hidden) load(); schedule(); });
        schedule();
    }

    document.addEventListener('DOMContentLoaded', () => {
        // Filter terbuka di desktop, dilipat di HP (tanpa JS tetap terbuka).
        if (window.matchMedia('(max-width: 767px)').matches) {
            document.querySelectorAll('details[data-collapse-mobile]').forEach((d) => { d.open = false; });
        }

        refreshDashboard();

        const drawer = document.querySelector('[data-drawer]');
        const toggle = document.querySelector('[data-drawer-toggle]');
        const backdrop = document.querySelector('[data-drawer-close]');
        if (!drawer || !toggle || !backdrop) return;

        const drawerMode = window.matchMedia('(max-width: 1023px)');
        const isOpen = () => drawer.classList.contains('is-open');

        // inert (bukan visibility) agar fokus bisa langsung pindah tanpa menunggu transisi.
        const setOpen = (open, moveFocus = true) => {
            drawer.classList.toggle('is-open', open);
            drawer.inert = drawerMode.matches && !open;
            backdrop.hidden = !open;
            toggle.setAttribute('aria-expanded', String(open));
            if (!moveFocus) return;
            if (open) drawer.querySelector('a')?.focus({ preventScroll: true });
            else toggle.focus();
        };

        toggle.addEventListener('click', () => setOpen(!isOpen()));
        backdrop.addEventListener('click', () => setOpen(false));
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && isOpen()) setOpen(false);
        });
        drawerMode.addEventListener('change', () => setOpen(false, false));
        setOpen(false, false);
    });
})();
