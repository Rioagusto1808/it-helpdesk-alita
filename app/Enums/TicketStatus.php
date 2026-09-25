<?php

declare(strict_types=1);

namespace App\Enums;

enum TicketStatus: string
{
    case Baru = 'baru';
    case Diproses = 'diproses';
    case Menunggu = 'menunggu';
    case Selesai = 'selesai';
    case Ditutup = 'ditutup';
    case Dibatalkan = 'dibatalkan';

    /** @return list<self> */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Baru => [self::Diproses, self::Dibatalkan],
            self::Diproses => [self::Menunggu, self::Selesai],
            self::Menunggu => [self::Diproses],
            self::Selesai => [self::Diproses, self::Ditutup],
            self::Ditutup, self::Dibatalkan => [],
        };
    }

    public function canTransitionTo(self $to): bool
    {
        return in_array($to, $this->allowedTransitions(), true);
    }

    /** Catatan wajib saat tim IT memindahkan ke status ini (menunggu apa, penyelesaian, alasan batal). */
    public function requiresNote(): bool
    {
        return in_array($this, [self::Menunggu, self::Selesai, self::Dibatalkan], true);
    }

    public function isInQueue(): bool
    {
        return in_array($this, self::queued(), true);
    }

    /** Ditutup/dibatalkan: tidak bisa dibalas atau diubah lagi. */
    public function isFinal(): bool
    {
        return $this->allowedTransitions() === [];
    }

    /**
     * Punya posisi antrian.
     *
     * @return list<self>
     */
    public static function queued(): array
    {
        return [self::Baru, self::Diproses];
    }

    /**
     * Tampil di papan antrian.
     *
     * @return list<self>
     */
    public static function active(): array
    {
        return [self::Baru, self::Diproses, self::Menunggu];
    }

    public function label(): string
    {
        return match ($this) {
            self::Baru => 'Baru',
            self::Diproses => 'Diproses',
            self::Menunggu => 'Menunggu',
            self::Selesai => 'Selesai',
            self::Ditutup => 'Ditutup',
            self::Dibatalkan => 'Dibatalkan',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Ditutup, self::Dibatalkan => 'badge--ditutup',
            default => 'badge--'.$this->value,
        };
    }
}
