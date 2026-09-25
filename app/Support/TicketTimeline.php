<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\AuthorType;
use App\Models\EmailLog;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketComment;
use App\Models\TicketStatusHistory;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Riwayat status + komentar (+ email untuk agent) dalam satu urutan waktu, lama ke baru.
 *
 * @phpstan-type Item array{kind: string, dot: string, at: ?Carbon, actor: string, title: string, body: ?string, internal: bool, attachments: array<int, TicketAttachment>}
 */
final class TicketTimeline
{
    /**
     * Versi pemohon: komentar internal dan email tidak pernah ikut.
     *
     * @return Collection<int, Item>
     */
    public static function forRequester(Ticket $ticket): Collection
    {
        return self::build($ticket, false);
    }

    /**
     * Versi panel admin: termasuk komentar internal dan email terkirim/gagal.
     *
     * @return Collection<int, Item>
     */
    public static function forAgent(Ticket $ticket): Collection
    {
        return self::build($ticket, true);
    }

    /** @return Collection<int, Item> */
    private static function build(Ticket $ticket, bool $forAgent): Collection
    {
        // Query eksplisit, bukan relasi yang mungkin sudah dimuat tanpa filter is_internal.
        $comments = $ticket->comments()
            ->when(! $forAgent, fn ($q) => $q->where('is_internal', false))
            ->with(['user:id,name', 'attachments'])
            ->get()
            ->map(fn (TicketComment $c): array => self::comment($c, $forAgent));

        $statuses = $ticket->statusHistories()->get()->map(fn (TicketStatusHistory $h): array => [
            'kind' => 'status',
            'dot' => $h->to_status->badgeClass(),
            'at' => $h->created_at,
            'actor' => $h->actor_label,
            'title' => $h->from_status ? "Status menjadi {$h->to_status->label()}" : 'Tiket dibuat',
            'body' => $h->note,
            'internal' => false,
            'attachments' => [],
        ]);

        $emails = $forAgent ? $ticket->emailLogs()->get()->map(fn (EmailLog $e): array => [
            'kind' => 'email',
            'dot' => 'timeline-dot--email',
            'at' => $e->sent_at ?? $e->created_at,
            'actor' => AuthorType::System->label(),
            'title' => 'Email '.Str::lower($e->status->label()).' ke '.$e->to_email,
            'body' => $e->subject,
            'internal' => false,
            'attachments' => [],
        ]) : new Collection;

        // Sort stabil: pada detik yang sama, balasan tampil sebelum perubahan status yang dipicunya, lalu email.
        return $comments->concat($statuses)->concat($emails)
            ->sortBy(fn (array $item): int => (int) $item['at']?->getTimestamp())
            ->values();
    }

    /** @return Item */
    private static function comment(TicketComment $c, bool $forAgent): array
    {
        $byRequester = $c->author_type === AuthorType::Requester;

        return [
            'kind' => 'comment',
            'dot' => $byRequester ? 'timeline-dot--requester' : 'timeline-dot--agent',
            'at' => $c->created_at,
            'actor' => $c->user->name ?? $c->author_type->label(),
            'title' => match (true) {
                $c->is_internal => 'Catatan internal',
                $forAgent => $byRequester ? 'Balasan pemohon' : 'Balasan ke pemohon',
                default => $byRequester ? 'Balasan kamu' : 'Balasan tim IT',
            },
            'body' => $c->body,
            'internal' => $c->is_internal,
            'attachments' => $c->attachments->all(),
        ];
    }
}
