(() => {
    'use strict';

    // Tombol "Salin nomor" di halaman sukses
    document.querySelectorAll('[data-copy]').forEach((btn) => {
        btn.addEventListener('click', async () => {
            const target = document.querySelector(btn.dataset.copy);
            if (!target) return;
            try {
                await navigator.clipboard.writeText(target.textContent.trim());
                btn.textContent = 'Nomor disalin';
                setTimeout(() => { btn.textContent = 'Salin nomor'; }, 2000);
            } catch (e) {
                btn.textContent = 'Salin manual, ya';
            }
        });
    });

    const form = document.getElementById('ticket-form');
    if (!form) return;

    const panel = form.closest('[data-accent-root]') || form;
    const MAX_BYTES = Number(form.dataset.maxKb || 5120) * 1024;
    const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'application/pdf'];
    const ALLOWED_EXT = /\.(jpe?g|png|pdf)$/i;
    const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    const $ = (sel, root = form) => root.querySelector(sel);
    const $$ = (sel, root = form) => Array.from(root.querySelectorAll(sel));

    /* ---------- Error helper ---------- */
    function fieldOf(el) {
        return el ? el.closest('.field') : null;
    }

    function showError(el, message) {
        const field = fieldOf(el);
        if (!field) return;
        field.classList.add('has-error');
        let p = field.querySelector(':scope > .error');
        if (!p) {
            p = document.createElement('p');
            p.className = 'error';
            field.appendChild(p);
        }
        p.textContent = message;
        if (el.matches('input, select, textarea')) el.setAttribute('aria-invalid', 'true');
    }

    function clearError(el) {
        const field = fieldOf(el);
        if (!field) return;
        field.classList.remove('has-error');
        field.querySelectorAll(':scope > .error').forEach((p) => p.remove());
        el.removeAttribute && el.removeAttribute('aria-invalid');
    }

    /* ---------- Kategori → Services / Module ---------- */
    function toggleInputs(container, enabled) {
        $$('input, select, textarea', container).forEach((el) => { el.disabled = !enabled; });
    }

    function syncOther(select) {
        const box = $(`[data-other-for="${select.id}"]`);
        if (!box) return;
        const opt = select.selectedOptions[0];
        const on = !select.disabled && !!opt && opt.dataset.other === '1';
        const input = $('input', box);
        box.classList.toggle('is-on', on);
        input.disabled = !on;
        input.required = on;
        if (!on) clearError(input);
    }

    function applyCategory() {
        const checked = $('input[name="category_id"]:checked');
        const code = checked ? checked.dataset.code : '';
        panel.dataset.accent = code;

        $$('[data-branch]').forEach((branch) => {
            const on = branch.dataset.branch === code;
            branch.classList.toggle('is-on', on);
            toggleInputs(branch, on);
            const select = $('select', branch);
            if (select) select.required = on;
        });

        $$('select[data-has-other]').forEach(syncOther);
    }

    $$('input[name="category_id"]').forEach((radio) => {
        radio.addEventListener('change', () => {
            clearError(radio);
            applyCategory();
        });
    });

    $$('select[data-has-other]').forEach((select) => {
        select.addEventListener('change', () => {
            syncOther(select);
            const box = $(`[data-other-for="${select.id}"]`);
            if (box && box.classList.contains('is-on')) $('input', box).focus();
        });
    });

    /* ---------- Penghitung karakter deskripsi ---------- */
    const desc = $('#description');
    const counter = $('#desc-count');
    function updateCounter() {
        if (desc && counter) counter.textContent = `${desc.value.length} / ${desc.maxLength || 5000}`;
    }
    if (desc) desc.addEventListener('input', updateCounter);

    /* ---------- Lampiran (klik atau drag & drop) ---------- */
    const fileInput = $('#attachment');
    const drop = $('.drop');
    const chip = $('#file-chip');

    function humanSize(bytes) {
        return bytes >= 1048576 ? `${(bytes / 1048576).toFixed(1)} MB` : `${Math.max(1, Math.round(bytes / 1024))} KB`;
    }

    function fileProblem(file) {
        if (!file) return null;
        const okType = file.type ? ALLOWED_TYPES.includes(file.type) : ALLOWED_EXT.test(file.name);
        if (!okType || !ALLOWED_EXT.test(file.name)) return 'Lampiran harus berupa JPG, PNG, atau PDF.';
        if (file.size > MAX_BYTES) return 'Ukuran lampiran maksimal 5 MB.';
        return null;
    }

    function renderFile() {
        const file = fileInput.files[0];
        clearError(fileInput);
        if (!file) {
            chip.hidden = true;
            return;
        }
        const problem = fileProblem(file);
        if (problem) {
            fileInput.value = '';
            chip.hidden = true;
            showError(fileInput, problem);
            return;
        }
        $('#file-name').textContent = file.name;
        $('#file-size').textContent = humanSize(file.size);
        chip.hidden = false;
    }

    if (fileInput && drop && chip) {
        fileInput.addEventListener('change', renderFile);

        ['dragenter', 'dragover'].forEach((type) => drop.addEventListener(type, (e) => {
            e.preventDefault();
            drop.classList.add('is-over');
        }));
        ['dragleave', 'dragend', 'drop'].forEach((type) => drop.addEventListener(type, () => {
            drop.classList.remove('is-over');
        }));
        drop.addEventListener('drop', (e) => {
            e.preventDefault();
            const files = e.dataTransfer && e.dataTransfer.files;
            if (!files || !files.length) return;
            const dt = new DataTransfer();
            dt.items.add(files[0]);
            fileInput.files = dt.files;
            renderFile();
        });

        $('#file-remove').addEventListener('click', () => {
            fileInput.value = '';
            renderFile();
            drop.focus();
        });
    }

    /* ---------- Validasi sebelum kirim (server tetap memvalidasi ulang) ---------- */
    function validate() {
        let first = null;
        const fail = (el, message) => {
            showError(el, message);
            if (!first) first = el;
        };

        if (!$('input[name="category_id"]:checked')) {
            fail($('input[name="category_id"]'), 'Pilih kategori ITPass atau ITInfra.');
        }

        $$('select[required]:not(:disabled)').forEach((select) => {
            if (!select.value) fail(select, select.dataset.msg || 'Wajib dipilih.');
        });

        $$('[data-other-for] input:not(:disabled)').forEach((input) => {
            if (!input.value.trim()) fail(input, 'Sebutkan detailnya.');
        });

        const name = $('#requester_name');
        if (!name.value.trim()) fail(name, 'Nama wajib diisi.');

        const email = $('#requester_email');
        if (!email.value.trim()) fail(email, 'Email wajib diisi.');
        else if (!EMAIL_RE.test(email.value.trim())) fail(email, 'Format email tidak valid.');

        const text = desc.value.trim();
        if (!text) fail(desc, 'Deskripsi wajib diisi.');
        else if (text.length < 10) fail(desc, 'Deskripsi minimal 10 karakter supaya tim IT bisa memahami kendalanya.');

        if (fileInput && fileInput.files[0]) {
            const problem = fileProblem(fileInput.files[0]);
            if (problem) fail(fileInput, problem);
        }

        if (first) first.focus();
        return !first;
    }

    form.addEventListener('input', (e) => {
        if (e.target.matches('input:not([type="radio"]):not([type="file"]), textarea')) clearError(e.target);
    });
    form.addEventListener('change', (e) => {
        if (e.target.matches('select')) clearError(e.target);
    });

    const submitBtn = $('button[type="submit"]');
    const submitLabel = submitBtn.textContent;

    form.addEventListener('submit', (e) => {
        if (form.dataset.sending === '1') {
            e.preventDefault(); // cegah tiket dobel karena klik berkali-kali
            return;
        }
        if (!validate()) {
            e.preventDefault();
            return;
        }
        form.dataset.sending = '1';
        submitBtn.disabled = true;
        submitBtn.textContent = 'Mengirim tiket…';
    });

    // Kalau user menekan tombol Back, kembalikan tombol ke kondisi normal
    window.addEventListener('pageshow', () => {
        form.dataset.sending = '';
        submitBtn.disabled = false;
        submitBtn.textContent = submitLabel;
    });

    applyCategory();
    updateCounter();
})();
