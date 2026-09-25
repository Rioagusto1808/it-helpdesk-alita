@extends('layouts.public')

@section('title', 'Lapor kendala IT')

@push('scripts')
    <script src="{{ asset('js/ticket-form.js') }}?v={{ config('app.asset_version') }}" defer></script>
@endpush

@section('content')
@php($maxUpload = \Illuminate\Support\Number::fileSize(config('helpdesk.max_upload_kb') * 1024))
<div class="ticket-page">
    <section class="hero" aria-labelledby="hero-title">
        <div class="orbs" aria-hidden="true"><span class="orb orb--1"></span><span class="orb orb--2"></span><span class="orb orb--3"></span></div>
        <div class="grid-dots" aria-hidden="true"></div>

        <div class="hero-body">
            <p class="kicker rise"><span class="kicker-icon"><x-icon name="bolt" /></span> Tim IT siap membantu</p>
            <h1 id="hero-title" class="hero-title rise i-1">Ada kendala IT? <span class="text-gradient">Laporkan di sini.</span></h1>
            <p class="hero-lede rise i-2">Cukup 2 menit. Tiket langsung masuk antrian tim IT, dan nomornya dikirim ke email kamu.</p>

            <div class="hero-links rise i-3">
                <a class="queue-pill" href="{{ route('queue.index') }}">
                    <span class="live-dot" aria-hidden="true"></span>
                    Lihat antrian
                    <span class="queue-pill-count">{{ $activeCount }} tiket aktif</span>
                    <x-icon name="arrow-right" class="icon--sm btn-arrow" />
                </a>
                <a class="hero-lookup" href="{{ route('tracking.lookup') }}"><x-icon name="search" class="icon--sm" /> Sudah punya tiket? Cari di sini</a>
            </div>
        </div>

        <ol class="steps rise i-4" aria-label="Cara kerja">
            <li>
                <span class="steps-no" aria-hidden="true"><x-icon name="edit" /></span>
                <span><strong>Isi form</strong> ITApps untuk aplikasi dan modul, ITInfra untuk perangkat, internet, dan email.</span>
            </li>
            <li>
                <span class="steps-no" aria-hidden="true"><x-icon name="list" /></span>
                <span><strong>Masuk antrian</strong> Tiket dikerjakan sesuai prioritas dan waktu masuk.</span>
            </li>
            <li>
                <span class="steps-no" aria-hidden="true"><x-icon name="mail" /></span>
                <span><strong>Pantau lewat email</strong> Setiap perubahan status dikirim ke email kamu.</span>
            </li>
        </ol>
    </section>

    <section class="form-card card card--accent rise i-2" aria-labelledby="form-title">
        <div class="form-card-head">
            <div>
                <h2 id="form-title" class="section-title">Form laporan</h2>
                <p class="form-card-sub">Semua isian wajib, kecuali yang bertanda opsional.</p>
            </div>
            <div class="form-progress" data-form-progress hidden>
                <span class="form-progress-label" data-form-progress-label aria-live="polite"></span>
                <span class="form-progress-bar" aria-hidden="true"><span data-form-progress-fill></span></span>
            </div>
        </div>

        <form class="ticket-form" method="POST" action="{{ route('tickets.store') }}" enctype="multipart/form-data"
              data-ticket-form data-max-kb="{{ config('helpdesk.max_upload_kb') }}" data-max-label="{{ $maxUpload }}">
            @csrf

            @if ($errors->any())
                <div class="alert alert--error reveal-in" role="alert"><x-icon name="alert" /> Ada isian yang perlu diperbaiki. Cek bagian yang ditandai merah.</div>
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
                        <label class="cat glow-border" data-tilt>
                            <input type="radio" name="category_id" value="{{ $category->id }}" data-code="{{ $category->code }}" required
                                   @checked(old('category_id') == $category->id)
                                   @error('category_id') aria-invalid="true" aria-describedby="category_id-error" @enderror>
                            <span class="cat-body">
                                <span @class(['cat-icon', 'cat-icon--apps' => $isApps, 'cat-icon--infra' => ! $isApps]) aria-hidden="true">
                                    <x-icon :name="$isApps ? 'apps' : 'laptop'" />
                                </span>
                                <span class="cat-check" aria-hidden="true"><x-icon name="check" /></span>
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
                    <p class="field-error" id="category_id-error"><x-icon name="alert" />{{ $message }}</p>
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
                    <x-field name="service_other" label="Layanan lainnya" icon="edit" maxlength="100" placeholder="Contoh: VPN, scanner, proyektor" data-required-when-visible />
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
                    <x-field name="module_other" label="Modul lainnya" icon="edit" maxlength="100" placeholder="Tulis nama modulnya" data-required-when-visible />
                </div>
            </div>

            <div class="field-row">
                <x-field name="requester_name" label="Nama" icon="user" maxlength="100" autocomplete="name" required />
                <x-field name="requester_email" type="email" label="Email" icon="mail" maxlength="150" autocomplete="email" inputmode="email" required />
            </div>

            <x-field name="description" type="textarea" label="Deskripsi kendala" rows="5" minlength="10" maxlength="5000" required
                     hint="Apa yang terjadi, sejak kapan, dan pesan error yang muncul." />

            <x-field name="attachment" label="Lampiran" optional>
                <label class="drop" data-drop>
                    <span class="drop-icon" aria-hidden="true"><x-icon name="upload" /></span>
                    <span class="drop-text">
                        <strong>Tarik file ke sini atau <span class="drop-link">pilih file</span></strong>
                        <small>JPG, PNG, atau PDF, maksimal {{ $maxUpload }}</small>
                    </span>
                    <input type="file" id="attachment" name="attachment" class="sr-only" accept=".jpg,.jpeg,.png,.pdf"
                           @error('attachment') aria-invalid="true" aria-describedby="attachment-error" @enderror>
                </label>
                <div class="file-chip reveal-in" data-file-chip hidden>
                    <span class="file-chip-icon" aria-hidden="true"><x-icon name="file" /></span>
                    <span class="file-chip-name" data-file-name></span>
                    <span class="file-chip-size" data-file-size></span>
                    <button type="button" class="btn btn--ghost btn--sm" data-file-remove>Hapus</button>
                </div>
                <p class="field-error" data-file-error role="alert" hidden></p>
            </x-field>

            <div class="form-actions">
                <p class="form-actions-note"><x-icon name="mail" class="icon--sm" /> Nomor tiket dan posisi antrian akan dikirim ke email kamu.</p>
                <button type="submit" class="btn btn--primary shine" data-submit data-loading-label="Mengirim tiket…">
                    <span data-label>Kirim tiket</span>
                    <x-icon name="arrow-right" class="btn-arrow" />
                </button>
            </div>
        </form>
    </section>
</div>
@endsection
