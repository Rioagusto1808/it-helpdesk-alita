{{--
    Isian balasan tim IT: status (diproses/selesai/ditolak) + deskripsi + lampiran.
    Dipakai modal daftar tiket ($ticket null, diisi JS) dan halaman detail ($ticket terisi, dirender server).
--}}
@php
    $icons = ['diproses' => 'refresh', 'selesai' => 'check', 'dibatalkan' => 'x'];
    $current = $ticket?->status;
@endphp
<fieldset class="respond-choices">
    <legend class="field-label">Status</legend>
    @foreach (\App\Enums\TicketStatus::responses() as $status)
        @php($allowed = ! $current || $status === $current || $current->canTransitionTo($status))
        <label class="respond-choice respond-choice--{{ $status->value }}" @unless ($allowed) title="Tidak bisa dari status {{ $current->label() }}" @endunless>
            <input type="radio" name="status" value="{{ $status->value }}" required
                   @checked(old('status', $current?->value) === $status->value) @disabled(! $allowed)>
            <span class="respond-choice-icon" aria-hidden="true"><x-icon :name="$icons[$status->value]" /></span>
            <span>{{ $status->responseLabel() }}</span>
        </label>
    @endforeach
</fieldset>
@error('status')
    <p class="field-error" id="status-error"><x-icon name="alert" />{{ $message }}</p>
@enderror

<x-field name="message" type="textarea" label="Deskripsi" maxlength="5000" rows="4" required
         hint="Jelaskan apa yang sudah dilakukan atau alasan ditolak." />

<div class="field @error('attachment') has-error @enderror">
    <label class="field-label" for="respond-attachment">Lampiran <span class="field-optional">(opsional)</span></label>
    <input class="input input--file" type="file" id="respond-attachment" name="attachment" accept=".jpg,.jpeg,.png,.pdf"
           aria-describedby="respond-attachment-hint @error('attachment') attachment-error @enderror" @error('attachment') aria-invalid="true" @enderror>
    <p class="field-hint" id="respond-attachment-hint">JPG, PNG, atau PDF, maksimal {{ \Illuminate\Support\Number::fileSize(config('helpdesk.max_upload_kb') * 1024) }}. Ikut terlampir di email.</p>
    @error('attachment')
        <p class="field-error" id="attachment-error"><x-icon name="alert" />{{ $message }}</p>
    @enderror
</div>

<p class="respond-note"><x-icon name="mail" /> <span>Balasan dikirim ke email <strong data-r="email">{{ $ticket?->requester_email }}</strong>.</span></p>
