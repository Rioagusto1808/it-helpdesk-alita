{{-- Notifikasi: sukses hilang sendiri dalam 5 detik (bar hitung mundur), error tetap tampil sampai ditutup. --}}
@if (session('toast') || session('error'))
    <div class="toast-stack">
        @if (session('toast'))
            <div class="toast toast--success" role="status">
                <span class="toast-icon"><x-icon name="check" /></span>
                <p>{{ session('toast') }}</p>
                <button type="button" class="toast-close" data-toast-close aria-label="Tutup notifikasi"><x-icon name="x" class="icon--sm" /></button>
                <span class="toast-progress" aria-hidden="true"></span>
            </div>
        @endif
        @if (session('error'))
            <div class="toast toast--error toast--sticky" role="alert">
                <span class="toast-icon"><x-icon name="alert" /></span>
                <p>{{ session('error') }}</p>
                <button type="button" class="toast-close" data-toast-close aria-label="Tutup notifikasi"><x-icon name="x" class="icon--sm" /></button>
            </div>
        @endif
    </div>
@endif
