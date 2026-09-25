<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\Module;
use App\Models\Service;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Tambah/ubah layanan (ITInfra) atau modul (ITApps). */
final class MasterItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $item = $this->route('service') ?? $this->route('module');
        $table = $this->routeIs('admin.services.*') ? 'services' : 'modules';

        return [
            'name' => ['required', 'string', 'max:100', Rule::unique($table, 'name')->ignore($item instanceof Service || $item instanceof Module ? $item->id : null)],
            // 999 dicadangkan untuk "Others" yang selalu paling bawah.
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:998'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['name' => 'nama'];
    }
}
