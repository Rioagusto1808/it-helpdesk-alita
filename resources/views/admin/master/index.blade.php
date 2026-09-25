{{-- Dipakai layanan dan modul. $page: title, item, hint, route (prefix nama route). --}}
@extends('layouts.admin')

@section('title', $page['title'])

@section('content')
<header class="admin-head">
    <h1 class="page-title">{{ $page['title'] }}</h1>
    <p class="page-lede">{{ $page['hint'] }} Item yang pernah dipakai tiket tidak dihapus, cukup dinonaktifkan.</p>
</header>

<div class="split">
    <section class="table-card" aria-label="Daftar {{ $page['item'] }}">
        <table class="ticket-table">
            <thead>
                <tr><th scope="col">Nama</th><th scope="col">Jumlah tiket</th><th scope="col">Urutan</th><th scope="col">Status</th><th scope="col"><span class="sr-only">Aksi</span></th></tr>
            </thead>
            <tbody>
                @foreach ($items as $item)
                    <tr @class(['trow', 'trow--muted' => ! $item->is_active, 'trow--selected' => $editing?->is($item)])>
                        <td class="trow-no">{{ $item->name }} @if ($item->is_other) <span class="badge badge--baru">Isian bebas</span> @endif</td>
                        <td data-label="Jumlah tiket">{{ $item->tickets_count }}</td>
                        <td data-label="Urutan">{{ $item->sort_order }}</td>
                        <td data-label="Status">
                            <span @class(['badge', 'badge--selesai' => $item->is_active, 'badge--ditutup' => ! $item->is_active])>{{ $item->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                        </td>
                        <td class="cell-actions">
                            @unless ($item->is_other)
                                <a class="btn btn--ghost btn--sm" href="{{ route($page['route'].'.edit', $item) }}">Ubah</a>
                                <form method="POST" action="{{ route($page['route'].'.toggle', $item) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn--secondary btn--sm">{{ $item->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                                </form>
                            @endunless
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>

    <section class="panel" aria-labelledby="form-title">
        <h2 id="form-title" class="section-title">{{ $editing ? 'Ubah '.$page['item'] : 'Tambah '.$page['item'] }}</h2>
        <form class="stack stack--sm" method="POST" action="{{ $editing ? route($page['route'].'.update', $editing) : route($page['route'].'.store') }}">
            @csrf
            @if ($editing)
                @method('PUT')
            @endif
            <x-field name="name" label="Nama" maxlength="100" :value="$editing?->name" required />
            <x-field name="sort_order" type="number" label="Urutan" min="0" max="998" :value="$editing?->sort_order ?? 0"
                     hint="Angka kecil tampil lebih atas. Others selalu paling bawah." />
            <div class="action-inline">
                <button type="submit" class="btn btn--primary btn--sm">{{ $editing ? 'Simpan perubahan' : 'Tambah '.$page['item'] }}</button>
                @if ($editing)
                    <a class="btn btn--ghost btn--sm" href="{{ route($page['route'].'.index') }}">Batal</a>
                @endif
            </div>
        </form>
    </section>
</div>
@endsection
