<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailLog extends Model
{
    protected $fillable = ['ticket_id', 'to_email', 'subject', 'type', 'status', 'error_message', 'sent_at'];

    protected $casts = ['sent_at' => 'datetime'];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }
}
