@extends('layouts.admin')

@section('title', 'Ganti password')

@section('content')
<header class="admin-head">
    <h1 class="page-title">Ganti password</h1>
    <p class="page-lede">Password baru minimal 10 karakter dan harus berbeda dari password lama.</p>
</header>

<section class="panel panel--narrow">
    <form class="stack" method="POST" action="{{ route('admin.profile.password') }}">
        @csrf
        @method('PUT')
        <x-field name="current_password" type="password" label="Password lama" autocomplete="current-password" required />
        <x-field name="password" type="password" label="Password baru" minlength="10" autocomplete="new-password" required />
        <x-field name="password_confirmation" type="password" label="Ulangi password baru" minlength="10" autocomplete="new-password" required />
        <div>
            <button type="submit" class="btn btn--primary">Simpan password</button>
        </div>
    </form>
</section>
@endsection
