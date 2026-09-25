<?php

declare(strict_types=1);

namespace App\Actions\Tickets;

use App\Enums\AuthorType;
use App\Enums\TicketStatus;
use App\Models\ActivityLog;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use App\Support\Attachments;
use App\Support\TicketNotifier;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Komentar dari pemohon (halaman tracking) atau agent (panel admin). */
final class AddComment
{
    public function __construct(
        private readonly ChangeTicketStatus $changeStatus,
        private readonly TicketNotifier $notifier,
    ) {}

    public function handle(Ticket $ticket, string $body, AuthorType $author, ?User $actor = null, bool $internal = false, ?UploadedFile $attachment = null): TicketComment
    {
        if ($ticket->status->isFinal()) {
            throw ValidationException::withMessages(['body' => 'Tiket ini sudah selesai diproses dan tidak bisa dibalas lagi.']);
        }

        return Attachments::storeThen($attachment, fn (?string $path): TicketComment => DB::transaction(
            function () use ($ticket, $body, $author, $actor, $internal, $attachment, $path): TicketComment {
                $comment = $ticket->comments()->create([
                    'user_id' => $actor?->id,
                    'author_type' => $author,
                    'body' => $body,
                    'is_internal' => $internal,
                ]);

                if ($attachment && $path) {
                    $ticket->attachments()->create([...Attachments::record($attachment, $path, $author), 'comment_id' => $comment->id]);
                }

                $ticket->forceFill([
                    'last_activity_at' => now(),
                    'first_response_at' => $ticket->first_response_at ?? ($author === AuthorType::Agent && ! $internal ? now() : null),
                ])->save();

                // Pemohon membalas saat menunggu/selesai = kendala belum beres, kembali dikerjakan.
                if ($author === AuthorType::Requester && in_array($ticket->status, [TicketStatus::Menunggu, TicketStatus::Selesai], true)) {
                    $this->changeStatus->handle($ticket, TicketStatus::Diproses, null, actorLabel: AuthorType::Requester->label());
                }

                ActivityLog::record('ticket.comment_added', $ticket, ['author' => $author->value, 'internal' => $internal], $actor, $actor ? null : $author->label());

                // Komentar internal tidak pernah dikirim ke pemohon.
                match (true) {
                    $author === AuthorType::Requester => DB::afterCommit(fn () => $this->notifier->requesterReplied($ticket, $comment)),
                    ! $internal => DB::afterCommit(fn () => $this->notifier->commentAdded($ticket, $comment)),
                    default => null,
                };

                return $comment;
            },
        ));
    }
}
