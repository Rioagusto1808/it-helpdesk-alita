<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Ticket;

final class TicketNumber
{
    /** IT-YYYYMMDD-NNNNN; memakai ID sehingga unik tanpa locking. */
    public static function for(Ticket $ticket): string
    {
        return sprintf('IT-%s-%05d', $ticket->created_at?->format('Ymd'), $ticket->id);
    }
}
