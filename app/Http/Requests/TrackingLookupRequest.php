<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Ticket;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

final class TrackingLookupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'ticket_no' => is_string($this->input('ticket_no')) ? Str::upper(trim($this->input('ticket_no'))) : $this->input('ticket_no'),
            'email' => is_string($this->input('email')) ? Str::lower(trim($this->input('email'))) : $this->input('email'),
        ]);
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'ticket_no' => ['required', 'string', 'max:30'],
            'email' => ['required', 'email:rfc', 'max:150'],
        ];
    }

    /** Null jika nomor + email tidak cocok; pemanggil tidak boleh membocorkan hasilnya ke layar. */
    public function ticket(): ?Ticket
    {
        return Ticket::query()
            ->where('ticket_no', $this->validated('ticket_no'))
            ->where('requester_email', $this->validated('email'))
            ->first();
    }
}
