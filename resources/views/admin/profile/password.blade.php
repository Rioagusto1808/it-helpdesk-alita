@extends('layouts.admin')

@section('title', 'Ganti password')

@section('content')
<header class="admin-head rise">
    <h1 class="page-title">Ganti password</h1>
    <p class="page-lede">Password baru minimal 10 karakter dan harus berbeda dari password lama.</p>
</header>

<section class="panel panel--narrow profile-card rise i-1" aria-labelledby="profile-name">
    <div class="profile-head">
        <span class="avatar" aria-hidden="true">{{ auth()->user()->initials() }}</span>
        <div>
            <p id="profile-name" class="section-title">{{ auth()->user()->name }}</p>
            <p class="action-note">{{ auth()->user()->email }} · {{ auth()->user()->role->label() }}</p>
        </div>
    </div>

    <form class="stack" method="POST" action="{{ route('admin.profile.password') }}">
        @csrf
        @method('PUT')
        <x-field name="current_password" type="password" label="Password lama" icon="lock" reveal autocomplete="current-password" required />
        <x-field name="password" type="password" label="Password baru" icon="key" reveal strength minlength="10" autocomplete="new-password" required />
        <x-field name="password_confirmation" type="password" label="Ulangi password baru" icon="key" reveal minlength="10" autocomplete="new-password" required />
        <div>
            <button type="submit" class="btn btn--primary shine"><x-icon name="check" /> Simpan password</button>
        </div>
    </form>
</section>
@endsection
