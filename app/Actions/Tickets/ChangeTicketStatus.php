<?php

declare(strict_types=1);

namespace App\Actions\Tickets;

use App\Enums\AuthorType;
use App\Enums\TicketStatus;
use App\Models\ActivityLog;
use App\Models\Ticket;
use App\Models\User;
use App\Support\TicketNotifier;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ChangeTicketStatus
{
    public function __construct(private readonly TicketNotifier $notifier) {}

    /**
     * $actor null = pemohon atau sistem; sebutkan lewat $actorLabel.
     * Catatan wajib (menunggu/selesai/dibatalkan) hanya berlaku untuk tim IT; pemohon dan sistem tidak mengisi catatan.
     */
    public function handle(Ticket $ticket, TicketStatus $to, ?User $actor, ?string $note = null, ?string $actorLabel = null): Ticket
    {
        $from = $ticket->status;

        if (! $from->canTransitionTo($to)) {
            throw ValidationException::withMessages([
                'status' => "Status tidak bisa diubah dari {$from->label()} ke {$to->label()}.",
            ]);
        }

        if ($actor && $to->requiresNote() && blank($note)) {
            throw ValidationException::withMessages(['note' => 'Catatan wajib diisi untuk status ini.']);
        }

        return DB::transaction(function () use ($ticket, $from, $to, $actor, $note, $actorLabel): Ticket {
            $label = $actorLabel ?? $actor->name ?? AuthorType::System->label();

            $ticket->forceFill([
                'status' => $to,
                // Respons pertama hanya dari tim IT, bukan saat pemohon membatalkan.
                'first_response_at' => $ticket->first_response_at ?? ($actor && $from === TicketStatus::Baru ? now() : null),
                'resolved_at' => match ($to) {
                    TicketStatus::Selesai => now(),
                    TicketStatus::Diproses => null,
                    default => $ticket->resolved_at,
                },
                'closed_at' => $to === TicketStatus::Ditutup ? now() : $ticket->closed_at,
                'last_activity_at' => now(),
            ])->save();

            $ticket->statusHistories()->create([
                'from_status' => $from,
                'to_status' => $to,
                'changed_by' => $actor?->id,
                'actor_label' => $label,
                'note' => $note,
            ]);

            ActivityLog::record('ticket.status_changed', $ticket, ['from' => $from->value, 'to' => $to->value], $actor, $label);

            // Pemohon tidak perlu diberi tahu perubahan yang ia picu sendiri (balas/batal).
            if ($label !== AuthorType::Requester->label()) {
                DB::afterCommit(fn () => $this->notifier->statusChanged($ticket, $from, $note));
            }

            return $ticket;
        });
    }
}
