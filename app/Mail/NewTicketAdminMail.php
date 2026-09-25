<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Ticket;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

final class NewTicketAdminMail extends TicketMail
{
    public function __construct(public Ticket $ticket, public ?int $position, public string $adminUrl) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: sprintf('[Tiket baru] %s - %s / %s', $this->ticket->ticket_no, $this->ticket->category->name, $this->ticket->typeLabel()),
            // "Reply" di email langsung membalas ke pemohon.
            replyTo: [new Address($this->ticket->requester_email, $this->ticket->requester_name)],
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.tickets.admin-new');
    }
}
