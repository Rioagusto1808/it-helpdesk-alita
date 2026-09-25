// Form publik (tiket baru, cari tiket, balasan) + tombol "Salin nomor" + dialog konfirmasi.
// Progressive enhancement: form tetap bisa dikirim tanpa JavaScript, server selalu memvalidasi ulang.
(() => {
    'use strict';

    /* ---------- Konfirmasi dengan <dialog> (tanpa JS: form langsung terkirim) ---------- */
    document.querySelectorAll('form[data-confirm]').forEach((confirmForm) => {
        const dialog = document.getElementById(confirmForm.dataset.confirm);
        if (!dialog || !dialog.showModal) return;
        confirmForm.addEventListener('submit', (e) => {
            if (confirmForm.dataset.confirmed) return;
            e.preventDefault();
            dialog.showModal();
        });
        dialog.querySelector('[data-confirm-accept]').addEventListener('click', () => {
            confirmForm.dataset.confirmed = '1';
            confirmForm.requestSubmit();
        });
    });

    /* ---------- Salin nomor tiket (halaman sukses) ---------- */
    document.querySelectorAll('[data-copy]').forEach((btn) => {
        const target = document.getElementById(btn.dataset.copy);
        const status = btn.parentElement.querySelector('[data-copy-status]');
        const label = btn.textContent;
        btn.addEventListener('click', async () => {
            let message = 'Nomor disalin';
            try {
                await navigator.clipboard.writeText(target.textContent.trim());
            } catch {
                message = 'Gagal menyalin, salin manual';
            }
            btn.textContent = message;
            if (status) status.textContent = message;
            setTimeout(() => { btn.textContent = label; }, 2000);
        });
    });

    const form = document.querySelector('[data-ticket-form]');
    if (!form) return;

    /* ---------- Cabang Layanan/Modul ----------
       CSS (:has) yang menentukan field mana yang tampil. Di sini hanya disamakan:
       field tersembunyi dinonaktifkan agar tidak terkirim dan tidak menghalangi validasi. */
    const branchInputs = form.querySelectorAll('[data-required-when-visible]');
    const syncBranches = () => {
        branchInputs.forEach((el) => {
            const visible = el.offsetParent !== null;
            el.disabled = !visible;
            el.required = visible;
        });
    };

    form.addEventListener('change', (e) => {
        if (!e.target.matches('[name="category_id"], select')) return;
        syncBranches();
        const other = e.target.closest('.branch')?.querySelector('.branch-other input');
        if (e.target.matches('select') && other && !other.disabled) other.focus();
    });

    /* ---------- Penghitung karakter ---------- */
    const fmt = new Intl.NumberFormat('id-ID');
    form.querySelectorAll('[data-char-count]').forEach((out) => {
        const field = document.getElementById(out.dataset.charCount);
        const update = () => { out.textContent = `${fmt.format(field.value.length)} / ${fmt.format(field.maxLength)}`; };
        field.addEventListener('input', update);
        update();
    });

    /* ---------- Lampiran: klik atau tarik-lepas ---------- */
    const fileInput = form.querySelector('input[type="file"]');
    const drop = form.querySelector('[data-drop]');
    const chip = form.querySelector('[data-file-chip]');
    const fileError = form.querySelector('[data-file-error]');
    const maxBytes = Number(form.dataset.maxKb) * 1024;
    const allowed = /\.(jpe?g|png|pdf)$/i;

    const humanSize = (bytes) => (bytes >= 1048576
        ? `${fmt.format(Math.round(bytes / 104857.6) / 10)} MB`
        : `${fmt.format(Math.max(1, Math.round(bytes / 1024)))} KB`);

    const renderFile = () => {
        const file = fileInput.files[0];
        let problem = '';
        if (file && !allowed.test(file.name)) problem = 'Lampiran harus berupa JPG, PNG, atau PDF.';
        else if (file && file.size > maxBytes) problem = `Ukuran lampiran maksimal ${form.dataset.maxLabel}.`;

        if (problem) fileInput.value = '';
        fileError.textContent = problem;
        fileError.hidden = !problem;

        const ok = fileInput.files[0];
        chip.hidden = !ok;
        if (ok) {
            chip.querySelector('[data-file-name]').textContent = ok.name;
            chip.querySelector('[data-file-size]').textContent = humanSize(ok.size);
        }
    };

    if (fileInput && drop && chip && fileError) {
        fileInput.addEventListener('change', renderFile);
        ['dragenter', 'dragover'].forEach((type) => drop.addEventListener(type, (e) => {
            e.preventDefault();
            drop.classList.add('is-over');
        }));
        ['dragleave', 'dragend', 'drop'].forEach((type) => drop.addEventListener(type, () => drop.classList.remove('is-over')));
        drop.addEventListener('drop', (e) => {
            e.preventDefault();
            if (!e.dataTransfer?.files.length) return;
            const dt = new DataTransfer();
            dt.items.add(e.dataTransfer.files[0]);
            fileInput.files = dt.files;
            renderFile();
        });
        chip.querySelector('[data-file-remove]').addEventListener('click', () => {
            fileInput.value = '';
            renderFile();
            fileInput.focus();
        });
    }

    /* ---------- Cegah kirim dobel ---------- */
    const submit = form.querySelector('[data-submit]');
    const submitLabel = submit.textContent;
    const resetSubmit = () => {
        delete form.dataset.sending;
        submit.disabled = false;
        submit.classList.remove('is-loading');
        submit.textContent = submitLabel;
    };

    form.addEventListener('submit', (e) => {
        if (form.dataset.sending) {
            e.preventDefault();
            return;
        }
        form.dataset.sending = '1';
        submit.disabled = true;
        submit.classList.add('is-loading');
        submit.textContent = submit.dataset.loadingLabel || 'Mengirim…';
    });

    // Kembali lewat tombol Back: halaman dari cache, tombol harus aktif lagi.
    window.addEventListener('pageshow', (e) => { if (e.persisted) resetSubmit(); });

    syncBranches();
})();
