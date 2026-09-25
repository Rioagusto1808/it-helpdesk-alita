@extends('layouts.public')

@section('title', 'Tiket '.$ticket->ticket_no)

@push('scripts')
    <script src="{{ asset('js/ticket-form.js') }}?v={{ config('app.asset_version') }}" defer></script>
@endpush

@use('App\Enums\TicketStatus')

@section('content')
@php
    $status = $ticket->status;
    $cancelled = $status === TicketStatus::Dibatalkan;
    $closed = $status === TicketStatus::Ditutup;
    $current = match ($status) {
        TicketStatus::Diproses, TicketStatus::Menunggu => 1,
        TicketStatus::Selesai => 2,
        TicketStatus::Ditutup => 3,
        default => 0,
    };
    $steps = ['Baru', 'Diproses', 'Selesai', 'Ditutup'];
@endphp
<div class="track">
    <section class="track-summary card card--accent rise" aria-labelledby="track-no">
        <div class="track-summary-top">
            <div>
                <span class="track-label">Nomor tiket</span>
                <h1 id="track-no" class="track-no">{{ $ticket->ticket_no }}</h1>
            </div>
            <x-status-badge :status="$ticket->status" class="badge--lg" />
        </div>

        <ol class="stepper stepper--{{ $cancelled ? 0 : $current }}" aria-label="Progres tiket">
            @foreach ($steps as $i => $label)
                @php
                    $done = ! $cancelled && ($i < $current || ($closed && $i === $current));
                    $isCurrent = ! $cancelled && ! $closed && $i === $current;
                @endphp
                <li @class(['step', 'is-done' => $done, 'is-current' => $isCurrent, 'is-paused' => $isCurrent && $status === TicketStatus::Menunggu])
                    @if ($isCurrent) aria-current="step" @endif>
                    <span class="step-dot" aria-hidden="true">
                        @if ($done)
                            <x-icon name="check" />
                        @elseif ($isCurrent && $status === TicketStatus::Menunggu)
                            <x-icon name="clock" />
                        @else
                            {{ $i + 1 }}
                        @endif
                    </span>
                    <span class="step-label">{{ $isCurrent && $status === TicketStatus::Menunggu ? 'Menunggu' : $label }}</span>
                </li>
            @endforeach
        </ol>
        @if ($cancelled)
            <p class="stepper-note"><x-icon name="x" class="icon--sm" /> Tiket ini sudah dibatalkan.</p>
        @elseif ($status === TicketStatus::Menunggu)
            <p class="stepper-note"><x-icon name="clock" class="icon--sm" /> Tim IT sedang menunggu info atau tindakan tambahan. Cek riwayat di bawah.</p>
        @endif

        <dl class="track-meta">
            <div class="track-meta-pos">
                <dt><x-icon name="list" /> Posisi antrian</dt>
                <dd>@if ($position)<span data-count-to="{{ $position }}">{{ $position }}</span>@else - @endif</dd>
            </div>
            <div>
                <dt><x-icon name="bolt" /> Prioritas</dt>
                <dd><x-priority-badge :priority="$ticket->priority" /></dd>
            </div>
            <div>
                <dt><x-icon name="grid" /> Kategori</dt>
                <dd>{{ $ticket->category->name }}</dd>
            </div>
            <div>
                <dt><x-icon name="{{ $ticket->typeFieldLabel() === 'Modul' ? 'puzzle' : 'server' }}" /> {{ $ticket->typeFieldLabel() }}</dt>
                <dd>{{ $ticket->typeLabel() }}</dd>
            </div>
            <div>
                <dt><x-icon name="clock" /> Masuk</dt>
                <dd>{{ $ticket->created_at?->translatedFormat('d M Y, H.i') }}</dd>
            </div>
            <div>
                <dt><x-icon name="user" /> Ditangani oleh</dt>
                <dd>{{ $ticket->assignee->name ?? 'Belum ada' }}</dd>
            </div>
        </dl>
    </section>

    @error('status')
        <div class="alert alert--error reveal-in" role="alert"><x-icon name="alert" /> {{ $message }}</div>
    @enderror

    <div class="track-grid">
        <section class="track-main card reveal" aria-labelledby="desc-title">
            <h2 id="desc-title" class="section-title">Deskripsi awal</h2>
            <div class="prose">{!! nl2br(e($ticket->description)) !!}</div>
            @foreach ($requesterFiles as $file)
                <a class="file-link" href="{{ $fileUrls[$file->id] }}">{{ $file->original_name }}</a>
            @endforeach

            <h2 class="section-title section-title--spaced">Riwayat</h2>
            <x-timeline :items="$timeline" :file-urls="$fileUrls" />
        </section>

        <aside class="track-side card reveal i-1" aria-labelledby="reply-title">
            <h2 id="reply-title" class="section-title">Balas tiket</h2>

            @if ($ticket->status->isFinal())
                <x-empty-state :title="'Tiket ini sudah '.Str::lower($ticket->status->label()).', jadi tidak bisa dibalas lagi. Buat tiket baru kalau kendalanya muncul lagi.'" icon="lock" class="track-closed">
                    <a class="btn btn--secondary btn--sm" href="{{ route('tickets.create') }}"><x-icon name="plus" /> Buat tiket baru</a>
                </x-empty-state>
            @else
                <form class="stack" method="POST" action="{{ $replyUrl }}" enctype="multipart/form-data"
                      data-ticket-form data-max-kb="{{ config('helpdesk.max_upload_kb') }}"
                      data-max-label="{{ \Illuminate\Support\Number::fileSize(config('helpdesk.max_upload_kb') * 1024) }}">
                    @csrf
                    <x-field name="body" type="textarea" label="Pesan" rows="4" maxlength="5000" required
                             hint="Tambahkan info baru, jawab pertanyaan tim IT, atau kabari kalau sudah beres." />

                    <x-field name="attachment" label="Lampiran" optional>
                        <label class="drop drop--compact" data-drop>
                            <span class="drop-icon" aria-hidden="true"><x-icon name="upload" /></span>
                            <span class="drop-text"><strong><span class="drop-link">Pilih file</span></strong><small>JPG, PNG, atau PDF</small></span>
                            <input type="file" id="attachment" name="attachment" class="sr-only" accept=".jpg,.jpeg,.png,.pdf"
                                   @error('attachment') aria-invalid="true" aria-describedby="attachment-error" @enderror>
                        </label>
                        <div class="file-chip reveal-in" data-file-chip hidden>
                            <span class="file-chip-icon" aria-hidden="true"><x-icon name="file" /></span>
                            <span class="file-chip-name" data-file-name></span>
                            <span class="file-chip-size" data-file-size></span>
                            <button type="button" class="btn btn--ghost btn--sm" data-file-remove>Hapus</button>
                        </div>
                        <p class="field-error" data-file-error role="alert" hidden></p>
                    </x-field>

                    <button type="submit" class="btn btn--primary btn--block shine" data-submit data-loading-label="Mengirim balasan…">
                        <span data-label>Kirim balasan</span> <x-icon name="send" class="btn-arrow" />
                    </button>
                </form>
            @endif

            @if ($ticket->status === TicketStatus::Baru)
                <form class="track-cancel" method="POST" action="{{ $cancelUrl }}" data-confirm="cancel-dialog">
                    @csrf
                    <p>Kendalanya sudah beres sendiri?</p>
                    <button type="submit" class="btn btn--secondary btn--sm">Batalkan tiket</button>
                </form>

                <dialog class="dialog" id="cancel-dialog" aria-labelledby="cancel-title">
                    <span class="dialog-icon" aria-hidden="true"><x-icon name="alert" /></span>
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
