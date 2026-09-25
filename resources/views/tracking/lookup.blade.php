@extends('layouts.public')

@section('title', 'Cari tiket')

@push('scripts')
    <script src="{{ asset('js/ticket-form.js') }}?v={{ config('app.asset_version') }}" defer></script>
@endpush

@section('content')
<section class="card-narrow card card--accent rise" aria-labelledby="lookup-title">
    <span class="card-icon" aria-hidden="true"><x-icon name="search" /></span>
    <div>
        <h1 id="lookup-title" class="page-title">Cari tiket</h1>
        <p class="page-lede">Masukkan nomor tiket dan email yang dipakai saat melapor. Kami kirim ulang link tiketnya ke email tersebut.</p>
    </div>

    @if (session('status'))
        <div class="alert alert--info reveal-in" role="status"><x-icon name="mail" /> {{ session('status') }}</div>
    @endif

    <form class="stack" method="POST" action="{{ route('tracking.send-link') }}" data-ticket-form>
        @csrf
        <x-field name="ticket_no" label="Nomor tiket" icon="ticket" maxlength="30" autocomplete="off" placeholder="IT-20260924-00042" required />
        <x-field name="email" type="email" label="Email" icon="mail" maxlength="150" autocomplete="email" inputmode="email" required />
        <button type="submit" class="btn btn--primary shine" data-submit data-loading-label="Mengirim link…">
            <span data-label>Kirim link</span> <x-icon name="send" class="btn-arrow" />
        </button>
    </form>
</section>
@endsection
