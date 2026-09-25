<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Users\CreateUser;
use App\Enums\UserRole;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

final class MakeAdminCommand extends Command
{
    protected $signature = 'helpdesk:make-admin';

    protected $description = 'Buat user admin. Password diketik interaktif dan tidak disimpan di .env.';

    public function handle(CreateUser $createUser): int
    {
        $validator = Validator::make([
            'name' => trim((string) $this->ask('Nama')),
            'email' => Str::lower(trim((string) $this->ask('Email'))),
            'password' => (string) $this->secret('Password (minimal 10 karakter)'),
            'password_confirmation' => (string) $this->secret('Ulangi password'),
        ], [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email:rfc', 'max:150', Rule::unique('users', 'email')],
            'password' => ['required', 'confirmed', Password::min(10)],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $data = $validator->validated();
        $user = $createUser->handle($data['name'], $data['email'], $data['password'], UserRole::Admin);

        $this->info("Admin {$user->email} berhasil dibuat.");

        return self::SUCCESS;
    }
}
