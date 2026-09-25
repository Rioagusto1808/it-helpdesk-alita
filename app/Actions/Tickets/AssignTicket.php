<?php

declare(strict_types=1);

namespace App\Actions\Tickets;

use App\Enums\TicketStatus;
use App\Models\ActivityLog;
use App\Models\Ticket;
use App\Models\User;
use App\Support\TicketNotifier;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Ambil tiket (assignee = actor) atau assign ke orang lain (admin). $assignee null = lepas penanganan. */
final class AssignTicket
{
    public function __construct(
        private readonly ChangeTicketStatus $changeStatus,
        private readonly TicketNotifier $notifier,
    ) {}

    public function handle(Ticket $ticket, ?User $assignee, User $actor): Ticket
    {
        if ($assignee && ! $assignee->is_active) {
            throw ValidationException::withMessages(['assigned_to' => 'User ini sudah nonaktif dan tidak bisa menerima tiket.']);
        }

        $from = $ticket->assigned_to;

        if ($from === $assignee?->id) {
            return $ticket;
        }

        return DB::transaction(function () use ($ticket, $assignee, $actor, $from): Ticket {
            $ticket->forceFill(['assigned_to' => $assignee?->id, 'last_activity_at' => now()])->save();
            ActivityLog::record('ticket.assigned', $ticket, ['from' => $from, 'to' => $assignee?->id, 'to_name' => $assignee?->name], $actor);

            // PRD: tiket baru otomatis diproses saat agent mengambilnya sendiri.
            if ($assignee?->is($actor) && $ticket->status === TicketStatus::Baru) {
                $this->changeStatus->handle($ticket, TicketStatus::Diproses, $actor);
            }

            if ($assignee && ! $assignee->is($actor)) {
                DB::afterCommit(fn () => $this->notifier->ticketAssigned($ticket, $assignee));
            }

            return $ticket;
        });
    }
}
