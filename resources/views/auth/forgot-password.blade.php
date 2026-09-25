@extends('layouts.auth')

@section('title', 'Lupa password')

@section('content')
<div class="auth-head rise">
    <span class="auth-icon" aria-hidden="true"><x-icon name="key" /></span>
    <h1 class="auth-title">Lupa password</h1>
    <p class="page-lede">Masukkan email akun panel admin kamu. Kami kirim link untuk membuat password baru.</p>
</div>

@if (session('status'))
    <div class="alert alert--info reveal-in" role="status"><x-icon name="mail" /> {{ session('status') }}</div>
@endif

<form class="stack rise i-1" method="POST" action="{{ route('admin.password.email') }}">
    @csrf
    <x-field name="email" type="email" label="Email" icon="mail" maxlength="150" autocomplete="username" required autofocus />
    <button type="submit" class="btn btn--primary btn--block shine">Kirim link reset <x-icon name="send" class="btn-arrow" /></button>
</form>

<a class="auth-back" href="{{ route('admin.login') }}"><x-icon name="arrow-left" class="icon--sm" /> Kembali ke halaman masuk</a>
@endsection
