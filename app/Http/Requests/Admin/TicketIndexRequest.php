<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Category;
use App\Models\Ticket;
use App\Support\TicketFilters;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/** Filter daftar tiket dari query string. Nilai yang tidak valid diabaikan (bukan error), agar link lama tetap terbuka. */
final class TicketIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Ticket::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [];
    }

    /** @return array<string, mixed> */
    public function filters(): array
    {
        $validator = Validator::make($this->query(), [
            'status' => ['string', Rule::in(['aktif', 'semua', ...array_column(TicketStatus::cases(), 'value')])],
            'kategori' => ['string', Rule::in([Category::ITAPPS, Category::ITINFRA])],
            'jenis' => ['string', 'regex:/^[sm]:\d+$/'],
            'prioritas' => ['string', Rule::enum(TicketPriority::class)],
            'petugas' => ['string', 'regex:/^(none|me|\d+)$/'],
            'dari' => ['date_format:Y-m-d'],
            'sampai' => ['date_format:Y-m-d'],
            'sla' => ['in:1'],
            'cari' => ['string', 'max:100'],
            'urut' => ['string', Rule::in(array_keys(TicketFilters::SORTS))],
        ]);

        return array_filter($validator->valid(), fn (mixed $value): bool => $value !== null && $value !== '');
    }
}
