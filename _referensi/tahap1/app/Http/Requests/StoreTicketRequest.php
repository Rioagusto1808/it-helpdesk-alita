<?php

namespace App\Http\Requests;

use App\Models\Category;
use App\Models\Module;
use App\Models\Service;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTicketRequest extends FormRequest
{
    private bool $categoryResolved = false;

    private ?string $categoryCode = null;

    public function authorize(): bool
    {
        return true; // form publik
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('requester_email'))) {
            $this->merge(['requester_email' => mb_strtolower($this->input('requester_email'))]);
        }
    }

    public function rules(): array
    {
        $code = $this->categoryCode();
        $maxKb = (int) config('helpdesk.max_upload_kb', 5120);

        return [
            'website' => ['prohibited'], // honeypot anti-bot, harus kosong

            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')],

            'service_id' => [
                Rule::requiredIf($code === 'ITINFRA'),
                'nullable', 'integer',
                Rule::exists('services', 'id')
                    ->where('category_id', (int) $this->input('category_id'))
                    ->where('is_active', true),
            ],
            'service_other' => [
                Rule::requiredIf(fn () => $code === 'ITINFRA' && $this->selectedIsOther(Service::class, 'service_id')),
                'nullable', 'string', 'max:100',
            ],

            'module_id' => [
                Rule::requiredIf($code === 'ITPASS'),
                'nullable', 'integer',
                Rule::exists('modules', 'id')->where('is_active', true),
            ],
            'module_other' => [
                Rule::requiredIf(fn () => $code === 'ITPASS' && $this->selectedIsOther(Module::class, 'module_id')),
                'nullable', 'string', 'max:100',
            ],

            'requester_name' => ['required', 'string', 'max:100'],
            'requester_email' => ['required', 'email:rfc', 'max:150'],
            'description' => ['required', 'string', 'min:10', 'max:5000'],
            'attachment' => ['nullable', 'file', 'max:'.$maxKb, 'mimes:jpg,jpeg,png,pdf'],
        ];
    }

    public function attributes(): array
    {
        return [
            'category_id' => 'kategori',
            'service_id' => 'layanan',
            'service_other' => 'detail layanan',
            'module_id' => 'modul',
            'module_other' => 'detail modul',
            'requester_name' => 'nama',
            'requester_email' => 'email',
            'description' => 'deskripsi',
            'attachment' => 'lampiran',
        ];
    }

    public function messages(): array
    {
        return [
            'required' => ':Attribute wajib diisi.',
            'integer' => 'Pilihan :attribute tidak valid.',
            'exists' => 'Pilihan :attribute tidak valid.',
            'category_id.required' => 'Pilih kategori ITPass atau ITInfra.',
            'service_id.required' => 'Pilih layanan yang bermasalah.',
            'module_id.required' => 'Pilih modul yang bermasalah.',
            'service_other.required' => 'Sebutkan layanan yang dimaksud.',
            'module_other.required' => 'Sebutkan modul yang dimaksud.',
            'requester_email.email' => 'Format email tidak valid.',
            'requester_name.max' => 'Nama maksimal 100 karakter.',
            'requester_email.max' => 'Email maksimal 150 karakter.',
            'service_other.max' => 'Maksimal 100 karakter.',
            'module_other.max' => 'Maksimal 100 karakter.',
            'description.min' => 'Deskripsi minimal 10 karakter supaya tim IT bisa memahami kendalanya.',
            'description.max' => 'Deskripsi maksimal 5.000 karakter.',
            'attachment.file' => 'Lampiran gagal diunggah, coba pilih ulang file-nya.',
            'attachment.max' => 'Ukuran lampiran maksimal 5 MB.',
            'attachment.mimes' => 'Lampiran harus berupa JPG, PNG, atau PDF.',
            'website.prohibited' => 'Permintaan tidak dapat diproses.',
        ];
    }

    public function categoryCode(): ?string
    {
        if (! $this->categoryResolved) {
            $id = $this->input('category_id');
            $this->categoryCode = is_numeric($id) ? Category::whereKey((int) $id)->value('code') : null;
            $this->categoryResolved = true;
        }

        return $this->categoryCode;
    }

    private function selectedIsOther(string $model, string $field): bool
    {
        $id = $this->input($field);

        return is_numeric($id) && (bool) $model::whereKey((int) $id)->value('is_other');
    }

    /** Data bersih untuk disimpan; field cabang yang tidak relevan dikosongkan. */
    public function ticketData(): array
    {
        $v = $this->validated();
        $infra = $this->categoryCode() === 'ITINFRA';

        return [
            'category_id' => (int) $v['category_id'],
            'service_id' => $infra ? ($v['service_id'] ?? null) : null,
            'service_other' => $infra && $this->selectedIsOther(Service::class, 'service_id') ? $v['service_other'] : null,
            'module_id' => $infra ? null : ($v['module_id'] ?? null),
            'module_other' => ! $infra && $this->selectedIsOther(Module::class, 'module_id') ? $v['module_other'] : null,
            'requester_name' => $v['requester_name'],
            'requester_email' => $v['requester_email'],
            'description' => $v['description'],
        ];
    }
}
