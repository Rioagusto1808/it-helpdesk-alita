// Alita IT Helpdesk — efek gerak & interaksi bersama. Tanpa library, aman jika elemen tidak ada.
// Dimuat TANPA defer di <head>: class "js" terpasang sebelum render (mencegah kedip), efek lain jalan setelah DOM siap.
// Semua efek gerak mati saat prefers-reduced-motion; tanpa JavaScript semua konten tetap tampil dan berfungsi.
(() => {
    'use strict';

    document.documentElement.classList.add('js');

    const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const canHover = window.matchMedia('(hover: hover)').matches;
    const fmt = new Intl.NumberFormat('id-ID');

    // Count-up: <span data-count-to="128">128</span>
    function countUp(el) {
        const to = Number(el.dataset.countTo || 0);
        if (reduce || to === 0) { el.textContent = fmt.format(to); return; }
        const from = Number(el.dataset.countFrom || 0);
        const start = performance.now();
        const dur = 900;
        const step = (now) => {
            const t = Math.min(1, (now - start) / dur);
            const eased = 1 - Math.pow(1 - t, 4);
            el.textContent = fmt.format(Math.round(from + (to - from) * eased));
            if (t < 1) requestAnimationFrame(step);
        };
        requestAnimationFrame(step);
    }

    function spotlight() {
        if (reduce || !canHover) return;
        document.addEventListener('pointermove', (e) => {
            const el = e.target.closest && e.target.closest('.spotlight');
            if (!el) return;
            const r = el.getBoundingClientRect();
            el.style.setProperty('--mx', `${e.clientX - r.left}px`);
            el.style.setProperty('--my', `${e.clientY - r.top}px`);
        }, { passive: true });
    }

    // Elemen .reveal muncul saat masuk layar (tanpa JS: langsung tampil, lihat CSS .js .reveal).
    function reveal() {
        const items = document.querySelectorAll('.reveal');
        if (reduce || !('IntersectionObserver' in window)) {
            items.forEach((el) => el.classList.add('is-visible'));
            return;
        }
        const io = new IntersectionObserver((entries) => entries.forEach((entry) => {
            if (!entry.isIntersecting) return;
            entry.target.classList.add('is-visible');
            io.unobserve(entry.target);
        }), { rootMargin: '0px 0px -6% 0px' });
        items.forEach((el) => io.observe(el));
    }

    function stickyHeader() {
        const header = document.querySelector('[data-sticky-header]');
        if (!header) return;
        const update = () => header.classList.toggle('is-scrolled', window.scrollY > 8);
        window.addEventListener('scroll', update, { passive: true });
        update();
    }

    // Kartu miring mengikuti kursor (maks ±6°).
    function tilt() {
        if (reduce || !canHover) return;
        document.querySelectorAll('[data-tilt]').forEach((el) => {
            el.addEventListener('pointermove', (e) => {
                const r = el.getBoundingClientRect();
                const x = (e.clientX - r.left) / r.width - 0.5;
                const y = (e.clientY - r.top) / r.height - 0.5;
                el.style.setProperty('--rx', `${(-y * 6).toFixed(2)}deg`);
                el.style.setProperty('--ry', `${(x * 6).toFixed(2)}deg`);
            });
            el.addEventListener('pointerleave', () => {
                el.style.setProperty('--rx', '0deg');
                el.style.setProperty('--ry', '0deg');
            });
        });
    }

    // Gelombang (ripple) dari titik klik pada tombol.
    function ripple() {
        if (reduce) return;
        document.addEventListener('pointerdown', (e) => {
            const btn = e.target.closest && e.target.closest('.btn');
            if (!btn || btn.disabled) return;
            const r = btn.getBoundingClientRect();
            const wave = document.createElement('span');
            wave.className = 'ripple';
            wave.style.setProperty('--size', `${Math.max(r.width, r.height) * 2}px`);
            wave.style.setProperty('--x', `${e.clientX - r.left}px`);
            wave.style.setProperty('--y', `${e.clientY - r.top}px`);
            btn.appendChild(wave);
            wave.addEventListener('animationend', () => wave.remove());
        });
    }

    // Toast: tombol tutup + sembunyikan setelah animasi keluar.
    function toasts() {
        document.addEventListener('click', (e) => {
            const btn = e.target.closest && e.target.closest('[data-toast-close]');
            if (btn) btn.closest('.toast')?.classList.add('is-leaving');
        });
        document.addEventListener('animationend', (e) => {
            const el = e.target;
            if (!el.classList || !el.classList.contains('toast')) return;
            if (e.animationName === 'toast-out') el.hidden = true;
        });
    }

    // Tombol tampil/sembunyikan password (tersembunyi tanpa JS).
    function passwordToggles() {
        document.querySelectorAll('[data-password-toggle]').forEach((btn) => {
            const input = document.getElementById(btn.dataset.passwordToggle);
            if (!input) return;
            btn.hidden = false;
            btn.addEventListener('click', () => {
                const show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                btn.setAttribute('aria-pressed', String(show));
                btn.setAttribute('aria-label', show ? 'Sembunyikan password' : 'Tampilkan password');
                btn.classList.toggle('is-on', show);
            });
        });
    }

    // Meter kekuatan password (panduan saja; aturan tetap di server: minimal 10 karakter).
    function strengthMeters() {
        const labels = ['', 'Terlalu pendek', 'Cukup', 'Kuat', 'Sangat kuat'];
        document.querySelectorAll('[data-strength-for]').forEach((meter) => {
            const input = document.getElementById(meter.dataset.strengthFor);
            const label = document.querySelector(`[data-strength-label="${meter.dataset.strengthFor}"]`);
            if (!input) return;
            const update = () => {
                const v = input.value;
                let score = 0;
                if (v.length > 0) score = 1;
                if (v.length >= 10) score = 2;
                if (v.length >= 10 && /[a-z]/i.test(v) && /\d/.test(v)) score = 3;
                if (v.length >= 14 && /[A-Z]/.test(v) && /[a-z]/.test(v) && /\d/.test(v) && /[^A-Za-z0-9]/.test(v)) score = 4;
                meter.value = score;
                meter.dataset.score = String(score);
                if (label) label.textContent = labels[score];
            };
            input.addEventListener('input', update);
            update();
        });
    }

    // Confetti sekali (halaman sukses).
    function confetti() {
        if (reduce) return;
        document.querySelectorAll('[data-confetti]').forEach((host) => {
            for (let i = 0; i < 56; i++) {
                const piece = document.createElement('span');
                piece.className = `confetti confetti--${i % 4}`;
                piece.style.setProperty('--x', `${(Math.random() * 2 - 1) * 320}px`);
                piece.style.setProperty('--y', `${-(Math.random() * 200 + 140)}px`);
                piece.style.setProperty('--r', `${Math.random() * 900 - 450}deg`);
                piece.style.setProperty('--d', `${Math.round(Math.random() * 250)}ms`);
                host.appendChild(piece);
            }
            setTimeout(() => host.replaceChildren(), 3200);
        });
    }

    function ready() {
        document.querySelectorAll('[data-count-to]').forEach(countUp);
        spotlight();
        reveal();
        stickyHeader();
        tilt();
        ripple();
        toasts();
        passwordToggles();
        strengthMeters();
        confetti();
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', ready);
    else ready();

    // Dipakai halaman lain (refresh papan antrian, dashboard) untuk menganimasikan angka baru.
    window.AlitaMotion = { countUp };
})();
