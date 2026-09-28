<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\AuthorType;
use App\Enums\EmailStatus;
use App\Enums\TicketStatus;
use App\Jobs\SendTicketEmail;
use App\Mail\NewTicketAdminMail;
use App\Mail\RequesterRepliedMail;
use App\Mail\TicketAssignedMail;
use App\Mail\TicketCommentMail;
use App\Mail\TicketCreatedMail;
use App\Mail\TicketMail;
use App\Mail\TicketStatusChangedMail;
use App\Mail\TrackingLinkMail;
use App\Models\EmailLog;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\TicketStatusHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

/** Semua email tiket lewat sini: catat di email_logs, lalu kirim lewat queue. Email ke pemohon selalu membawa link tracking baru. */
final class TicketNotifier
{
    public function ticketCreated(Ticket $ticket): void
    {
        $ticket->loadMissing(['category', 'service', 'module', 'attachments']);
        $position = QueuePosition::for($ticket);

        foreach (config('helpdesk.admin_emails') as $adminEmail) {
            $this->send($ticket, $adminEmail, 'admin_new_ticket', new NewTicketAdminMail($ticket, $position, $this->adminUrl($ticket)));
        }

        $this->send($ticket, $ticket->requester_email, 'requester_ticket_created', new TicketCreatedMail($ticket, $position, TrackingUrl::make($ticket)));
    }

    public function statusChanged(Ticket $ticket, TicketStatus $from, ?string $note): void
    {
        $this->send($ticket, $ticket->requester_email, 'requester_status_changed', new TicketStatusChangedMail($ticket, $from, $ticket->status, $note, TrackingUrl::make($ticket)));
    }

    /**
     * Kirim ulang email yang gagal. Isi email disusun ulang dari data tiket pada saat email itu dibuat
     * (riwayat/komentar terakhir sebelum waktu email_logs.created_at), ke alamat tujuan yang sama.
     */
    public function retry(EmailLog $log): void
    {
        $ticket = $log->ticket;
        $mail = $ticket ? $this->rebuild($ticket, $log) : null;

        if ($mail === null) {
            throw ValidationException::withMessages(['email' => 'Email ini tidak bisa dikirim ulang karena tiket atau datanya sudah tidak ada.']);
        }

        $log->update(['status' => EmailStatus::Queued, 'error_message' => null]);
        SendTicketEmail::dispatch($log->id, $mail);
    }

    private function rebuild(Ticket $ticket, EmailLog $log): ?TicketMail
    {
        $ticket->loadMissing(['category', 'service', 'module', 'attachments']);
        $latestBefore = fn (HasMany $relation): ?Model => $relation->where('created_at', '<=', $log->created_at)->latest('id')->first();

        return match ($log->type) {
            'admin_new_ticket' => new NewTicketAdminMail($ticket, QueuePosition::for($ticket), $this->adminUrl($ticket)),
            'requester_ticket_created' => new TicketCreatedMail($ticket, QueuePosition::for($ticket), TrackingUrl::make($ticket)),
            'requester_status_changed' => ($h = $latestBefore($ticket->statusHistories()->whereNotNull('from_status'))) instanceof TicketStatusHistory && $h->from_status
                ? new TicketStatusChangedMail($ticket, $h->from_status, $h->to_status, $h->note, TrackingUrl::make($ticket))
                : null,
            'requester_comment' => ($c = $latestBefore($ticket->comments()->where('author_type', AuthorType::Agent)->where('is_internal', false))) instanceof TicketComment
                ? new TicketCommentMail($ticket, $c, TrackingUrl::make($ticket))
                : null,
            'agent_requester_replied' => ($c = $latestBefore($ticket->comments()->where('author_type', AuthorType::Requester))) instanceof TicketComment
                ? new RequesterRepliedMail($ticket, $c, $this->adminUrl($ticket))
                : null,
            'agent_ticket_assigned' => new TicketAssignedMail($ticket, $this->adminUrl($ticket)),
            'requester_tracking_link' => new TrackingLinkMail($ticket, TrackingUrl::make($ticket)),
            default => null,
        };
    }

    /** Hanya komentar publik agent; komentar internal tidak pernah sampai ke sini. $newStatus: balasan dari modal yang ikut mengubah status. */
    public function commentAdded(Ticket $ticket, TicketComment $comment, ?TicketStatus $newStatus = null): void
    {
        $comment->loadMissing(['user:id,name', 'attachments']);

        $this->send($ticket, $ticket->requester_email, 'requester_comment', new TicketCommentMail($ticket, $comment, TrackingUrl::make($ticket), $newStatus));
    }

    /** Ke agent yang menangani, atau semua admin jika belum di-assign. */
    public function requesterReplied(Ticket $ticket, TicketComment $comment): void
    {
        $ticket->loadMissing('assignee');
        $recipients = $ticket->assignee ? [$ticket->assignee->email] : config('helpdesk.admin_emails');

        foreach ($recipients as $email) {
            $this->send($ticket, $email, 'agent_requester_replied', new RequesterRepliedMail($ticket, $comment, $this->adminUrl($ticket)));
        }
    }

    public function ticketAssigned(Ticket $ticket, User $assignee): void
    {
        $ticket->loadMissing(['category', 'service', 'module']);

        $this->send($ticket, $assignee->email, 'agent_ticket_assigned', new TicketAssignedMail($ticket, $this->adminUrl($ticket)));
    }

    public function trackingLink(Ticket $ticket): void
    {
        $this->send($ticket, $ticket->requester_email, 'requester_tracking_link', new TrackingLinkMail($ticket, TrackingUrl::make($ticket)));
    }

    private function adminUrl(Ticket $ticket): string
    {
        return route('admin.tickets.show', $ticket);
    }

    private function send(Ticket $ticket, string $to, string $type, TicketMail $mail): void
    {
        $log = EmailLog::query()->create([
            'ticket_id' => $ticket->id,
            'to_email' => $to,
            'subject' => (string) $mail->envelope()->subject,
            'type' => $type,
        ]);

        SendTicketEmail::dispatch($log->id, $mail);
    }
}
