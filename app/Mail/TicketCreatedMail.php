<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Ticket;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

final class TicketCreatedMail extends TicketMail
{
    public function __construct(public Ticket $ticket, public ?int $position, public string $trackingUrl) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Tiket {$this->ticket->ticket_no} sudah kami terima");
    }

    public function content(): Content
    {
        return new Content(view: 'emails.tickets.requester-created');
    }
}
