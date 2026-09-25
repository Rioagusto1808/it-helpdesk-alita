<?php

declare(strict_types=1);

namespace App\Enums;

/** Urutan case = urutan antrian: yang di atas dilayani lebih dulu. */
enum TicketPriority: string
{
    case Urgent = 'urgent';
    case Tinggi = 'tinggi';
    case Sedang = 'sedang';
    case Rendah = 'rendah';

    /** @return list<self> */
    public function higherPriorities(): array
    {
        return array_slice(self::cases(), 0, (int) array_search($this, self::cases(), true));
    }

    public function label(): string
    {
        return match ($this) {
            self::Urgent => 'Urgent',
            self::Tinggi => 'Tinggi',
            self::Sedang => 'Sedang',
            self::Rendah => 'Rendah',
        };
    }

    public function badgeClass(): string
    {
        return 'badge--'.$this->value;
    }
}
