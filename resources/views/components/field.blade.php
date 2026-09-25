{{--
    Field form: label → kontrol → bantuan → error.
    Tanpa slot: merender <input>/<textarea> sendiri; atribut lain (maxlength, required, ...) diteruskan ke kontrol.
    Dengan slot: kontrol ditulis pemanggil (select, upload), termasuk aria-invalid/aria-describedby-nya.
    icon="mail"      → ikon di kiri input.
    reveal           → tombol tampil/sembunyikan password (muncul lewat motion.js).
    strength         → meter kekuatan password di bawah input.
--}}
@props(['name', 'label', 'type' => 'text', 'hint' => null, 'optional' => false, 'icon' => null, 'reveal' => false, 'strength' => false])
@php
    $hasError = $errors->has($name);
    $describedBy = trim(($hint ? "{$name}-hint " : '').($hasError ? "{$name}-error" : ''));
@endphp
<div class="field @if ($hasError) has-error @endif">
    <label class="field-label" for="{{ $name }}">
        {{ $label }}
        @if ($optional)
            <span class="field-optional">(opsional)</span>
        @endif
    </label>

    @if ($slot->isNotEmpty())
        {{ $slot }}
    @elseif ($type === 'textarea')
        <textarea id="{{ $name }}" name="{{ $name }}" class="input"
            @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
            @if ($hasError) aria-invalid="true" @endif
            {{ $attributes }}>{{ old($name) }}</textarea>
    @else
        <span @class(['input-wrap', 'has-icon' => $icon, 'has-action' => $reveal])>
            @if ($icon)
                <x-icon :name="$icon" class="input-icon" />
            @endif
            <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" class="input" value="{{ old($name, $attributes->get('value')) }}"
                @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
                @if ($hasError) aria-invalid="true" @endif
                {{ $attributes->except('value') }}>
            @if ($reveal)
                <button type="button" class="input-action" data-password-toggle="{{ $name }}" aria-label="Tampilkan password" aria-pressed="false" hidden>
                    <x-icon name="eye" class="icon-off" />
                    <x-icon name="eye-off" class="icon-on" />
                </button>
            @endif
        </span>
    @endif

    @if ($strength)
        <span class="strength" aria-hidden="true">
            <meter min="0" max="4" low="2" high="3" optimum="4" value="0" data-strength-for="{{ $name }}"></meter>
            <span class="strength-label" data-strength-label="{{ $name }}"></span>
        </span>
    @endif

    @if ($hint)
        <p class="field-hint" id="{{ $name }}-hint">
            <span>{{ $hint }}</span>
            @if ($type === 'textarea' && $attributes->has('maxlength'))
                <span class="field-count" data-char-count="{{ $name }}" aria-hidden="true"></span>
            @endif
        </p>
    @endif

    @error($name)
        <p class="field-error" id="{{ $name }}-error"><x-icon name="alert" />{{ $message }}</p>
    @enderror
</div>
