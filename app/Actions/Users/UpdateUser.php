<?php

declare(strict_types=1);

namespace App\Actions\Users;

use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class UpdateUser
{
    /** $password null = password tidak diganti. */
    public function handle(User $user, string $name, string $email, UserRole $role, ?string $password, User $actor): User
    {
        if ($user->role === UserRole::Admin && $role !== UserRole::Admin) {
            match (true) {
                $user->is($actor) => throw ValidationException::withMessages(['role' => 'Kamu tidak bisa menurunkan peran akunmu sendiri.']),
                $user->is_active && ! User::query()->activeAdmins()->whereKeyNot($user->id)->exists() => throw ValidationException::withMessages(['role' => 'Sistem harus punya minimal satu admin aktif.']),
                default => null,
            };
        }

        return DB::transaction(function () use ($user, $name, $email, $role, $password, $actor): User {
            $user->fill(['name' => $name, 'email' => Str::lower($email), 'role' => $role]);
            if ($password !== null) {
                $user->password = $password;
            }

            $changed = array_keys($user->getDirty());
            $user->save();

            // Nama kolom yang berubah saja; nilai password tidak pernah dicatat.
            ActivityLog::record('user.updated', $user, ['changed' => $changed], $actor);

            return $user;
        });
    }
}
