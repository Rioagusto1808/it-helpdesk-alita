@extends('layouts.admin')

@section('title', 'Tiket')

@push('scripts')
    <script src="{{ asset('js/ticket-respond.js') }}?v={{ config('app.asset_version') }}" defer></script>
@endpush

@section('content')
<header class="admin-head admin-head--row">
    <div>
        <h1 class="page-title">Tiket</h1>
        <p class="page-lede">{{ $tickets->total() }} tiket sesuai filter</p>
    </div>
    @can('export', \App\Models\Ticket::class)
        <a class="btn btn--secondary btn--sm" href="{{ route('admin.tickets.export', request()->query()) }}"><x-icon name="download" class="icon--sm" /> Export CSV</a>
    @endcan
</header>

<details class="filters" @if (collect(request()->except('page'))->filter()->isNotEmpty()) open @endif data-collapse-mobile>
    <summary class="filters-toggle"><x-icon name="filter" class="icon--sm" /> Filter dan urutan</summary>
    <form class="filters-form" method="GET" action="{{ route('admin.tickets.index') }}">
        <div class="filters-search">
            <label class="field-label" for="cari">Cari</label>
            <span class="input-wrap has-icon"><x-icon name="search" class="input-icon" /><input class="input" type="search" id="cari" name="cari" value="{{ $filters['cari'] ?? '' }}" maxlength="100" placeholder="Nomor, nama, email, atau isi deskripsi"></span>
        </div>

        <div class="filters-field">
            <label class="field-label" for="f-status">Status</label>
            <span class="select"><select id="f-status" name="status">
                <option value="aktif" @selected(($filters['status'] ?? 'aktif') === 'aktif')>Semua yang aktif</option>
                <option value="semua" @selected(($filters['status'] ?? '') === 'semua')>Semua status</option>
                @foreach ($options['statuses'] as $status)
                    <option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select></span>
        </div>

        <div class="filters-field">
            <label class="field-label" for="f-kategori">Kategori</label>
            <span class="select"><select id="f-kategori" name="kategori">
                <option value="">Semua</option>
                @foreach ($options['categories'] as $category)
                    <option value="{{ $category->code }}" @selected(($filters['kategori'] ?? '') === $category->code)>{{ $category->name }}</option>
                @endforeach
            </select></span>
        </div>

        <div class="filters-field">
            <label class="field-label" for="f-jenis">Layanan/modul</label>
            <span class="select"><select id="f-jenis" name="jenis">
                <option value="">Semua</option>
                <optgroup label="Layanan (ITInfra)">
                    @foreach ($options['services'] as $service)
                        <option value="s:{{ $service->id }}" @selected(($filters['jenis'] ?? '') === 's:'.$service->id)>{{ $service->name }}</option>
                    @endforeach
                </optgroup>
                <optgroup label="Modul (ITApps)">
                    @foreach ($options['modules'] as $module)
                        <option value="m:{{ $module->id }}" @selected(($filters['jenis'] ?? '') === 'm:'.$module->id)>{{ $module->name }}</option>
                    @endforeach
                </optgroup>
            </select></span>
        </div>

        <div class="filters-field">
            <label class="field-label" for="f-prioritas">Prioritas</label>
            <span class="select"><select id="f-prioritas" name="prioritas">
                <option value="">Semua</option>
                @foreach ($options['priorities'] as $priority)
                    <option value="{{ $priority->value }}" @selected(($filters['prioritas'] ?? '') === $priority->value)>{{ $priority->label() }}</option>
                @endforeach
            </select></span>
        </div>

        <div class="filters-field">
            <label class="field-label" for="f-petugas">Petugas</label>
            <span class="select"><select id="f-petugas" name="petugas">
                <option value="">Semua</option>
                <option value="none" @selected(($filters['petugas'] ?? '') === 'none')>Belum di-assign</option>
                <option value="me" @selected(($filters['petugas'] ?? '') === 'me')>Saya</option>
                @foreach ($options['agents'] as $agent)
                    <option value="{{ $agent->id }}" @selected(($filters['petugas'] ?? '') === (string) $agent->id)>{{ $agent->name }}</option>
                @endforeach
            </select></span>
        </div>

        <div class="filters-field">
            <label class="field-label" for="f-dari">Masuk dari</label>
            <input class="input" type="date" id="f-dari" name="dari" value="{{ $filters['dari'] ?? '' }}">
        </div>

        <div class="filters-field">
            <label class="field-label" for="f-sampai">Sampai</label>
            <input class="input" type="date" id="f-sampai" name="sampai" value="{{ $filters['sampai'] ?? '' }}">
        </div>

        <div class="filters-field">
            <label class="field-label" for="f-urut">Urutkan</label>
            <span class="select"><select id="f-urut" name="urut">
                @foreach ($options['sorts'] as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['urut'] ?? 'antrian') === $value)>{{ $label }}</option>
                @endforeach
            </select></span>
        </div>

        <div class="filters-actions">
            <label class="check">
                <input type="checkbox" name="sla" value="1" @checked($filters['sla'] ?? false)>
                <span>Hanya lewat SLA</span>
            </label>
            <a class="btn btn--ghost btn--sm" href="{{ route('admin.tickets.index') }}">Reset</a>
            <button type="submit" class="btn btn--primary btn--sm"><x-icon name="check" class="icon--sm" /> Terapkan</button>
        </div>
    </form>
