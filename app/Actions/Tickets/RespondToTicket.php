<?php

declare(strict_types=1);

namespace App\Actions\Tickets;

use App\Enums\AuthorType;
use App\Enums\TicketStatus;
use App\Models\ActivityLog;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use App\Support\TicketNotifier;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Balasan tim IT dari modal daftar tiket: pesan (+ lampiran) sebagai komentar publik, status baru jika berubah,
 * lalu satu email ke pemohon berisi keduanya dengan file terlampir.
 */
final class RespondToTicket
{
    public function __construct(
        private readonly AddComment $addComment,
        private readonly ChangeTicketStatus $changeStatus,
        private readonly TicketNotifier $notifier,
    ) {}

    public function handle(Ticket $ticket, TicketStatus $to, string $message, User $actor, ?UploadedFile $attachment = null): TicketComment
    {
        $from = $ticket->status;
        $changes = $from !== $to;

        // Dicek sebelum lampiran disimpan, agar transisi yang ditolak tidak meninggalkan file.
        if (! in_array($to, TicketStatus::responses(), true) || ($changes && ! $from->canTransitionTo($to))) {
            throw ValidationException::withMessages([
                'status' => "Status tidak bisa diubah dari {$from->label()} ke {$to->responseLabel()}.",
            ]);
        }

        return DB::transaction(function () use ($ticket, $to, $changes, $message, $actor, $attachment): TicketComment {
            // Tanpa panel assign: yang pertama membalas otomatis menjadi petugas tiket.
            if ($ticket->assigned_to === null) {
                $ticket->forceFill(['assigned_to' => $actor->id])->save();
                ActivityLog::record('ticket.assigned', $ticket, ['from' => null, 'to' => $actor->id, 'to_name' => $actor->name], $actor);
            }

            // Komentar dulu: setelah ditolak (dibatalkan) tiket sudah final dan tidak bisa dibalas lagi.
            $comment = $this->addComment->handle($ticket, $message, AuthorType::Agent, $actor, attachment: $attachment, notify: false);

            if ($changes) {
                $this->changeStatus->handle($ticket, $to, $actor, notify: false);
            }

            DB::afterCommit(fn () => $this->notifier->commentAdded($ticket, $comment, $changes ? $to : null));

            return $comment;
        });
    }
}
