<?php

declare(strict_types=1);

namespace App\Mail;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Status lama dan baru disimpan di mail (bukan dibaca dari tiket) agar kirim ulang tetap menampilkan perubahan yang sama. */
final class TicketStatusChangedMail extends TicketMail
{
    public function __construct(
        public Ticket $ticket,
        public TicketStatus $previousStatus,
        public TicketStatus $newStatus,
        public ?string $note,
        public string $trackingUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Tiket {$this->ticket->ticket_no} sekarang: {$this->newStatus->label()}");
    }

    public function content(): Content
    {
        return new Content(view: 'emails.tickets.status-changed');
    }
}
