<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\User;

/** Menu sidebar admin. Item admin-only tidak ditampilkan ke agent (aksesnya tetap dijaga middleware/Policy). */
final class AdminMenu
{
    /** [route, pola aktif, label, ikon, khusus admin] */
    private const ITEMS = [
        ['admin.dashboard', 'admin.dashboard', 'Dashboard', 'grid', false],
        ['admin.tickets.index', 'admin.tickets.*', 'Tiket', 'ticket', false],
        ['admin.services.index', 'admin.services.*', 'Layanan', 'server', true],
        ['admin.modules.index', 'admin.modules.*', 'Modul', 'puzzle', true],
        ['admin.users.index', 'admin.users.*', 'User', 'users', true],
        ['admin.logs.activity', 'admin.logs.activity', 'Audit log', 'list', true],
        ['admin.logs.email', 'admin.logs.email*', 'Email log', 'mail', true],
    ];

    /** @return list<array{url: string, active: bool, label: string, icon: string}> */
    public static function for(User $user): array
    {
        $items = [];

        foreach (self::ITEMS as [$route, $pattern, $label, $icon, $adminOnly]) {
            if ($adminOnly && ! $user->isAdmin()) {
                continue;
            }

            $items[] = ['url' => route($route), 'active' => request()->routeIs($pattern), 'label' => $label, 'icon' => $icon];
        }

        return $items;
    }
}
