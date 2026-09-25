<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class AssignRequest extends FormRequest
{
    public function authorize(): bool
    {
        $ticket = $this->route('ticket');

        return $ticket instanceof Ticket && (bool) $this->user()?->can('assign', $ticket);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['assigned_to' => ['nullable', 'integer', Rule::exists('users', 'id')->where('is_active', true)]];
    }

    /** Null = lepas penanganan. */
    public function assignee(): ?User
    {
        $id = $this->validated('assigned_to');

        return $id ? User::query()->find((int) $id) : null;
    }
}
