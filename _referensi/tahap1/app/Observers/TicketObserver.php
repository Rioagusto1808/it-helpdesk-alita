<?php

namespace App\Observers;

use App\Models\Ticket;

class TicketObserver
{
    public function created(Ticket $ticket): void
    {
        // Format: IT-20260924-00042 (tanggal + ID, dijamin unik tanpa race condition)
        $ticket->ticket_no = sprintf('IT-%s-%05d', $ticket->created_at->format('Ymd'), $ticket->id);
        $ticket->saveQuietly();

        $ticket->statusHistories()->create([
            'from_status' => null,
            'to_status' => $ticket->status->value,
            'changed_by' => auth()->id(),
            'note' => 'Tiket dibuat',
        ]);
    }

    public function updated(Ticket $ticket): void
    {
        if ($ticket->wasChanged('status')) {
            $ticket->statusHistories()->create([
                'from_status' => $ticket->getRawOriginal('status'),
                'to_status' => $ticket->status->value,
                'changed_by' => auth()->id(),
            ]);
        }
    }
}
