<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\EmailStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/** Filter audit log dan email log dari query string. Nilai yang tidak valid diabaikan. */
final class LogIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [];
    }

    /** @return array<string, string> */
    public function filters(): array
    {
        $validator = Validator::make($this->query(), [
            'aksi' => ['string', 'max:60', 'regex:/^[a-z_.]+$/'],
            'pelaku' => ['string', 'regex:/^(sistem|\d+)$/'],
            'dari' => ['date_format:Y-m-d'],
            'sampai' => ['date_format:Y-m-d'],
            'tiket' => ['string', 'max:30'],
            'status' => ['string', Rule::enum(EmailStatus::class)],
            'tipe' => ['string', 'max:40', 'regex:/^[a-z_]+$/'],
        ]);

        return array_filter($validator->valid(), fn (mixed $value): bool => is_string($value) && $value !== '');
    }
}
