<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\TicketPriority;
use App\Models\Ticket;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdatePriorityRequest extends FormRequest
{
    public function authorize(): bool
    {
        $ticket = $this->route('ticket');

        return $ticket instanceof Ticket && (bool) $this->user()?->can('changePriority', $ticket);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['priority' => ['required', Rule::enum(TicketPriority::class)]];
    }
}
