<?php

declare(strict_types=1);

namespace App\Enums;

enum AuthorType: string
{
    case Requester = 'requester';
    case Agent = 'agent';
    case System = 'system';

    /** Dipakai juga sebagai actor_label saat pelakunya bukan user login. */
    public function label(): string
    {
        return match ($this) {
            self::Requester => 'Pemohon',
            self::Agent => 'Agent',
            self::System => 'Sistem',
        };
    }
}
