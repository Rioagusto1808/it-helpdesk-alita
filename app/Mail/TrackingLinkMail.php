<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Ticket;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

final class TrackingLinkMail extends TicketMail
{
    public function __construct(public Ticket $ticket, public string $trackingUrl) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Link tiket {$this->ticket->ticket_no}");
    }

    public function content(): Content
    {
        return new Content(view: 'emails.tickets.tracking-link');
    }
}
