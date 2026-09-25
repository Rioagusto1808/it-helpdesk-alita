@extends('layouts.auth')

@section('title', 'Masuk')

@section('content')
<div class="auth-head rise">
    <h1 class="auth-title">Selamat datang kembali</h1>
    <p class="page-lede">Masuk ke panel admin. Mau melapor kendala? <a href="{{ route('tickets.create') }}">Buat tiket</a> tanpa login.</p>
</div>

@if (session('status'))
    <div class="alert alert--info reveal-in" role="status"><x-icon name="info" /> {{ session('status') }}</div>
@endif

<form class="stack rise i-1" method="POST" action="{{ route('admin.login') }}">
    @csrf
    <x-field name="email" type="email" label="Email" icon="mail" maxlength="150" autocomplete="username" required autofocus />
    <x-field name="password" type="password" label="Password" icon="lock" reveal autocomplete="current-password" required />

    <div class="auth-row">
        <label class="check">
            <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
            <span>Ingat saya</span>
        </label>
        <a href="{{ route('admin.password.request') }}">Lupa password?</a>
    </div>

    <button type="submit" class="btn btn--primary btn--block shine">Masuk <x-icon name="arrow-right" class="btn-arrow" /></button>
</form>
@endsection
