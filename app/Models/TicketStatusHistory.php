<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TicketStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Append-only: tidak ada update atau delete dari aplikasi. */
class TicketStatusHistory extends Model
{
    public const UPDATED_AT = null;

    /** @var list<string> */
    protected $fillable = ['ticket_id', 'from_status', 'to_status', 'changed_by', 'actor_label', 'note'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'from_status' => TicketStatus::class,
            'to_status' => TicketStatus::class,
        ];
    }

    /** @return BelongsTo<Ticket, $this> */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /** @return BelongsTo<User, $this> */
    public function changer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
