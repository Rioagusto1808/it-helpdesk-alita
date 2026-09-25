<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Ticket;
use Illuminate\Database\Eloquent\Builder;

final class QueuePosition
{
    /** Posisi 1-based: prioritas lebih tinggi dulu, lalu ID terkecil. Null jika tiket tidak di antrian. */
    public static function for(Ticket $ticket): ?int
    {
        if (! $ticket->status->isInQueue()) {
            return null;
        }

        $ahead = Ticket::query()
            ->inQueue()
            ->where(fn (Builder $query) => $query
                ->whereIn('priority', $ticket->priority->higherPriorities())
                ->orWhere(fn (Builder $query) => $query
                    ->where('priority', $ticket->priority)
                    ->where('id', '<', $ticket->id)))
            ->count();

        return $ahead + 1;
    }
}
