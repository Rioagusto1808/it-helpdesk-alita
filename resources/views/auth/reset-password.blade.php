@extends('layouts.auth')

@section('title', 'Buat password baru')

@section('content')
<div class="auth-head">
    <h1 class="page-title">Buat password baru</h1>
    <p class="page-lede">Minimal 10 karakter. Gunakan kalimat yang mudah kamu ingat tapi sulit ditebak orang lain.</p>
</div>

<form class="stack" method="POST" action="{{ route('admin.password.update', $token) }}">
    @csrf
    <x-field name="email" type="email" label="Email" maxlength="150" autocomplete="username" :value="$email" required />
    <x-field name="password" type="password" label="Password baru" minlength="10" autocomplete="new-password" required autofocus />
    <x-field name="password_confirmation" type="password" label="Ulangi password baru" minlength="10" autocomplete="new-password" required />
    <button type="submit" class="btn btn--primary shine">Simpan password</button>
</form>
@endsection
