// Alita IT Helpdesk — efek gerak kecil. Tanpa library. Aman jika elemen tidak ada.
(() => {
    'use strict';
    const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    // Spotlight: set posisi kursor ke --mx/--my
    if (!reduce && window.matchMedia('(hover: hover)').matches) {
        document.addEventListener('pointermove', (e) => {
            const el = e.target.closest && e.target.closest('.spotlight');
            if (!el) return;
            const r = el.getBoundingClientRect();
            el.style.setProperty('--mx', `${e.clientX - r.left}px`);
            el.style.setProperty('--my', `${e.clientY - r.top}px`);
        }, { passive: true });
    }

    // Count-up: <span data-count-to="128">0</span>
    const fmt = new Intl.NumberFormat('id-ID');
    function countUp(el) {
        const to = Number(el.dataset.countTo || 0);
        if (reduce || to === 0) { el.textContent = fmt.format(to); return; }
        const from = Number(el.dataset.countFrom || 0);
        const start = performance.now();
        const dur = 700;
        const step = (now) => {
            const t = Math.min(1, (now - start) / dur);
            const eased = 1 - Math.pow(1 - t, 3);
            el.textContent = fmt.format(Math.round(from + (to - from) * eased));
            if (t < 1) requestAnimationFrame(step);
        };
        requestAnimationFrame(step);
    }
    document.querySelectorAll('[data-count-to]').forEach(countUp);

    // Dipakai halaman lain (misalnya refresh dashboard) untuk menganimasikan angka baru
    window.AlitaMotion = { countUp };
})();
