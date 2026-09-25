@extends('layouts.admin')

@section('title', 'Audit log')

@section('content')
<header class="admin-head">
    <h1 class="page-title">Audit log</h1>
    <p class="page-lede">Semua aksi yang mengubah data atau terkait keamanan. Hanya bisa dibaca.</p>
</header>

<details class="filters" open data-collapse-mobile>
    <summary class="filters-toggle">Filter</summary>
    <form class="filters-form" method="GET" action="{{ route('admin.logs.activity') }}">
        <div class="filters-field">
            <label class="field-label" for="f-aksi">Aksi</label>
            <span class="select"><select id="f-aksi" name="aksi">
                <option value="">Semua</option>
                @foreach ($actions as $action)
                    <option value="{{ $action }}" @selected(($filters['aksi'] ?? '') === $action)>{{ $action }}</option>
                @endforeach
            </select></span>
        </div>
        <div class="filters-field">
            <label class="field-label" for="f-pelaku">Pelaku</label>
            <span class="select"><select id="f-pelaku" name="pelaku">
                <option value="">Semua</option>
                <option value="sistem" @selected(($filters['pelaku'] ?? '') === 'sistem')>Pemohon / sistem</option>
                @foreach ($actors as $actor)
                    <option value="{{ $actor->id }}" @selected(($filters['pelaku'] ?? '') === (string) $actor->id)>{{ $actor->name }}</option>
                @endforeach
            </select></span>
        </div>
        <div class="filters-field">
            <label class="field-label" for="f-tiket">Nomor tiket</label>
            <input class="input" type="search" id="f-tiket" name="tiket" maxlength="30" value="{{ $filters['tiket'] ?? '' }}">
        </div>
        <div class="filters-field">
            <label class="field-label" for="f-dari">Dari</label>
            <input class="input" type="date" id="f-dari" name="dari" value="{{ $filters['dari'] ?? '' }}">
        </div>
        <div class="filters-field">
            <label class="field-label" for="f-sampai">Sampai</label>
            <input class="input" type="date" id="f-sampai" name="sampai" value="{{ $filters['sampai'] ?? '' }}">
        </div>
        <div class="filters-actions">
            <a class="btn btn--ghost btn--sm" href="{{ route('admin.logs.activity') }}">Reset</a>
            <button type="submit" class="btn btn--primary btn--sm">Terapkan</button>
        </div>
    </form>
</details>

<div class="table-card">
    <table class="ticket-table">
        <thead>
            <tr><th scope="col">Waktu</th><th scope="col">Pelaku</th><th scope="col">Aksi</th><th scope="col">Subjek</th><th scope="col">IP</th><th scope="col">Detail</th></tr>
        </thead>
        <tbody>
            @forelse ($logs as $log)
                <tr class="trow">
                    <td class="trow-no"><time datetime="{{ $log->created_at?->toIso8601String() }}">{{ $log->created_at?->translatedFormat('d M Y, H.i.s') }}</time></td>
                    <td data-label="Pelaku">{{ $log->actor_label }}</td>
                    <td data-label="Aksi"><code class="code">{{ $log->action }}</code></td>
                    <td data-label="Subjek">
                        @if ($log->subject instanceof \App\Models\Ticket)
                            @if ($log->subject->trashed())
                                {{ $log->subject->ticket_no }} <span class="cell-sub">dihapus</span>
                            @else
                                <a href="{{ route('admin.tickets.show', $log->subject) }}">{{ $log->subject->ticket_no }}</a>
                            @endif
                        @elseif ($log->subject)
                            {{ $log->subject->name ?? $log->subject->to_email ?? class_basename($log->subject).' #'.$log->subject_id }}
                        @else
                            -
                        @endif
                    </td>
                    <td data-label="IP">{{ $log->ip_address ?? '-' }}</td>
                    <td data-label="Detail">
                        @if ($log->properties)
                            <details class="json">
                                <summary>Lihat</summary>
                                <pre>{{ json_encode($log->properties, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                            </details>
                        @else
                            -
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6"><x-empty-state title="Belum ada catatan yang cocok dengan filter ini." /></td></tr>
            @endforelse
        </tbody>
    </table>
</div>

{{ $logs->links('admin._pagination') }}
@endsection
