// Modal balasan di daftar tiket admin: klik nomor/baris tiket → modal status + deskripsi + lampiran.
// Tanpa JavaScript (atau Ctrl/Cmd+klik), link nomor tiket tetap membuka halaman detail.
(() => {
    const dialog = document.querySelector('[data-respond-dialog]');
    if (!dialog || typeof dialog.showModal !== 'function') return;

    const form = dialog.querySelector('[data-respond-form]');
    const submit = form.querySelector('[data-respond-submit]');
    const each = (key, fn) => dialog.querySelectorAll(`[data-r="${key}"]`).forEach(fn);
    const text = (key, value) => each(key, (el) => { el.textContent = value ?? '-'; });

    function fill(data, keepInput) {
        if (!keepInput) {
            // reset() mengembalikan isian ke nilai awal HTML (old input tiket lain), jadi dikosongkan manual.
            form.reset();
            form.querySelector('textarea').value = '';
            dialog.querySelectorAll('.field-error').forEach((el) => el.remove());
            dialog.querySelectorAll('[aria-invalid]').forEach((el) => el.removeAttribute('aria-invalid'));
            dialog.querySelectorAll('.has-error').forEach((el) => el.classList.remove('has-error'));
        }

        ['no', 'type', 'requester', 'email', 'created', 'description'].forEach((key) => text(key, data[key]));
        each('status', (el) => { el.className = `badge badge--dot ${data.badge}`; el.textContent = data.statusLabel; });
        each('detail', (el) => { el.href = data.detail; });
        each('id', (el) => { el.value = data.id; });
        each('final', (el) => { el.hidden = !data.final; });
        // Tiket final: hanya info + link riwayat lengkap, tanpa isian.
        each('fields', (el) => { el.hidden = data.final; });
        each('submit', (el) => { el.hidden = data.final; });
        each('files', (list) => {
            list.replaceChildren(...data.files.map((file) => {
                const item = document.createElement('li');
                const link = document.createElement('a');
                link.className = 'file-link';
                link.href = file.url;
                link.textContent = file.name;
                item.append(link);
                return item;
            }));
            list.hidden = data.files.length === 0;
        });

        form.action = data.action;
        // Kontrol yang tersembunyi juga dinonaktifkan agar tidak ikut terkirim.
        form.querySelectorAll('textarea, input[type="file"], [data-respond-submit]').forEach((el) => { el.disabled = data.final; });

        form.querySelectorAll('input[name="status"]').forEach((radio) => {
            radio.disabled = data.final || !data.allowed.includes(radio.value);
            radio.closest('label').title = radio.disabled ? `Tidak bisa dari status ${data.statusLabel}` : '';
            // Default: status saat ini jika termasuk pilihan (misal Diproses → tinggal tulis balasan).
            if (!keepInput) radio.checked = radio.value === data.status && !radio.disabled;
        });

        submit.classList.remove('is-loading');
        submit.querySelector('[data-label]').textContent = 'Kirim balasan';
    }

    function open(row, keepInput) {
        const data = JSON.parse(row.dataset.ticket);
        data.id = row.dataset.ticketId;
        fill(data, keepInput);
        dialog.showModal();
        const checked = form.querySelector('input[name="status"]:checked');
        const target = data.final
            ? dialog.querySelector('[data-respond-close]')
            : (checked ? form.querySelector('textarea') : form.querySelector('input[name="status"]:not(:disabled)'));
        target.focus();
    }

    document.addEventListener('click', (event) => {
        const row = event.target.closest('tr[data-ticket]');
        if (!row || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
        event.preventDefault();
        open(row, false);
    });

    dialog.querySelectorAll('[data-respond-close]').forEach((button) => button.addEventListener('click', () => dialog.close()));
    // Klik di luar kotak (area backdrop) menutup modal.
    dialog.addEventListener('click', (event) => { if (event.target === dialog) dialog.close(); });

    form.addEventListener('submit', () => {
        submit.classList.add('is-loading');
        submit.querySelector('[data-label]').textContent = 'Mengirim…';
        // Dinonaktifkan setelah submit berjalan agar tidak terkirim dua kali.
        setTimeout(() => { submit.disabled = true; }, 0);
    });

    // Validasi gagal: buka lagi modal tiket yang sama, isian lama tetap ada.
    const reopen = dialog.dataset.reopen;
    const row = reopen ? document.querySelector(`tr[data-ticket-id="${CSS.escape(reopen)}"]`) : null;
    if (row) open(row, true);
})();
