<?php

declare(strict_types=1);

namespace App\Actions\Users;

use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** User tidak pernah dihapus, hanya dinonaktifkan. Session aktifnya diputus oleh middleware "active". */
final class ToggleUserActive
{
    public function handle(User $user, User $actor): User
    {
        if ($user->is_active) {
            match (true) {
                $user->is($actor) => throw ValidationException::withMessages(['user' => 'Kamu tidak bisa menonaktifkan akunmu sendiri.']),
                $user->role === UserRole::Admin && ! User::query()->activeAdmins()->whereKeyNot($user->id)->exists() => throw ValidationException::withMessages(['user' => 'Sistem harus punya minimal satu admin aktif.']),
                default => null,
            };
        }

        return DB::transaction(function () use ($user, $actor): User {
            $user->forceFill(['is_active' => ! $user->is_active])->save();
            ActivityLog::record('user.toggled', $user, ['is_active' => $user->is_active], $actor);

            return $user;
        });
    }
}
