<?php

declare(strict_types=1);

namespace App\Actions\Tickets;

use App\Enums\AuthorType;
use App\Models\ActivityLog;
use App\Models\Ticket;
use App\Models\User;
use App\Support\TicketNotifier;

/** Kirim link tracking baru ke email pemohon (dari halaman /lacak, atau oleh agent di M5). */
final class SendTrackingLink
{
    public function __construct(private readonly TicketNotifier $notifier) {}

    public function handle(Ticket $ticket, ?User $actor = null): void
    {
        ActivityLog::record('ticket.tracking_link_sent', $ticket, [], $actor, $actor ? null : AuthorType::Requester->label());

        $this->notifier->trackingLink($ticket);
    }
}
