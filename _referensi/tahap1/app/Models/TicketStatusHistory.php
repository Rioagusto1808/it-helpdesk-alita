<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketStatusHistory extends Model
{
    public $timestamps = false; // created_at diisi default database

    protected $fillable = ['ticket_id', 'from_status', 'to_status', 'changed_by', 'note'];

    protected $casts = ['created_at' => 'datetime'];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function changer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
