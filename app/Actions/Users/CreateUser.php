<?php

declare(strict_types=1);

namespace App\Actions\Users;

use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

final class CreateUser
{
    /**
     * $password null: akun dibuat dengan password acak, lalu user menerima link untuk membuat password sendiri.
     * $actor null: dibuat lewat CLI, tercatat sebagai "Sistem".
     */
    public function handle(string $name, string $email, ?string $password, UserRole $role, ?User $actor = null): User
    {
        return DB::transaction(function () use ($name, $email, $password, $role, $actor): User {
            $user = User::query()->create([
                'name' => $name,
                'email' => Str::lower($email),
                'password' => $password ?? Str::password(40),
                'role' => $role,
                'is_active' => true,
            ]);

            ActivityLog::record('user.created', $user, ['email' => $user->email, 'role' => $role->value, 'set_password_link' => $password === null], $actor);

            if ($password === null) {
                DB::afterCommit(fn () => Password::sendResetLink(['email' => $user->email]));
            }

            return $user;
        });
    }
}
