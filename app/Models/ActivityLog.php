<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AuthorType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/** Append-only: tidak ada update atau delete dari aplikasi. */
class ActivityLog extends Model
{
    public const UPDATED_AT = null;

    /** @var list<string> */
    protected $fillable = ['actor_id', 'actor_label', 'action', 'subject_type', 'subject_id', 'ip_address', 'user_agent', 'properties'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['properties' => 'array'];
    }

    /**
     * Jangan pernah memasukkan password, token, signature URL, atau isi file ke $properties.
     *
     * @param  array<string, mixed>  $properties
     */
    public static function record(string $action, ?Model $subject = null, array $properties = [], ?User $actor = null, ?string $actorLabel = null): self
    {
        return self::create([
            'actor_id' => $actor?->id,
            'actor_label' => $actorLabel ?? $actor->name ?? AuthorType::System->label(),
            'action' => $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'ip_address' => request()->ip(),
            'user_agent' => Str::limit((string) request()->userAgent(), 250, ''),
            'properties' => $properties ?: null,
        ]);
    }

    /**
     * @param  Builder<self>  $query
     * @param  array<string, string>  $filters  dari LogIndexRequest::filters()
     */
    public function scopeFilter(Builder $query, array $filters): void
    {
        $query
            ->when($filters['aksi'] ?? null, fn (Builder $q, string $action) => $q->where('action', $action))
            ->when($filters['pelaku'] ?? null, fn (Builder $q, string $actor) => $actor === 'sistem' ? $q->whereNull('actor_id') : $q->where('actor_id', (int) $actor))
            ->when($filters['dari'] ?? null, fn (Builder $q, string $date) => $q->where('created_at', '>=', Carbon::parse($date)->startOfDay()))
            ->when($filters['sampai'] ?? null, fn (Builder $q, string $date) => $q->where('created_at', '<=', Carbon::parse($date)->endOfDay()))
            ->when($filters['tiket'] ?? null, fn (Builder $q, string $no) => $q
                ->where('subject_type', (new Ticket)->getMorphClass())
                ->whereIn('subject_id', Ticket::withTrashed()->whereLike('ticket_no', "%{$no}%")->select('id')));
    }

    /** @return BelongsTo<User, $this> */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /**
     * Termasuk subjek yang sudah di-soft-delete (tiket), supaya log lama tetap bisa dibaca.
     *
     * @return MorphTo<Model, $this>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo()->withTrashed();
    }
}
