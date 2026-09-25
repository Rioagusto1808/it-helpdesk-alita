<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EmailStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailLog extends Model
{
    /** @var list<string> */
    protected $fillable = ['ticket_id', 'to_email', 'subject', 'type', 'status', 'attempts', 'error_message', 'sent_at'];

    /** @var array<string, mixed> */
    protected $attributes = [
        'status' => 'queued',
        'attempts' => 0,
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => EmailStatus::class,
            'attempts' => 'integer',
            'sent_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Ticket, $this> */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }
}