</details>

<div class="table-card">
    <table class="ticket-table ticket-table--list">
        <caption class="sr-only">Daftar tiket. Pilih nomor tiket untuk membalas.</caption>
        <thead>
            <tr>
                <th scope="col">Tiket</th>
                <th scope="col">Pemohon</th>
                <th scope="col">Status</th>
                <th scope="col">Prioritas</th>
                <th scope="col">Petugas</th>
                <th scope="col">Masuk</th>
                <th scope="col"><span class="sr-only">Aksi</span></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($tickets as $ticket)
                <tr class="trow" data-ticket-id="{{ $ticket->id }}" data-ticket="{{ json_encode($respondData[$ticket->id], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}">
                    <td class="trow-no">
                        <a class="trow-link" href="{{ route('admin.tickets.show', $ticket) }}" data-respond>{{ $ticket->ticket_no }}</a>
                        <span class="cell-sub">{{ $ticket->category->name }} · {{ $ticket->typeLabel() }}</span>
                    </td>
                    <td data-label="Pemohon">
                        <span class="cell-main">{{ $ticket->requester_name }}</span>
                        <span class="cell-sub">{{ $ticket->requester_email }}</span>
                    </td>
                    <td data-label="Status">
                        <span class="cell-stack">
                            <x-status-badge :status="$ticket->status" :live="false" />
                            @if ($ticket->isOverdue())
                                <span class="badge badge--sla badge--dot">Lewat SLA</span>
                            @endif
                        </span>
                    </td>
                    <td data-label="Prioritas"><x-priority-badge :priority="$ticket->priority" /></td>
                    <td data-label="Petugas">{{ $ticket->assignee->name ?? 'Belum ada' }}</td>
                    <td data-label="Masuk"><time datetime="{{ $ticket->created_at?->toIso8601String() }}" title="{{ $ticket->created_at?->translatedFormat('d F Y, H.i') }}">{{ $ticket->created_at?->diffForHumans() }}</time></td>
                    <td class="cell-go" aria-hidden="true">
                        <span class="trow-go">{{ $ticket->status->isFinal() ? 'Lihat' : 'Balas' }} <x-icon name="arrow-right" class="icon--sm" /></span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">
                        <x-empty-state title="Tidak ada tiket yang cocok dengan filter ini.">
                            <a class="btn btn--secondary btn--sm" href="{{ route('admin.tickets.index') }}">Reset filter</a>
                        </x-empty-state>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{ $tickets->links('admin._pagination') }}

@include('admin.tickets._respond')
@endsection
