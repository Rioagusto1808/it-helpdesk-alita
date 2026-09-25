<?php

declare(strict_types=1);

namespace App\Actions\Tickets;

use App\Enums\AuthorType;
use App\Enums\TicketStatus;
use App\Models\Ticket;

/**
 * Tutup tiket "selesai" yang tidak dibalas pemohon selama HELPDESK_AUTO_CLOSE_DAYS hari.
 * Balasan pemohon selalu memindahkan tiket kembali ke "diproses", jadi tiket yang masih "selesai" memang belum dibalas.
 */
final class AutoCloseResolvedTickets
{
    public function __construct(private readonly ChangeTicketStatus $changeStatus) {}

    /** @return int jumlah tiket yang ditutup */
    public function handle(): int
    {
        $closed = 0;

        Ticket::query()
            ->where('status', TicketStatus::Selesai)
            ->where('resolved_at', '<=', now()->subDays(config('helpdesk.auto_close_days')))
            ->orderBy('id')
            ->lazyById(100)
            ->each(function (Ticket $ticket) use (&$closed): void {
                $this->changeStatus->handle(
                    $ticket,
                    TicketStatus::Ditutup,
                    null,
                    'Ditutup otomatis karena tidak ada balasan setelah '.config('helpdesk.auto_close_days').' hari.',
                    AuthorType::System->label(),
                );
                $closed++;
            });

        return $closed;
    }
}
