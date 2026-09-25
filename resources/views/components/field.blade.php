{{--
    Field form: label → kontrol → bantuan → error.
    Tanpa slot: merender <input>/<textarea> sendiri; atribut lain (maxlength, required, ...) diteruskan ke kontrol.
    Dengan slot: kontrol ditulis pemanggil (select, upload), termasuk aria-invalid/aria-describedby-nya.
--}}
@props(['name', 'label', 'type' => 'text', 'hint' => null, 'optional' => false])
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
        <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" class="input" value="{{ old($name, $attributes->get('value')) }}"
            @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
            @if ($hasError) aria-invalid="true" @endif
            {{ $attributes->except('value') }}>
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
        <p class="field-error reveal-in" id="{{ $name }}-error">{{ $message }}</p>
    @enderror
</div>
