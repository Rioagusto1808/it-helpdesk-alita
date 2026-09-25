@extends('layouts.auth')

@section('title', 'Lupa password')

@section('content')
<div class="auth-head">
    <h1 class="page-title">Lupa password</h1>
    <p class="page-lede">Masukkan email akun panel admin kamu. Kami kirim link untuk membuat password baru.</p>
</div>

@if (session('status'))
    <div class="alert alert--info reveal-in" role="status">{{ session('status') }}</div>
@endif

<form class="stack" method="POST" action="{{ route('admin.password.email') }}">
    @csrf
    <x-field name="email" type="email" label="Email" maxlength="150" autocomplete="username" required autofocus />
    <button type="submit" class="btn btn--primary shine">Kirim link reset</button>
</form>

<a class="auth-back" href="{{ route('admin.login') }}">Kembali ke halaman masuk</a>
@endsection
