@extends('layouts.public')

@section('title', 'Link tidak berlaku')

@section('content')
<section class="card-narrow rise" aria-labelledby="invalid-title">
    <svg class="card-icon" viewBox="0 0 24 24" aria-hidden="true">
        <path d="M10 14a3.5 3.5 0 0 0 5 0l3-3a3.5 3.5 0 0 0-5-5l-.5.5M14 10a3.5 3.5 0 0 0-5 0l-3 3a3.5 3.5 0 0 0 5 5l.5-.5"/>
        <path d="M4 4l16 16"/>
    </svg>
    <h1 id="invalid-title" class="page-title">Link tiket tidak berlaku</h1>
    <p class="page-lede">Link ini sudah kedaluwarsa atau tidak utuh. Minta link baru dengan nomor tiket dan email kamu, kami kirim ke email tersebut.</p>
    <a class="btn btn--primary shine" href="{{ route('tracking.lookup') }}">Minta link baru</a>
</section>
@endsection
