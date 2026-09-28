{{--
    Modal balasan tim IT. Satu dialog untuk semua baris; isinya diisi public/js/ticket-respond.js dari data-ticket.
    Setelah validasi gagal, data-reopen membuka lagi modal tiket yang sama dengan isian lama.
    Tanpa JavaScript, nomor tiket tetap membuka halaman detail.
--}}
<dialog class="dialog respond" id="respond-dialog" aria-labelledby="respond-title" data-respond-dialog data-reopen="{{ old('ticket_id') }}">
    <div class="respond-body">
        <header class="respond-head">
            <div class="respond-head-main">
                <p class="respond-type" data-r="type"></p>
                <h2 class="respond-title" id="respond-title" data-r="no"></h2>
            </div>
            <span class="badge badge--dot" data-r="status"></span>
            <button type="button" class="btn btn--ghost btn--icon" data-respond-close aria-label="Tutup"><x-icon name="x" /></button>
        </header>

        <dl class="respond-meta">
            <div><dt><x-icon name="user" /> Pemohon</dt><dd data-r="requester"></dd></div>
            <div><dt><x-icon name="mail" /> Email</dt><dd data-r="email"></dd></div>
            <div><dt><x-icon name="clock" /> Masuk</dt><dd data-r="created"></dd></div>
        </dl>

        <section class="respond-desc" aria-label="Deskripsi kendala">
            <p class="respond-label">Deskripsi kendala</p>
            <p class="respond-desc-text" data-r="description"></p>
            <ul class="respond-files" data-r="files" hidden></ul>
        </section>

        <p class="alert alert--info" data-r="final" hidden><x-icon name="info" /> Tiket ini sudah ditutup, jadi tidak bisa dibalas lagi.</p>

        <form class="respond-form" method="POST" action="" enctype="multipart/form-data" data-respond-form>
            @csrf
            <input type="hidden" name="ticket_id" value="{{ old('ticket_id') }}" data-r="id">

            <div class="respond-fields" data-r="fields">
                @include('admin.tickets._respond-fields', ['ticket' => null])
            </div>

            <div class="respond-actions">
                <a class="btn btn--ghost btn--sm" href="#" data-r="detail"><x-icon name="list" class="icon--sm" /> Riwayat lengkap</a>
                <span class="respond-actions-main">
                    <button type="button" class="btn btn--secondary" data-respond-close>Tutup</button>
                    <button type="submit" class="btn btn--primary" data-respond-submit data-r="submit"><x-icon name="send" /> <span data-label>Kirim balasan</span></button>
                </span>
            </div>
        </form>
    </div>
</dialog>
