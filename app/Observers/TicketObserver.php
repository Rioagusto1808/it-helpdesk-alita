<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\AuthorType;
use App\Models\Ticket;
use App\Support\TicketNumber;

final class TicketObserver
{
    public function creating(Ticket $ticket): void
    {
        $ticket->last_activity_at ??= now();
    }

    /** Tiket selalu dibuat oleh pemohon lewat form publik. */
    public function created(Ticket $ticket): void
    {
        $ticket->ticket_no = TicketNumber::for($ticket);
        $ticket->saveQuietly();

        $ticket->statusHistories()->create([
            'from_status' => null,
            'to_status' => $ticket->status,
            'actor_label' => AuthorType::Requester->label(),
        ]);
    }
}
