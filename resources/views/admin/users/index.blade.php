@extends('layouts.admin')

@section('title', 'User')

@section('content')
<header class="admin-head">
    <h1 class="page-title">User</h1>
    <p class="page-lede">Admin dan agent tim IT. User tidak dihapus, cukup dinonaktifkan; sistem selalu butuh minimal satu admin aktif.</p>
</header>

@error('user')
    <div class="alert alert--error reveal-in" role="alert">{{ $message }}</div>
@enderror

<div class="split">
    <section class="table-card" aria-label="Daftar user">
        <table class="ticket-table">
            <thead>
                <tr><th scope="col">Nama</th><th scope="col">Peran</th><th scope="col">Status</th><th scope="col">Login terakhir</th><th scope="col">Tiket aktif</th><th scope="col"><span class="sr-only">Aksi</span></th></tr>
            </thead>
            <tbody>
                @foreach ($users as $user)
                    <tr @class(['trow', 'trow--muted' => ! $user->is_active, 'trow--selected' => $editing?->is($user)])>
                        <td class="trow-no">
                            <span>
                                <span class="cell-main">{{ $user->name }} @if ($user->is(auth()->user())) <span class="cell-sub">(kamu)</span> @endif</span>
                                <span class="cell-sub">{{ $user->email }}</span>
                            </span>
                        </td>
                        <td data-label="Peran">{{ $user->role->label() }}</td>
                        <td data-label="Status">
                            <span @class(['badge', 'badge--selesai' => $user->is_active, 'badge--ditutup' => ! $user->is_active])>{{ $user->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                        </td>
                        <td data-label="Login terakhir">{{ $user->last_login_at?->diffForHumans() ?? 'Belum pernah' }}</td>
                        <td data-label="Tiket aktif">{{ $user->active_tickets_count }}</td>
                        <td class="cell-actions">
                            <a class="btn btn--ghost btn--sm" href="{{ route('admin.users.edit', $user) }}">Ubah</a>
                            @unless ($user->is(auth()->user()))
                                <form method="POST" action="{{ route('admin.users.toggle', $user) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn--secondary btn--sm">{{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                                </form>
                            @endunless
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>

    <section class="panel" aria-labelledby="form-title">
        <h2 id="form-title" class="section-title">{{ $editing ? 'Ubah user' : 'Tambah user' }}</h2>
        <form class="stack stack--sm" method="POST" action="{{ $editing ? route('admin.users.update', $editing) : route('admin.users.store') }}">
            @csrf
            @if ($editing)
                @method('PUT')
            @endif
            <x-field name="name" label="Nama" maxlength="100" autocomplete="off" :value="$editing?->name" required />
            <x-field name="email" type="email" label="Email" maxlength="150" autocomplete="off" :value="$editing?->email" required />
            <x-field name="role" label="Peran">
                <span class="select"><select id="role" name="role">
                    @foreach (\App\Enums\UserRole::cases() as $role)
                        <option value="{{ $role->value }}" @selected(old('role', $editing?->role->value ?? 'agent') === $role->value)>{{ $role->label() }}</option>
                    @endforeach
                </select></span>
            </x-field>
            <x-field name="password" type="password" :label="$editing ? 'Password baru' : 'Password awal'" minlength="10" autocomplete="new-password" optional
                     :hint="$editing ? 'Kosongkan jika tidak diganti.' : 'Kosongkan untuk mengirim link buat password ke email user.'" />
            <div class="action-inline">
                <button type="submit" class="btn btn--primary btn--sm">{{ $editing ? 'Simpan perubahan' : 'Tambah user' }}</button>
                @if ($editing)
                    <a class="btn btn--ghost btn--sm" href="{{ route('admin.users.index') }}">Batal</a>
                @endif
            </div>
        </form>
    </section>
</div>
@endsection
