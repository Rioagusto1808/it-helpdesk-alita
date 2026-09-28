<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\TicketStatus;
use App\Http\Requests\Concerns\ValidatesAttachment;
use App\Models\Ticket;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Modal balasan tim IT. Transisi status dicek ulang di Action RespondToTicket. */
final class RespondRequest extends FormRequest
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
            'status' => ['required', Rule::in(array_map(fn (TicketStatus $s): string => $s->value, TicketStatus::responses()))],
            'message' => ['required', 'string', 'max:5000'],
            'attachment' => $this->attachmentRules(),
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'status.required' => 'Pilih status tiket.',
            'status.in' => 'Pilih Diproses, Selesai, atau Ditolak.',
            'message.required' => 'Tulis deskripsi balasan dulu.',
            ...$this->attachmentMessages(),
        ];
    }

    public function status(): TicketStatus
    {
        return TicketStatus::from($this->validated('status'));
    }
}
