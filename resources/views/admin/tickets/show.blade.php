@extends('layouts.admin')

@section('title', $ticket->ticket_no)

@push('scripts')
    <script src="{{ asset('js/ticket-form.js') }}?v={{ config('app.asset_version') }}" defer></script>
@endpush

@section('content')
<a class="back-link" href="{{ route('admin.tickets.index') }}">Kembali ke daftar tiket</a>

<header class="detail-head">
    <div>
        <p class="detail-label">{{ $ticket->category->name }} · {{ $ticket->typeLabel() }}</p>
        <h1 class="detail-no">{{ $ticket->ticket_no }}</h1>
    </div>
    <div class="detail-badges">
        <x-status-badge :status="$ticket->status" :live="false" class="badge--lg" />
        <x-priority-badge :priority="$ticket->priority" class="badge--lg" />
        @if ($ticket->isOverdue())
            <span class="badge badge--lg badge--sla">Lewat SLA</span>
        @endif
    </div>
</header>

<div class="detail-grid">
    <section class="panel detail-info" aria-labelledby="info-title">
        <h2 id="info-title" class="section-title">Informasi</h2>
        <dl class="info-grid">
            <div><dt>Pemohon</dt><dd>{{ $ticket->requester_name }}</dd></div>
            <div><dt>Email</dt><dd><a href="mailto:{{ $ticket->requester_email }}">{{ $ticket->requester_email }}</a></dd></div>
            <div><dt>Masuk</dt><dd>{{ $ticket->created_at?->translatedFormat('d M Y, H.i') }}</dd></div>
            <div><dt>Posisi antrian</dt><dd>{{ $position ?? '-' }}</dd></div>
            <div><dt>Petugas</dt><dd>{{ $ticket->assignee->name ?? 'Belum ada' }}</dd></div>
            <div><dt>Respons pertama</dt><dd>{{ $ticket->first_response_at?->translatedFormat('d M Y, H.i') ?? 'Belum ada' }}</dd></div>
            <div><dt>Aktivitas terakhir</dt><dd>{{ $ticket->last_activity_at?->diffForHumans() ?? '-' }}</dd></div>
            <div><dt>IP pemohon</dt><dd>{{ $ticket->ip_address ?? '-' }}</dd></div>
        </dl>

        <h3 class="subsection-title">Deskripsi</h3>
        <div class="prose">{!! nl2br(e($ticket->description)) !!}</div>
        @foreach ($ticket->attachments->whereNull('comment_id') as $file)
            <a class="file-link" href="{{ $fileUrls[$file->id] }}">{{ $file->original_name }}</a>
        @endforeach
    </section>

    <aside class="detail-actions" aria-label="Aksi tiket">
        @if ($errors->any())
            <div class="alert alert--error reveal-in" role="alert">{{ $errors->first() }}</div>
        @endif

        <section class="action-card" aria-labelledby="act-status">
            <h2 id="act-status" class="action-title">Status</h2>
            @if ($ticket->status->isFinal())
                <p class="action-note">Tiket sudah {{ Str::lower($ticket->status->label()) }}. Status tidak bisa diubah lagi.</p>
            @else
                <form class="stack stack--sm" method="POST" action="{{ route('admin.tickets.status', $ticket) }}">
                    @csrf
                    @method('PATCH')
                    <x-field name="status" label="Ubah ke">
                        <span class="select"><select id="status" name="status">
                            @foreach ($ticket->status->allowedTransitions() as $to)
                                <option value="{{ $to->value }}" @selected(old('status') === $to->value)>{{ $to->label() }}</option>
                            @endforeach
                        </select></span>
                    </x-field>
                    <x-field name="note" type="textarea" label="Catatan" rows="3" maxlength="500"
                             hint="Wajib untuk Menunggu, Selesai, dan Dibatalkan. Catatan ikut terkirim ke pemohon." />
                    <div><button type="submit" class="btn btn--primary btn--sm">Simpan status</button></div>
                </form>
            @endif
        </section>

        @can('changePriority', $ticket)
            <section class="action-card" aria-labelledby="act-priority">
                <h2 id="act-priority" class="action-title">Prioritas</h2>
                <form class="action-inline" method="POST" action="{{ route('admin.tickets.priority', $ticket) }}">
                    @csrf
                    @method('PATCH')
                    <label class="sr-only" for="priority">Prioritas</label>
                    <span class="select"><select id="priority" name="priority">
                        @foreach (\App\Enums\TicketPriority::cases() as $priority)
                            <option value="{{ $priority->value }}" @selected($ticket->priority === $priority)>{{ $priority->label() }}</option>
                        @endforeach
                    </select></span>
                    <button type="submit" class="btn btn--secondary btn--sm">Simpan</button>
                </form>
            </section>
        @endcan

        <section class="action-card" aria-labelledby="act-assign">
            <h2 id="act-assign" class="action-title">Penanganan</h2>
            <p class="action-note">Petugas: <strong>{{ $ticket->assignee->name ?? 'belum ada' }}</strong></p>
            @can('take', $ticket)
                <form method="POST" action="{{ route('admin.tickets.take', $ticket) }}">
                    @csrf
                    <button type="submit" class="btn btn--secondary btn--sm">Ambil tiket</button>
                </form>
            @endcan
            @can('assign', $ticket)
                <form class="action-inline" method="POST" action="{{ route('admin.tickets.assign', $ticket) }}">
                    @csrf
                    @method('PATCH')
                    <label class="sr-only" for="assigned_to">Assign ke</label>
                    <span class="select"><select id="assigned_to" name="assigned_to">
                        <option value="">Belum di-assign</option>
                        @foreach ($agents as $agent)
                            <option value="{{ $agent->id }}" @selected($ticket->assigned_to === $agent->id)>{{ $agent->name }}</option>
                        @endforeach
                    </select></span>
                    <button type="submit" class="btn btn--secondary btn--sm">Assign</button>
                </form>
            @endcan
        </section>

        @can('comment', $ticket)
            <section class="action-card" aria-labelledby="act-comment">
                <h2 id="act-comment" class="action-title">Komentar</h2>
                <form class="stack stack--sm" method="POST" action="{{ route('admin.tickets.comments.store', $ticket) }}" enctype="multipart/form-data" data-ticket-form>
                    @csrf
                    <x-field name="body" type="textarea" label="Pesan" rows="4" maxlength="5000" required />
                    <fieldset class="choice-group">
                        <legend class="field-label">Kirim sebagai</legend>
                        {{-- Default internal: lupa memilih lebih aman daripada catatan internal terkirim ke pemohon. --}}
                        <label class="check"><input type="radio" name="visibility" value="internal" @checked(old('visibility', 'internal') === 'internal')> <span>Catatan internal</span></label>
                        <label class="check"><input type="radio" name="visibility" value="public" @checked(old('visibility') === 'public')> <span>Kirim ke pemohon</span></label>
                    </fieldset>
                    <x-field name="attachment" label="Lampiran" optional>
                        <input class="input input--file" type="file" id="attachment" name="attachment" accept=".jpg,.jpeg,.png,.pdf">
                    </x-field>
                    <div><button type="submit" class="btn btn--primary btn--sm" data-submit data-loading-label="Mengirim…">Kirim komentar</button></div>
                </form>
            </section>
        @endcan

        <section class="action-card" aria-labelledby="act-more">
            <h2 id="act-more" class="action-title">Lainnya</h2>
            <div class="action-inline">
                <form method="POST" action="{{ route('admin.tickets.send-link', $ticket) }}">
                    @csrf
                    <button type="submit" class="btn btn--secondary btn--sm">Kirim ulang link tracking</button>
                </form>
                @can('delete', $ticket)
                    <form method="POST" action="{{ route('admin.tickets.destroy', $ticket) }}" data-confirm="delete-dialog">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn--ghost btn--sm btn--danger-text">Hapus tiket</button>
                    </form>
                @endcan
            </div>
        </section>
    </aside>

    <section class="panel detail-timeline" aria-labelledby="timeline-title">
        <h2 id="timeline-title" class="section-title">Timeline</h2>
        <x-timeline :items="$timeline" :file-urls="$fileUrls" />
    </section>
</div>

@can('delete', $ticket)
    <dialog class="dialog" id="delete-dialog" aria-labelledby="delete-title">
        <h2 id="delete-title" class="dialog-title">Hapus tiket {{ $ticket->ticket_no }}?</h2>
        <p class="dialog-text">Tiket hilang dari antrian dan panel admin. Riwayatnya tetap tersimpan di audit log.</p>
        <div class="dialog-actions">
            <form method="dialog"><button class="btn btn--secondary">Batal</button></form>
            <button type="button" class="btn btn--danger" data-confirm-accept>Hapus tiket</button>
        </div>
    </dialog>
@endcan
@endsection
