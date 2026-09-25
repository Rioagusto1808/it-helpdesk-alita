<?php

declare(strict_types=1);

namespace App\Enums;

enum EmailStatus: string
{
    case Queued = 'queued';
    case Sent = 'sent';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Queued => 'Antri',
            self::Sent => 'Terkirim',
            self::Failed => 'Gagal',
        };
    }

    public function badgeClass(): string
    {
        return 'badge--email-'.$this->value;
    }
}
