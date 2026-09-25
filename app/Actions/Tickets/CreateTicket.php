<?php

declare(strict_types=1);

namespace App\Actions\Tickets;

use App\Enums\AuthorType;
use App\Models\ActivityLog;
use App\Models\Ticket;
use App\Support\Attachments;
use App\Support\TicketNotifier;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

final class CreateTicket
{
    public function __construct(private readonly TicketNotifier $notifier) {}

    /** @param array<string, mixed> $data data tervalidasi dari StoreTicketRequest::ticketData() */
    public function handle(array $data, ?UploadedFile $attachment, ?string $ip): Ticket
    {
        return Attachments::storeThen($attachment, fn (?string $path): Ticket => DB::transaction(function () use ($data, $attachment, $path, $ip): Ticket {
            $ticket = Ticket::query()->create([...$data, 'ip_address' => $ip]);

            if ($attachment && $path) {
                $ticket->attachments()->create(Attachments::record($attachment, $path, AuthorType::Requester));
            }

            ActivityLog::record('ticket.created', $ticket, ['ticket_no' => $ticket->ticket_no], actorLabel: AuthorType::Requester->label());

            DB::afterCommit(fn () => $this->notifier->ticketCreated($ticket));

            return $ticket;
        }));
    }
}
