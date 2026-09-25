<?php

namespace App\Mail;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewTicketAdminMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Ticket $ticket) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: sprintf('[Tiket baru] %s - %s / %s',
                $this->ticket->ticket_no,
                $this->ticket->category->name,
                $this->ticket->typeLabel()
            ),
            // Klik "Reply" di email langsung membalas ke pemohon
            replyTo: [new Address($this->ticket->requester_email, $this->ticket->requester_name)],
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.tickets.admin-new');
    }
}
