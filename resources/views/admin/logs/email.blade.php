@extends('layouts.admin')

@section('title', 'Email log')

@section('content')
<header class="admin-head">
    <h1 class="page-title">Email log</h1>
    <p class="page-lede">Setiap email tiket yang dikirim. Email yang gagal bisa dikirim ulang setelah pengaturan SMTP diperbaiki.</p>
</header>

@error('email')
    <div class="alert alert--error reveal-in" role="alert">{{ $message }}</div>
@enderror

<details class="filters" open data-collapse-mobile>
    <summary class="filters-toggle">Filter</summary>
    <form class="filters-form" method="GET" action="{{ route('admin.logs.email') }}">
        <div class="filters-field">
            <label class="field-label" for="f-status">Status</label>
            <span class="select"><select id="f-status" name="status">
                <option value="">Semua</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select></span>
        </div>
        <div class="filters-field">
            <label class="field-label" for="f-tipe">Tipe</label>
            <span class="select"><select id="f-tipe" name="tipe">
                <option value="">Semua</option>
                @foreach ($types as $type)
                    <option value="{{ $type }}" @selected(($filters['tipe'] ?? '') === $type)>{{ $type }}</option>
                @endforeach
            </select></span>
        </div>
        <div class="filters-actions">
            <a class="btn btn--ghost btn--sm" href="{{ route('admin.logs.email') }}">Reset</a>
            <button type="submit" class="btn btn--primary btn--sm">Terapkan</button>
        </div>
    </form>
</details>

<div class="table-card">
    <table class="ticket-table">
        <thead>
            <tr><th scope="col">Waktu</th><th scope="col">Ke</th><th scope="col">Subjek</th><th scope="col">Tipe</th><th scope="col">Status</th><th scope="col">Percobaan</th><th scope="col"><span class="sr-only">Aksi</span></th></tr>
        </thead>
        <tbody>
            @forelse ($logs as $log)
                <tr class="trow">
                    <td class="trow-no"><time datetime="{{ $log->created_at?->toIso8601String() }}">{{ $log->created_at?->translatedFormat('d M Y, H.i') }}</time></td>
                    <td data-label="Ke">{{ $log->to_email }}</td>
                    <td data-label="Subjek">
                        <span class="cell-main">{{ $log->subject }}</span>
                        @if ($log->ticket)
                            <a class="cell-sub" href="{{ route('admin.tickets.show', $log->ticket) }}">{{ $log->ticket->ticket_no }}</a>
                        @endif
                        @if ($log->error_message)
                            <details class="json">
                                <summary>Lihat error</summary>
                                <pre>{{ $log->error_message }}</pre>
                            </details>
                        @endif
                    </td>
                    <td data-label="Tipe"><code class="code">{{ $log->type }}</code></td>
                    <td data-label="Status"><span class="badge {{ $log->status->badgeClass() }}">{{ $log->status->label() }}</span></td>
                    <td data-label="Percobaan">{{ $log->attempts }}</td>
                    <td class="cell-actions">
                        @if ($log->status === \App\Enums\EmailStatus::Failed)
                            <form method="POST" action="{{ route('admin.logs.email.retry', $log) }}">
                                @csrf
                                <button type="submit" class="btn btn--secondary btn--sm">Kirim ulang</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7"><x-empty-state title="Belum ada email yang cocok dengan filter ini." /></td></tr>
            @endforelse
        </tbody>
    </table>
</div>

{{ $logs->links('admin._pagination') }}
@endsection
