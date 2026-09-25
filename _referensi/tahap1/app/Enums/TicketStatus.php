<?php

namespace App\Enums;

enum TicketStatus: string
{
    case Baru = 'baru';
    case Diproses = 'diproses';
    case Menunggu = 'menunggu';
    case Selesai = 'selesai';
    case Ditutup = 'ditutup';
    case Dibatalkan = 'dibatalkan';

    public function label(): string
    {
        return match ($this) {
            self::Baru => 'Baru',
            self::Diproses => 'Diproses',
            self::Menunggu => 'Menunggu respons',
            self::Selesai => 'Selesai',
            self::Ditutup => 'Ditutup',
            self::Dibatalkan => 'Dibatalkan',
        };
    }

    /** Status yang dihitung sebagai "dalam antrian". */
    public static function queued(): array
    {
        return [self::Baru->value, self::Diproses->value];
    }
}
