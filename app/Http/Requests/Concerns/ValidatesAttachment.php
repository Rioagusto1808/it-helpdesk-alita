<?php

declare(strict_types=1);

namespace App\Http\Requests\Concerns;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Number;

/** Aturan lampiran yang sama untuk tiket baru, balasan pemohon, dan komentar agent. */
trait ValidatesAttachment
{
    /** @return list<string> */
    protected function attachmentRules(): array
    {
        return [
            'nullable', 'file', 'max:'.config('helpdesk.max_upload_kb'),
            'extensions:jpg,jpeg,png,pdf', 'mimes:jpg,jpeg,png,pdf',
        ];
    }

    /** @return array<string, string> */
    protected function attachmentMessages(): array
    {
        return [
            'attachment.max' => 'Ukuran lampiran maksimal '.Number::fileSize(config('helpdesk.max_upload_kb') * 1024).'.',
            'attachment.extensions' => 'Lampiran harus berupa JPG, PNG, atau PDF.',
            'attachment.mimes' => 'Lampiran harus berupa JPG, PNG, atau PDF.',
        ];
    }

    public function attachment(): ?UploadedFile
    {
        $file = $this->file('attachment');

        return $file instanceof UploadedFile ? $file : null;
    }
}
