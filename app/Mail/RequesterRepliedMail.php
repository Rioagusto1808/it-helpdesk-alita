<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Ticket;
use App\Models\TicketComment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

final class RequesterRepliedMail extends TicketMail
{
    public function __construct(public Ticket $ticket, public TicketComment $comment, public string $adminUrl) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Pemohon membalas tiket {$this->ticket->ticket_no}");
    }

    public function content(): Content
    {
        return new Content(view: 'emails.tickets.requester-replied');
    }
}
