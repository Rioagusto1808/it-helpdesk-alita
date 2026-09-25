<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\TicketStatus;
use App\Models\Ticket;

/**
 * Data papan antrian publik. Sengaja hanya berisi nomor, kategori, layanan/modul, status, dan waktu:
 * tidak pernah nama, email, deskripsi, atau nama agent.
 */
final class QueueBoard
{
    /**
     * @return array{
     *     summary: array{baru: int, diproses: int, menunggu: int, selesai_hari_ini: int},
     *     rows: list<array{position: ?int, ticket_no: string, category: string, category_code: string, type: string, status: string, status_label: string, status_badge: string, created_at: string, created_human: string}>,
     *     more: int
     * }
     */
    public static function snapshot(?string $categoryCode = null): array
    {
        $tickets = Ticket::query()
            ->with(['category:id,code,name', 'service:id,name,is_other', 'module:id,name,is_other'])
            ->whereIn('status', TicketStatus::active())
            ->queueOrder()
            ->get(['id', 'ticket_no', 'category_id', 'service_id', 'service_other', 'module_id', 'module_other', 'status', 'priority', 'created_at']);

        $position = 0;
        $rows = $tickets
            // Closure biasa (bukan fn) karena penghitung posisi harus by-reference.
            ->map(function (Ticket $ticket) use (&$position): array {
                return self::row($ticket, $ticket->status->isInQueue() ? ++$position : null);
            })
            ->filter(fn (array $row): bool => $categoryCode === null || $row['category_code'] === $categoryCode)
            ->values();

        $counts = $tickets->countBy(fn (Ticket $ticket): string => $ticket->status->value);
        $limit = config('helpdesk.queue_board_limit');

        return [
            'summary' => [
                'baru' => $counts->get(TicketStatus::Baru->value, 0),
                'diproses' => $counts->get(TicketStatus::Diproses->value, 0),
                'menunggu' => $counts->get(TicketStatus::Menunggu->value, 0),
                'selesai_hari_ini' => Ticket::query()->where('resolved_at', '>=', today())->count(),
            ],
            'rows' => $rows->take($limit)->all(),
            'more' => max(0, $rows->count() - $limit),
        ];
    }

    /** @return array{position: ?int, ticket_no: string, category: string, category_code: string, type: string, status: string, status_label: string, status_badge: string, created_at: string, created_human: string} */
    private static function row(Ticket $ticket, ?int $position): array
    {
        return [
            'position' => $position,
            'ticket_no' => (string) $ticket->ticket_no,
            'category' => $ticket->category->name,
            'category_code' => $ticket->category->code,
            'type' => $ticket->typeLabel(),
            'status' => $ticket->status->value,
            'status_label' => $ticket->status->label(),
            'status_badge' => $ticket->status->badgeClass(),
            'created_at' => (string) $ticket->created_at?->toIso8601String(),
            'created_human' => (string) $ticket->created_at?->diffForHumans(),
        ];
    }
}
