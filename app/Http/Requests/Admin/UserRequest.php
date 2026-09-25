<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/** Tambah dan ubah user. Password kosong: saat tambah dikirim link buat password, saat ubah tidak diganti. */
final class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->route('user');

        return $user instanceof User
            ? (bool) $this->user()?->can('update', $user)
            : (bool) $this->user()?->can('create', User::class);
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge(['email' => Str::lower(trim($this->input('email')))]);
        }
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $user = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email:rfc', 'max:150', Rule::unique('users', 'email')->ignore($user instanceof User ? $user->id : null)],
            'role' => ['required', Rule::enum(UserRole::class)],
            'password' => ['nullable', 'string', Password::min(10)],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['password' => 'password awal'];
    }

    public function role(): UserRole
    {
        return UserRole::from($this->validated('role'));
    }
}
