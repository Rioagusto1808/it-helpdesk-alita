<?php

declare(strict_types=1);

namespace App\Actions\Tickets;

use App\Models\ActivityLog;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/** Soft delete: data tetap ada di database dan audit log, tapi hilang dari antrian dan panel. */
final class DeleteTicket
{
    public function handle(Ticket $ticket, User $actor): void
    {
        DB::transaction(function () use ($ticket, $actor): void {
            $ticket->delete();
            ActivityLog::record('ticket.deleted', $ticket, ['ticket_no' => $ticket->ticket_no], $actor);
        });
    }
}
