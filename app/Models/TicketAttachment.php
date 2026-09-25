<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AuthorType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketAttachment extends Model
{
    /** @var list<string> */
    protected $fillable = ['ticket_id', 'comment_id', 'uploaded_by', 'original_name', 'stored_path', 'mime', 'size'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'uploaded_by' => AuthorType::class,
            'size' => 'integer',
        ];
    }

    /** @return BelongsTo<Ticket, $this> */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /** @return BelongsTo<TicketComment, $this> */
    public function comment(): BelongsTo
    {
        return $this->belongsTo(TicketComment::class, 'comment_id');
    }

    /** Boleh dilihat pemohon: lampiran tiket atau dari komentar yang bukan internal. Butuh relasi comment dimuat. */
    public function isPublic(): bool
    {
        return $this->comment_id === null || $this->comment?->is_internal === false;
    }
}
