<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AuthorType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TicketComment extends Model
{
    /** @var list<string> */
    protected $fillable = ['ticket_id', 'user_id', 'author_type', 'body', 'is_internal'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'author_type' => AuthorType::class,
            'is_internal' => 'boolean',
        ];
    }

    /** @return BelongsTo<Ticket, $this> */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<TicketAttachment, $this> */
    public function attachments(): HasMany
    {
        return $this->hasMany(TicketAttachment::class, 'comment_id');
    }
}
