@extends('layouts.public')

@section('title', 'Cari tiket')

@push('scripts')
    <script src="{{ asset('js/ticket-form.js') }}?v={{ config('app.asset_version') }}" defer></script>
@endpush

@section('content')
<section class="card-narrow rise" aria-labelledby="lookup-title">
    <h1 id="lookup-title" class="page-title">Cari tiket</h1>
    <p class="page-lede">Masukkan nomor tiket dan email yang dipakai saat melapor. Kami kirim ulang link tiketnya ke email tersebut.</p>

    @if (session('status'))
        <div class="alert alert--info reveal-in" role="status">{{ session('status') }}</div>
    @endif

    <form class="stack" method="POST" action="{{ route('tracking.send-link') }}" data-ticket-form>
        @csrf
        <x-field name="ticket_no" label="Nomor tiket" maxlength="30" autocomplete="off" placeholder="IT-20260924-00042" required />
        <x-field name="email" type="email" label="Email" maxlength="150" autocomplete="email" inputmode="email" required />
        <button type="submit" class="btn btn--primary shine" data-submit data-loading-label="Mengirim link…">Kirim link</button>
    </form>
</section>
@endsection
