@extends('layouts.auth')

@section('title', 'Masuk')

@section('content')
<div class="auth-head">
    <h1 class="page-title">Masuk ke panel admin</h1>
    <p class="page-lede">Khusus tim IT. Mau melapor kendala? <a href="{{ route('tickets.create') }}">Buat tiket</a> tanpa login.</p>
</div>

@if (session('status'))
    <div class="alert alert--info reveal-in" role="status">{{ session('status') }}</div>
@endif

<form class="stack" method="POST" action="{{ route('admin.login') }}">
    @csrf
    <x-field name="email" type="email" label="Email" maxlength="150" autocomplete="username" required autofocus />
    <x-field name="password" type="password" label="Password" autocomplete="current-password" required />

    <div class="auth-row">
        <label class="check">
            <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
            <span>Ingat saya</span>
        </label>
        <a href="{{ route('admin.password.request') }}">Lupa password?</a>
    </div>

    <button type="submit" class="btn btn--primary shine">Masuk</button>
</form>
@endsection
