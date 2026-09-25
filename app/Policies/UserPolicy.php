<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

/**
 * Kelola user: admin saja. Aturan "tidak bisa menonaktifkan/menurunkan diri sendiri"
 * dan "minimal satu admin aktif" dicek di Action, bukan di sini.
 */
final class UserPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->isAdmin();
    }

    public function create(User $actor): bool
    {
        return $actor->isAdmin();
    }

    public function update(User $actor, User $user): bool
    {
        return $actor->isAdmin();
    }

    public function toggle(User $actor, User $user): bool
    {
        return $actor->isAdmin();
    }
}
