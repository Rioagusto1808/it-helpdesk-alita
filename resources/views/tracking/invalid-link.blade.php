@extends('layouts.public')

@section('title', 'Link tidak berlaku')

@section('content')
<section class="card-narrow card card--accent rise" aria-labelledby="invalid-title">
    <span class="card-icon" aria-hidden="true"><x-icon name="link-off" /></span>
    <div>
        <h1 id="invalid-title" class="page-title">Link tiket tidak berlaku</h1>
        <p class="page-lede">Link ini sudah kedaluwarsa atau tidak utuh. Minta link baru dengan nomor tiket dan email kamu, kami kirim ke email tersebut.</p>
    </div>
    <a class="btn btn--primary btn--block shine" href="{{ route('tracking.lookup') }}">Minta link baru <x-icon name="arrow-right" class="btn-arrow" /></a>
</section>
@endsection
