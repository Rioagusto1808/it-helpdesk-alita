<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesAttachment;
use App\Models\Category;
use App\Models\Module;
use App\Models\Service;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

final class StoreTicketRequest extends FormRequest
{
    use ValidatesAttachment;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('requester_email'))) {
            $this->merge(['requester_email' => Str::lower($this->input('requester_email'))]);
        }
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $code = $this->categoryCode();
        $domains = config('helpdesk.allowed_email_domains');

        // Field cabang yang tidak relevan di-exclude, sehingga tidak ikut tersimpan.
        return [
            'website' => ['prohibited'],
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')],
            'service_id' => [
                Rule::excludeIf($code !== Category::ITINFRA), 'required', 'integer',
                Rule::exists('services', 'id')->where('is_active', true),
            ],
            'service_other' => [
                Rule::excludeIf(fn (): bool => $code !== Category::ITINFRA || ! $this->isOther(Service::class, 'service_id')),
                'required', 'string', 'max:100',
            ],
            'module_id' => [
                Rule::excludeIf($code !== Category::ITAPPS), 'required', 'integer',
                Rule::exists('modules', 'id')->where('is_active', true),
            ],
            'module_other' => [
                Rule::excludeIf(fn (): bool => $code !== Category::ITAPPS || ! $this->isOther(Module::class, 'module_id')),
                'required', 'string', 'max:100',
            ],
            'requester_name' => ['required', 'string', 'max:100'],
            'requester_email' => array_filter([
                'required', 'email:rfc', 'max:150',
                $domains ? 'ends_with:'.implode(',', array_map(fn (string $d): string => '@'.$d, $domains)) : null,
            ]),
            'description' => ['required', 'string', 'min:10', 'max:5000'],
            'attachment' => $this->attachmentRules(),
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'category_id.required' => 'Pilih kategori ITApps atau ITInfra.',
            'service_id.required' => 'Pilih layanan yang bermasalah.',
            'module_id.required' => 'Pilih modul yang bermasalah.',
            'service_other.required' => 'Sebutkan layanan yang dimaksud.',
            'module_other.required' => 'Sebutkan modul yang dimaksud.',
            'requester_email.email' => 'Format email tidak valid.',
            'requester_email.ends_with' => 'Gunakan email kantor (:values).',
            'description.min' => 'Deskripsi minimal 10 karakter supaya tim IT bisa memahami kendalanya.',
            'website.prohibited' => 'Permintaan tidak dapat diproses.',
            ...$this->attachmentMessages(),
        ];
    }

    /** @return array<string, mixed> */
    public function ticketData(): array
    {
        return [
            'service_id' => null,
            'service_other' => null,
            'module_id' => null,
            'module_other' => null,
            ...Arr::except($this->validated(), ['website', 'attachment']),
        ];
    }

    private function categoryCode(): ?string
    {
        return once(fn (): ?string => is_numeric($this->input('category_id'))
            ? Category::query()->whereKey((int) $this->input('category_id'))->value('code')
            : null);
    }

    /** @param class-string<Service|Module> $model */
    private function isOther(string $model, string $field): bool
    {
        $id = $this->input($field);

        return is_numeric($id) && (bool) $model::query()->whereKey((int) $id)->value('is_other');
    }
}
