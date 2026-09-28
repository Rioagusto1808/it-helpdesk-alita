<?php

declare(strict_types=1);

namespace App\Mail;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketComment;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Balasan tim IT ke pemohon; lampiran komentar ikut dikirim sebagai file. */
final class TicketCommentMail extends TicketMail
{
    public function __construct(
        public Ticket $ticket,
        public TicketComment $comment,
        public string $trackingUrl,
        public ?TicketStatus $newStatus = null,
    ) {}

    public function envelope(): Envelope
    {
        $status = $this->newStatus ? " ({$this->newStatus->label()})" : '';

        return new Envelope(subject: "Balasan untuk tiket {$this->ticket->ticket_no}{$status}");
    }

    public function content(): Content
    {
        return new Content(view: 'emails.tickets.comment');
    }

    /** @return list<Attachment> */
    public function attachments(): array
    {
        return $this->comment->attachments
            ->map(fn (TicketAttachment $file): Attachment => Attachment::fromStorageDisk('local', $file->stored_path)
                ->as($file->original_name)
                ->withMime($file->mime))
            ->values()
            ->all();
    }
}
