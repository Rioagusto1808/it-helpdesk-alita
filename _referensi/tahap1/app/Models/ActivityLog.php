<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

class ActivityLog extends Model
{
    public $timestamps = false;

    protected $fillable = ['actor_id', 'action', 'subject_type', 'subject_id', 'ip_address', 'user_agent', 'properties'];

    protected $casts = ['properties' => 'array', 'created_at' => 'datetime'];

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /** Catat satu aksi ke audit log. */
    public static function record(string $action, ?Model $subject = null, array $properties = []): self
    {
        $request = request();

        return static::create([
            'actor_id' => auth()->id(),
            'action' => $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'ip_address' => $request?->ip(),
            'user_agent' => Str::limit((string) $request?->userAgent(), 250, ''),
            'properties' => $properties ?: null,
        ]);
    }
}
