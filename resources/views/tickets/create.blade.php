@extends('layouts.public')

@section('title', 'Lapor kendala IT')

@push('scripts')
    <script src="{{ asset('js/ticket-form.js') }}?v={{ config('app.asset_version') }}" defer></script>
@endpush

@section('content')
@php($maxUpload = \Illuminate\Support\Number::fileSize(config('helpdesk.max_upload_kb') * 1024))
<div class="ticket-page">
    <div class="ticket-page-bg aurora" aria-hidden="true"></div>

    <section class="intro">
        <h1 class="intro-title rise">Ada kendala IT? Laporkan di sini.</h1>
        <p class="intro-lede rise i-1">Cukup 2 menit. Tiket langsung masuk antrian tim IT, dan nomornya dikirim ke email kamu.</p>
        <p class="intro-links rise i-2">
            <a class="intro-queue" href="{{ route('queue.index') }}">
                <span class="live-dot" aria-hidden="true"></span>
                Lihat antrian <span class="intro-queue-count">{{ $activeCount }} tiket aktif</span>
            </a>
            <a class="intro-lookup" href="{{ route('tracking.lookup') }}">Sudah punya tiket? Cari di sini</a>
        </p>
    </section>

    <section class="form-panel rise i-3" aria-labelledby="form-title">
        <div class="form-panel-head">
            <h2 id="form-title" class="form-panel-title">Form laporan</h2>
            <p class="form-panel-sub">Semua isian wajib, kecuali yang bertanda opsional.</p>
        </div>

        <form class="ticket-form" method="POST" action="{{ route('tickets.store') }}" enctype="multipart/form-data"
              data-ticket-form data-max-kb="{{ config('helpdesk.max_upload_kb') }}" data-max-label="{{ $maxUpload }}">
            @csrf

            @if ($errors->any())
                <div class="alert alert--error reveal-in" role="alert">Ada isian yang perlu diperbaiki. Cek bagian yang ditandai merah.</div>
            @endif

            {{-- Honeypot: tidak terlihat manusia, biasanya diisi bot. --}}
            <div class="hp" aria-hidden="true">
                <label for="website">Website</label>
                <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
            </div>

            <fieldset class="field @error('category_id') has-error @enderror">
                <legend class="field-label">Kategori</legend>
                <div class="cats">
                    @foreach ($categories as $category)
                        @php($isApps = $category->code === \App\Models\Category::ITAPPS)
                        <label class="cat glow-border lift">
                            <input type="radio" name="category_id" value="{{ $category->id }}" data-code="{{ $category->code }}" required
                                   @checked(old('category_id') == $category->id)
                                   @error('category_id') aria-invalid="true" aria-describedby="category_id-error" @enderror>
                            <span class="cat-body">
                                <span class="cat-check" aria-hidden="true"></span>
                                <span class="cat-name">
                                    {{ $category->name }}
                                    <span class="tag {{ $isApps ? 'tag-apps' : 'tag-infra' }}">{{ $isApps ? 'Aplikasi' : 'Perangkat' }}</span>
                                </span>
                                <span class="cat-desc">{{ $category->description }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
                @error('category_id')
                    <p class="field-error reveal-in" id="category_id-error">{{ $message }}</p>
                @enderror
            </fieldset>

            {{-- Cabang tampil lewat CSS :has() sesuai kategori; JS hanya menonaktifkan yang tersembunyi. --}}
            <div class="branch reveal-in" data-branch="ITINFRA">
                <x-field name="service_id" label="Layanan">
                    <span class="select">
                        <select id="service_id" name="service_id" data-required-when-visible
                                @error('service_id') aria-invalid="true" aria-describedby="service_id-error" @enderror>
                            <option value="">Pilih layanan</option>
                            @foreach ($services as $service)
                                <option value="{{ $service->id }}" @if ($service->is_other) data-other @endif @selected(old('service_id') == $service->id)>{{ $service->name }}</option>
                            @endforeach
                        </select>
                    </span>
                </x-field>
                <div class="branch-other reveal-in">
                    <x-field name="service_other" label="Layanan lainnya" maxlength="100" placeholder="Contoh: VPN, scanner, proyektor" data-required-when-visible />
                </div>
            </div>

            <div class="branch reveal-in" data-branch="ITAPPS">
                <x-field name="module_id" label="Modul">
                    <span class="select">
                        <select id="module_id" name="module_id" data-required-when-visible
                                @error('module_id') aria-invalid="true" aria-describedby="module_id-error" @enderror>
                            <option value="">Pilih modul</option>
                            @foreach ($modules as $module)
                                <option value="{{ $module->id }}" @if ($module->is_other) data-other @endif @selected(old('module_id') == $module->id)>{{ $module->name }}</option>
                            @endforeach
                        </select>
                    </span>
                </x-field>
                <div class="branch-other reveal-in">
                    <x-field name="module_other" label="Modul lainnya" maxlength="100" placeholder="Tulis nama modulnya" data-required-when-visible />
                </div>
            </div>

            <div class="field-row">
                <x-field name="requester_name" label="Nama" maxlength="100" autocomplete="name" required />
                <x-field name="requester_email" type="email" label="Email" maxlength="150" autocomplete="email" inputmode="email" required />
            </div>

            <x-field name="description" type="textarea" label="Deskripsi kendala" rows="5" minlength="10" maxlength="5000" required
                     hint="Apa yang terjadi, sejak kapan, dan pesan error yang muncul." />

            <x-field name="attachment" label="Lampiran" optional>
                <label class="drop" data-drop>
                    <svg class="drop-icon" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M12 16V4m0 0-4.5 4.5M12 4l4.5 4.5M4 15v3a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-3"/>
                    </svg>
                    <span class="drop-text">
                        <strong>Tarik file ke sini atau pilih file</strong>
                        <small>JPG, PNG, atau PDF, maksimal {{ $maxUpload }}</small>
                    </span>
                    <input type="file" id="attachment" name="attachment" class="sr-only" accept=".jpg,.jpeg,.png,.pdf"
                           @error('attachment') aria-invalid="true" aria-describedby="attachment-error" @enderror>
                </label>
                <div class="file-chip reveal-in" data-file-chip hidden>
                    <span class="file-chip-name" data-file-name></span>
                    <span class="file-chip-size" data-file-size></span>
                    <button type="button" class="btn btn--ghost btn--sm" data-file-remove>Hapus</button>
                </div>
                <p class="field-error" data-file-error role="alert" hidden></p>
            </x-field>

            <div class="form-actions">
                <p class="form-actions-note">Nomor tiket dan posisi antrian akan dikirim ke email kamu.</p>
                <button type="submit" class="btn btn--primary shine" data-submit data-loading-label="Mengirim tiket…">Kirim tiket</button>
            </div>
        </form>
    </section>

    <ol class="steps rise i-4" aria-label="Cara kerja">
        <li><span class="steps-no" aria-hidden="true">1</span><span><strong>Isi form</strong> ITApps untuk aplikasi dan modul, ITInfra untuk perangkat, internet, dan email.</span></li>
        <li><span class="steps-no" aria-hidden="true">2</span><span><strong>Masuk antrian</strong> Tiket dikerjakan sesuai prioritas dan waktu masuk.</span></li>
        <li><span class="steps-no" aria-hidden="true">3</span><span><strong>Pantau lewat email</strong> Setiap perubahan status dikirim ke email kamu.</span></li>
    </ol>
</div>
@endsection
