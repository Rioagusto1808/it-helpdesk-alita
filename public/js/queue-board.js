// Papan antrian: polling /antrian/data tiap 30 detik tanpa reload.
// Berhenti saat tab tersembunyi, backoff 30 → 60 → 120 detik saat gagal, data lama tidak dikosongkan.
(() => {
    'use strict';

    const board = document.querySelector('[data-queue-board]');
    if (!board) return;

    const BASE_DELAY = 30000;
    const MAX_DELAY = 120000;
    const tbody = board.querySelector('[data-queue-rows]');
    const template = board.querySelector('[data-row-template]');
    const search = board.querySelector('[data-queue-search]');
    const empty = board.querySelector('[data-queue-empty]');
    const more = board.querySelector('[data-queue-more]');
    const announce = board.querySelector('[data-queue-announce]');
    const errorToast = board.querySelector('[data-queue-error]');
    const updatedAt = board.querySelector('[data-updated-at]');

    let delay = BASE_DELAY;
    let timer = null;
    let known = new Set([...tbody.rows].map((tr) => tr.dataset.ticket));

    const highlight = () => {
        const q = search.value.trim().toUpperCase();
        [...tbody.rows].forEach((tr) => tr.classList.toggle('is-match', q !== '' && tr.dataset.ticket.includes(q)));
    };

    const buildRow = (row) => {
        const tr = template.content.firstElementChild.cloneNode(true);
        tr.dataset.ticket = row.ticket_no;
        tr.querySelectorAll('[data-field]').forEach((el) => { el.textContent = row[el.dataset.field] ?? '-'; });
        tr.querySelector('[data-badge]').className = `badge ${row.status_badge}`;
        if (!known.has(row.ticket_no)) tr.classList.add('flash-new');
        return tr;
    };

    const render = ({ summary, rows, more: moreCount }, meta) => {
        const fresh = rows.filter((row) => !known.has(row.ticket_no)).length;
        tbody.replaceChildren(...rows.map(buildRow));
        known = new Set(rows.map((row) => row.ticket_no));

        board.querySelectorAll('[data-stat]').forEach((el) => {
            const to = String(summary[el.dataset.stat] ?? 0);
            if (to === el.dataset.countTo) return;
            el.dataset.countFrom = el.dataset.countTo;
            el.dataset.countTo = to;
            window.AlitaMotion ? window.AlitaMotion.countUp(el) : (el.textContent = to);
        });

        empty.hidden = rows.length > 0;
        more.hidden = moreCount === 0;
        more.querySelector('[data-more-count]').textContent = moreCount;
        updatedAt.textContent = new Date(meta.updated_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
        if (fresh > 0) announce.textContent = `${fresh} tiket baru masuk antrian`;
        highlight();
    };

    const schedule = () => {
        clearTimeout(timer);
        timer = document.hidden ? null : setTimeout(poll, delay);
    };

    async function poll() {
        try {
            const res = await fetch(board.dataset.url, { headers: { Accept: 'application/json' } });
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            const json = await res.json();
            render(json.data, json.meta);
            delay = BASE_DELAY;
            errorToast.hidden = true;
        } catch {
            delay = Math.min(delay * 2, MAX_DELAY);
            errorToast.hidden = false;
        }
        schedule();
    }

    document.addEventListener('visibilitychange', () => (document.hidden ? clearTimeout(timer) : poll()));
    search.addEventListener('input', highlight);
    schedule();
})();
