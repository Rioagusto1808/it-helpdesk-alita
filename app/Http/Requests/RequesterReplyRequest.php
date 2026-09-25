<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesAttachment;
use Illuminate\Foundation\Http\FormRequest;

final class RequesterReplyRequest extends FormRequest
{
    use ValidatesAttachment;

    /** Kepemilikan sudah dibuktikan oleh signed URL (middleware "signed"). */
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:5000'],
            'attachment' => $this->attachmentRules(),
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return ['body.required' => 'Tulis balasan kamu dulu.', ...$this->attachmentMessages()];
    }
}
