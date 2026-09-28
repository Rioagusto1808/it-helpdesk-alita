<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TicketStatusEnumTest extends TestCase
{
    /** @return array<string, array{TicketStatus, TicketStatus}> */
    public static function allowedTransitions(): array
    {
        return [
            'baru → diproses' => [TicketStatus::Baru, TicketStatus::Diproses],
            'baru → selesai' => [TicketStatus::Baru, TicketStatus::Selesai],
            'baru → dibatalkan' => [TicketStatus::Baru, TicketStatus::Dibatalkan],
            'diproses → menunggu' => [TicketStatus::Diproses, TicketStatus::Menunggu],
            'diproses → selesai' => [TicketStatus::Diproses, TicketStatus::Selesai],
            'diproses → dibatalkan' => [TicketStatus::Diproses, TicketStatus::Dibatalkan],
            'menunggu → diproses' => [TicketStatus::Menunggu, TicketStatus::Diproses],
            'menunggu → selesai' => [TicketStatus::Menunggu, TicketStatus::Selesai],
            'menunggu → dibatalkan' => [TicketStatus::Menunggu, TicketStatus::Dibatalkan],
            'selesai → diproses' => [TicketStatus::Selesai, TicketStatus::Diproses],
            'selesai → ditutup' => [TicketStatus::Selesai, TicketStatus::Ditutup],
        ];
    }

    #[DataProvider('allowedTransitions')]
    public function test_allowed_transition_is_permitted(TicketStatus $from, TicketStatus $to): void
    {
        $this->assertTrue($from->canTransitionTo($to));
    }

    public function test_only_listed_transitions_are_permitted(): void
    {
        $allowed = array_map(fn (array $pair): string => $pair[0]->value.'>'.$pair[1]->value, self::allowedTransitions());

        foreach (TicketStatus::cases() as $from) {
            foreach (TicketStatus::cases() as $to) {
                $expected = in_array($from->value.'>'.$to->value, $allowed, true);
                $this->assertSame($expected, $from->canTransitionTo($to), "{$from->value} → {$to->value}");
            }
        }
    }

    public function test_waiting_resolved_and_cancelled_require_note(): void
    {
        $this->assertSame(
            [TicketStatus::Menunggu, TicketStatus::Selesai, TicketStatus::Dibatalkan],
            array_values(array_filter(TicketStatus::cases(), fn (TicketStatus $s): bool => $s->requiresNote())),
        );
    }

    public function test_higher_priorities_follow_case_order(): void
    {
        $this->assertSame([], TicketPriority::Urgent->higherPriorities());
        $this->assertSame([TicketPriority::Urgent, TicketPriority::Tinggi], TicketPriority::Sedang->higherPriorities());
    }
}
