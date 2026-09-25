@extends('layouts.public')

@section('title', 'Tiket '.$ticket->ticket_no)

@push('scripts')
    <script src="{{ asset('js/ticket-form.js') }}?v={{ config('app.asset_version') }}" defer></script>
@endpush

@section('content')
<div class="track">
    <section class="track-summary rise" aria-labelledby="track-no">
        <div class="track-summary-top">
            <div>
                <span class="track-label">Nomor tiket</span>
                <h1 id="track-no" class="track-no">{{ $ticket->ticket_no }}</h1>
            </div>
            <x-status-badge :status="$ticket->status" class="badge--lg" />
        </div>

        <dl class="track-meta">
            <div class="track-meta-pos">
                <dt>Posisi antrian</dt>
                <dd>{{ $position ?? '-' }}</dd>
            </div>
            <div>
                <dt>Prioritas</dt>
                <dd><x-priority-badge :priority="$ticket->priority" /></dd>
            </div>
            <div>
                <dt>Kategori</dt>
                <dd>{{ $ticket->category->name }}</dd>
            </div>
            <div>
                <dt>{{ $ticket->typeFieldLabel() }}</dt>
                <dd>{{ $ticket->typeLabel() }}</dd>
            </div>
            <div>
                <dt>Masuk</dt>
                <dd>{{ $ticket->created_at?->translatedFormat('d M Y, H.i') }}</dd>
            </div>
            <div>
                <dt>Ditangani oleh</dt>
                <dd>{{ $ticket->assignee->name ?? 'Belum ada' }}</dd>
            </div>
        </dl>
    </section>

    @error('status')
        <div class="alert alert--error reveal-in" role="alert">{{ $message }}</div>
    @enderror

    <div class="track-grid">
        <section class="track-main rise i-1" aria-labelledby="desc-title">
            <h2 id="desc-title" class="section-title">Deskripsi awal</h2>
            <div class="prose">{!! nl2br(e($ticket->description)) !!}</div>
            @foreach ($requesterFiles as $file)
                <a class="file-link" href="{{ $fileUrls[$file->id] }}">{{ $file->original_name }}</a>
            @endforeach

            <h2 class="section-title section-title--spaced">Riwayat</h2>
            <x-timeline :items="$timeline" :file-urls="$fileUrls" />
        </section>

        <aside class="track-side rise i-2" aria-labelledby="reply-title">
            <h2 id="reply-title" class="section-title">Balas tiket</h2>

            @if ($ticket->status->isFinal())
                <p class="track-closed">Tiket ini sudah {{ Str::lower($ticket->status->label()) }}, jadi tidak bisa dibalas lagi. Buat tiket baru kalau kendalanya muncul lagi.</p>
                <a class="btn btn--secondary" href="{{ route('tickets.create') }}">Buat tiket baru</a>
            @else
                <form class="stack" method="POST" action="{{ $replyUrl }}" enctype="multipart/form-data"
                      data-ticket-form data-max-kb="{{ config('helpdesk.max_upload_kb') }}"
                      data-max-label="{{ \Illuminate\Support\Number::fileSize(config('helpdesk.max_upload_kb') * 1024) }}">
                    @csrf
                    <x-field name="body" type="textarea" label="Pesan" rows="4" maxlength="5000" required
                             hint="Tambahkan info baru, jawab pertanyaan tim IT, atau kabari kalau sudah beres." />

                    <x-field name="attachment" label="Lampiran" optional>
                        <label class="drop drop--compact" data-drop>
                            <svg class="drop-icon" viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M12 16V4m0 0-4.5 4.5M12 4l4.5 4.5M4 15v3a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-3"/>
                            </svg>
                            <span class="drop-text"><strong>Pilih file</strong><small>JPG, PNG, atau PDF</small></span>
                            <input type="file" id="attachment" name="attachment" class="sr-only" accept=".jpg,.jpeg,.png,.pdf"
                                   @error('attachment') aria-invalid="true" aria-describedby="attachment-error" @enderror>
                        </label>
                        <div class="file-chip reveal-in" data-file-chip hidden>
                            <span class="file-chip-name" data-file-name></span>
                            <span class="file-chip-size" data-file-size></span>
                            <button type="button" class="btn btn--ghost btn--sm" data-file-remove>Hapus</button>
                        </div>
                        <p class="field-error" data-file-error role="alert" hidden></p>
                    </x-field>

                    <button type="submit" class="btn btn--primary shine" data-submit data-loading-label="Mengirim balasan…">Kirim balasan</button>
                </form>
            @endif

            @if ($ticket->status === \App\Enums\TicketStatus::Baru)
                <form class="track-cancel" method="POST" action="{{ $cancelUrl }}" data-confirm="cancel-dialog">
                    @csrf
                    <p>Kendalanya sudah beres sendiri?</p>
                    <button type="submit" class="btn btn--secondary btn--sm">Batalkan tiket</button>
                </form>

                <dialog class="dialog" id="cancel-dialog" aria-labelledby="cancel-title">
                    <h2 id="cancel-title" class="dialog-title">Batalkan tiket ini?</h2>
                    <p class="dialog-text">Tiket yang dibatalkan tidak bisa dibuka lagi. Buat tiket baru kalau kendalanya muncul lagi.</p>
                    <div class="dialog-actions">
                        <form method="dialog"><button class="btn btn--secondary">Kembali</button></form>
                        <button type="button" class="btn btn--danger" data-confirm-accept>Ya, batalkan</button>
                    </div>
                </dialog>
            @endif
        </aside>
    </div>
</div>
@endsection
