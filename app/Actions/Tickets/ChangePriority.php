<?php

declare(strict_types=1);

namespace App\Actions\Tickets;

use App\Enums\TicketPriority;
use App\Models\ActivityLog;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class ChangePriority
{
    public function handle(Ticket $ticket, TicketPriority $to, User $actor): Ticket
    {
        $from = $ticket->priority;

        if ($from === $to) {
            return $ticket;
        }

        return DB::transaction(function () use ($ticket, $from, $to, $actor): Ticket {
            $ticket->forceFill(['priority' => $to])->save();
            ActivityLog::record('ticket.priority_changed', $ticket, ['from' => $from->value, 'to' => $to->value], $actor);

            return $ticket;
        });
    }
}
