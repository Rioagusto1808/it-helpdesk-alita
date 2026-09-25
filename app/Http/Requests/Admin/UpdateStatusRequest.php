<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Transisi yang diizinkan dan catatan wajib dicek ulang di Action ChangeTicketStatus. */
final class UpdateStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $ticket = $this->route('ticket');

        return $ticket instanceof Ticket && (bool) $this->user()?->can('changeStatus', $ticket);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(TicketStatus::class)],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
