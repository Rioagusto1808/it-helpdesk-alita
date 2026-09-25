@extends('layouts.auth')

@section('title', 'Buat password baru')

@section('content')
<div class="auth-head rise">
    <span class="auth-icon" aria-hidden="true"><x-icon name="lock" /></span>
    <h1 class="auth-title">Buat password baru</h1>
    <p class="page-lede">Minimal 10 karakter. Gunakan kalimat yang mudah kamu ingat tapi sulit ditebak orang lain.</p>
</div>

<form class="stack rise i-1" method="POST" action="{{ route('admin.password.update', $token) }}">
    @csrf
    <x-field name="email" type="email" label="Email" icon="mail" maxlength="150" autocomplete="username" :value="$email" required />
    <x-field name="password" type="password" label="Password baru" icon="lock" reveal strength minlength="10" autocomplete="new-password" required autofocus />
    <x-field name="password_confirmation" type="password" label="Ulangi password baru" icon="lock" reveal minlength="10" autocomplete="new-password" required />
    <button type="submit" class="btn btn--primary btn--block shine">Simpan password <x-icon name="check" class="btn-arrow" /></button>
</form>
@endsection
