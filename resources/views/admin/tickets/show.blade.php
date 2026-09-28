@extends('layouts.admin')

@section('title', $ticket->ticket_no)

@section('content')
<a class="back-link" href="{{ route('admin.tickets.index') }}"><x-icon name="arrow-left" class="icon--sm" /> Kembali ke daftar tiket</a>

<header class="detail-head rise">
    <div>
        <p class="detail-label"><x-icon name="{{ $ticket->typeFieldLabel() === 'Modul' ? 'apps' : 'laptop' }}" class="icon--sm" /> {{ $ticket->category->name }} · {{ $ticket->typeLabel() }}</p>
        <h1 class="detail-no">{{ $ticket->ticket_no }}</h1>
    </div>
    <div class="detail-badges">
        <x-status-badge :status="$ticket->status" :live="false" class="badge--lg" />
        <x-priority-badge :priority="$ticket->priority" class="badge--lg" />
        @if ($ticket->isOverdue())
            <span class="badge badge--lg badge--sla badge--dot">Lewat SLA</span>
        @endif
    </div>
</header>

<div class="detail-grid">
    <section class="panel detail-info rise i-1" aria-labelledby="info-title">
        <h2 id="info-title" class="panel-title"><span class="panel-title-icon"><x-icon name="info" /></span> Informasi</h2>
        <dl class="info-grid">
            <div><dt><x-icon name="user" /> Pemohon</dt><dd>{{ $ticket->requester_name }}</dd></div>
            <div><dt><x-icon name="mail" /> Email</dt><dd><a href="mailto:{{ $ticket->requester_email }}">{{ $ticket->requester_email }}</a></dd></div>
            <div><dt><x-icon name="clock" /> Masuk</dt><dd>{{ $ticket->created_at?->translatedFormat('d M Y, H.i') }}</dd></div>
            <div><dt><x-icon name="list" /> Posisi antrian</dt><dd>{{ $position ?? '-' }}</dd></div>
            <div><dt><x-icon name="hand" /> Petugas</dt><dd>{{ $ticket->assignee->name ?? 'Belum ada' }}</dd></div>
            <div><dt><x-icon name="bolt" /> Respons pertama</dt><dd>{{ $ticket->first_response_at?->translatedFormat('d M Y, H.i') ?? 'Belum ada' }}</dd></div>
            <div><dt><x-icon name="refresh" /> Aktivitas terakhir</dt><dd>{{ $ticket->last_activity_at?->diffForHumans() ?? '-' }}</dd></div>
            <div><dt><x-icon name="shield" /> IP pemohon</dt><dd>{{ $ticket->ip_address ?? '-' }}</dd></div>
        </dl>

        <h3 class="subsection-title">Deskripsi</h3>
        <div class="prose">{!! nl2br(e($ticket->description)) !!}</div>
        @foreach ($ticket->attachments->whereNull('comment_id') as $file)
            <a class="file-link" href="{{ $fileUrls[$file->id] }}">{{ $file->original_name }}</a>
        @endforeach
    </section>

    <aside class="detail-actions rise i-2" aria-label="Aksi tiket">
        <section class="action-card" aria-labelledby="act-respond">
            <h2 id="act-respond" class="action-title"><x-icon name="send" /> Balas tiket</h2>
            @if ($ticket->status->isFinal())
                <p class="action-note">Tiket sudah {{ Str::lower($ticket->status->label()) }}, jadi tidak bisa dibalas lagi.</p>
            @else
                <form class="respond-form" method="POST" action="{{ route('admin.tickets.respond', $ticket) }}" enctype="multipart/form-data">
                    @csrf
                    @include('admin.tickets._respond-fields', ['ticket' => $ticket])
                    <div>
                        <button type="submit" class="btn btn--primary btn--block shine"><x-icon name="send" /> Kirim balasan</button>
                    </div>
                </form>
            @endif
        </section>
    </aside>

    <section class="panel detail-timeline reveal" aria-labelledby="timeline-title">
        <h2 id="timeline-title" class="panel-title"><span class="panel-title-icon"><x-icon name="clock" /></span> Timeline</h2>
        <x-timeline :items="$timeline" :file-urls="$fileUrls" />
    </section>
</div>
@endsection
