<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Http\Requests\Concerns\ValidatesAttachment;
use App\Models\Ticket;
use Illuminate\Foundation\Http\FormRequest;

final class CommentRequest extends FormRequest
{
    use ValidatesAttachment;

    public function authorize(): bool
    {
        $ticket = $this->route('ticket');

        return $ticket instanceof Ticket && (bool) $this->user()?->can('comment', $ticket);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:5000'],
            'visibility' => ['required', 'in:public,internal'],
            'attachment' => $this->attachmentRules(),
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return ['body.required' => 'Tulis komentar dulu.', ...$this->attachmentMessages()];
    }

    public function isInternal(): bool
    {
        return $this->validated('visibility') === 'internal';
    }
}
