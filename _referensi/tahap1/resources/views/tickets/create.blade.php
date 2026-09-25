@extends('layouts.app')

@section('title', 'Lapor kendala IT')

@section('content')
<div class="shell">
    <aside class="intro">
        <p class="brand">{{ config('helpdesk.app_name') }}</p>
        <h1>Ada kendala IT? Laporkan di sini.</h1>
        <p class="lede">Tiket langsung masuk antrian tim IT, dan nomor tiketnya dikirim ke email kamu.</p>

        <ol class="steps">
            <li><strong>Isi form</strong><span>ITPass untuk aplikasi dan modul, ITInfra untuk perangkat, internet, dan email.</span></li>
            <li><strong>Masuk antrian</strong><span>Tiket dikerjakan berurutan sesuai waktu masuk.</span></li>
            <li><strong>Pantau lewat email</strong><span>Setiap perubahan status dikirim ke email yang kamu isi.</span></li>
        </ol>
    </aside>

    <main class="panel" data-accent-root data-accent="">
        @if ($errors->any())
            <div class="alert" role="alert">Ada isian yang perlu diperbaiki. Cek bagian yang ditandai merah.</div>
        @endif

        <form id="ticket-form" method="POST" action="{{ route('tickets.store') }}"
              enctype="multipart/form-data" novalidate
              data-max-kb="{{ config('helpdesk.max_upload_kb') }}">
            @csrf

            {{-- Honeypot: manusia tidak melihat field ini, bot biasanya mengisinya --}}
            <div class="hp" aria-hidden="true">
                <label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
            </div>

            <fieldset class="field @error('category_id') has-error @enderror">
                <legend>Kategori</legend>
                <div class="cat-grid">
                    @foreach ($categories as $category)
                        <label class="cat" data-code="{{ $category->code }}">
                            <input type="radio" name="category_id" value="{{ $category->id }}"
                                   data-code="{{ $category->code }}"
                                   @checked(old('category_id') == $category->id) required>
                            <span class="cat-body">
                                <span class="cat-name">{{ $category->name }}</span>
                                <span class="cat-desc">
                                    {{ $category->code === 'ITPASS' ? 'Aplikasi dan modul sistem' : 'Laptop, printer, internet, email' }}
                                </span>
                            </span>
                        </label>
                    @endforeach
                </div>
                @error('category_id')<p class="error">{{ $message }}</p>@enderror
            </fieldset>

            {{-- ITInfra → Services --}}
            <div class="field branch @error('service_id') has-error @enderror" data-branch="ITINFRA">
                <label for="service_id">Layanan</label>
                <select id="service_id" name="service_id" data-has-other data-msg="Pilih layanan yang bermasalah.">
                    <option value="">Pilih layanan</option>
                    @foreach ($services as $service)
                        <option value="{{ $service->id }}" data-other="{{ $service->is_other ? 1 : 0 }}"
                                @selected(old('service_id') == $service->id)>{{ $service->name }}</option>
                    @endforeach
                </select>
                @error('service_id')<p class="error">{{ $message }}</p>@enderror

                <div class="field other @error('service_other') has-error @enderror" data-other-for="service_id">
                    <label for="service_other">Layanan lainnya</label>
                    <input type="text" id="service_other" name="service_other" maxlength="100"
                           value="{{ old('service_other') }}" placeholder="Contoh: VPN, scanner, proyektor">
                    @error('service_other')<p class="error">{{ $message }}</p>@enderror
                </div>
            </div>

            {{-- ITPass → Module --}}
            <div class="field branch @error('module_id') has-error @enderror" data-branch="ITPASS">
                <label for="module_id">Modul</label>
                <select id="module_id" name="module_id" data-has-other data-msg="Pilih modul yang bermasalah.">
                    <option value="">Pilih modul</option>
                    @foreach ($modules as $module)
                        <option value="{{ $module->id }}" data-other="{{ $module->is_other ? 1 : 0 }}"
                                @selected(old('module_id') == $module->id)>{{ $module->name }}</option>
                    @endforeach
                </select>
                @error('module_id')<p class="error">{{ $message }}</p>@enderror

                <div class="field other @error('module_other') has-error @enderror" data-other-for="module_id">
                    <label for="module_other">Modul lainnya</label>
                    <input type="text" id="module_other" name="module_other" maxlength="100"
                           value="{{ old('module_other') }}" placeholder="Tulis nama modulnya">
                    @error('module_other')<p class="error">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="row">
                <div class="field @error('requester_name') has-error @enderror">
                    <label for="requester_name">Nama</label>
                    <input type="text" id="requester_name" name="requester_name" maxlength="100"
                           value="{{ old('requester_name') }}" autocomplete="name" required>
                    @error('requester_name')<p class="error">{{ $message }}</p>@enderror
                </div>

                <div class="field @error('requester_email') has-error @enderror">
                    <label for="requester_email">Email</label>
                    <input type="email" id="requester_email" name="requester_email" maxlength="150"
                           value="{{ old('requester_email') }}" autocomplete="email" inputmode="email"
                           placeholder="nama@alita.id" required>
                    @error('requester_email')<p class="error">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="field @error('description') has-error @enderror">
                <label for="description">Deskripsi kendala</label>
                <textarea id="description" name="description" maxlength="5000" required
                          placeholder="Apa yang terjadi, sejak kapan, dan pesan error yang muncul (kalau ada).">{{ old('description') }}</textarea>
                <p class="hint"><span>Semakin jelas, semakin cepat ditangani.</span><span id="desc-count">0 / 5000</span></p>
                @error('description')<p class="error">{{ $message }}</p>@enderror
            </div>

            <div class="field @error('attachment') has-error @enderror">
                <span class="label">Lampiran <span class="optional">(opsional)</span></span>
                <label class="drop" for="attachment">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M12 16V4m0 0-4.5 4.5M12 4l4.5 4.5M4 15v3a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-3"
                              stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <span>
                        <strong>Tarik file ke sini atau pilih file</strong>
                        <small>JPG, PNG, atau PDF, maksimal 5 MB</small>
                    </span>
                    <input type="file" id="attachment" name="attachment" accept=".jpg,.jpeg,.png,.pdf,image/jpeg,image/png,application/pdf">
                </label>
                <div class="file-chip" id="file-chip" hidden>
                    <span class="file-name" id="file-name"></span>
                    <span class="file-size" id="file-size"></span>
                    <button type="button" class="link-btn" id="file-remove">Hapus</button>
                </div>
                @error('attachment')<p class="error">{{ $message }}</p>@enderror
            </div>

            <div class="actions">
                <p>Nomor tiket dan posisi antrian akan dikirim ke email kamu.</p>
                <button type="submit" class="btn">Kirim tiket</button>
            </div>
        </form>
    </main>
</div>
@endsection
