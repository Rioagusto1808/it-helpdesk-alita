<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Observers\TicketObserver;
use Database\Factories\TicketFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy(TicketObserver::class)]
class Ticket extends Model
{
    /** @use HasFactory<TicketFactory> */
    use HasFactory, SoftDeletes;

    /** @var list<string> */
    protected $fillable = [
        'category_id', 'service_id', 'service_other', 'module_id', 'module_other',
        'requester_name', 'requester_email', 'description',
        'status', 'priority', 'assigned_to', 'ip_address',
        'first_response_at', 'resolved_at', 'closed_at', 'last_activity_at',
    ];

    /** @var array<string, mixed> */
    protected $attributes = [
        'status' => 'baru',
        'priority' => 'sedang',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => TicketStatus::class,
            'priority' => TicketPriority::class,
            'first_response_at' => 'datetime',
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
            'last_activity_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** @return BelongsTo<Service, $this> */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /** @return BelongsTo<Module, $this> */
    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    /** @return BelongsTo<User, $this> */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /** @return HasMany<TicketAttachment, $this> */
    public function attachments(): HasMany
    {
        return $this->hasMany(TicketAttachment::class);
    }

    /** @return HasMany<TicketComment, $this> */
    public function comments(): HasMany
    {
        return $this->hasMany(TicketComment::class);
    }

    /** @return HasMany<TicketStatusHistory, $this> */
    public function statusHistories(): HasMany
    {
        return $this->hasMany(TicketStatusHistory::class);
    }

    /** @return HasMany<EmailLog, $this> */
    public function emailLogs(): HasMany
    {
        return $this->hasMany(EmailLog::class);
    }

    /** @param Builder<self> $query */
    public function scopeInQueue(Builder $query): void
    {
        $query->whereIn('status', TicketStatus::queued());
    }

    /**
     * Urutan antrian, sama dengan QueuePosition: tiket berposisi dulu, lalu prioritas, lalu ID terkecil.
     *
     * @param  Builder<self>  $query
     */
    public function scopeQueueOrder(Builder $query): void
    {
        $queued = array_map(fn (TicketStatus $s): string => $s->value, TicketStatus::queued());

        $query
            ->orderByRaw('case when status in ('.implode(',', array_fill(0, count($queued), '?')).') then 0 else 1 end', $queued)
            ->orderByPriority()
            ->orderBy('id');
    }

    /**
     * Urgent dulu, rendah terakhir (urutan case di TicketPriority).
     *
     * @param  Builder<self>  $query
     */
    public function scopeOrderByPriority(Builder $query): void
    {
        $priorities = TicketPriority::cases();

        $query->orderByRaw(
            'case priority '.str_repeat('when ? then ? ', count($priorities)).'end',
            array_merge(...array_map(fn (TicketPriority $p, int $rank): array => [$p->value, $rank], $priorities, array_keys($priorities))),
        );
    }

    /**
     * Tiket aktif yang melewati target respons pertama atau target selesai (jam kalender).
     * Harus sama dengan isOverdue().
     *
     * @param  Builder<self>  $query
     */
    public function scopeOverdue(Builder $query): void
    {
        $query->whereIn('status', TicketStatus::active())->where(function (Builder $query): void {
            foreach (TicketPriority::cases() as $priority) {
                $sla = config("helpdesk.sla.{$priority->value}");

                $query->orWhere(fn (Builder $q) => $q->where('priority', $priority)->where(fn (Builder $q) => $q
                    ->where(fn (Builder $q) => $q->whereNull('first_response_at')->where('created_at', '<', now()->subMinutes($sla['response'])))
                    ->orWhere('created_at', '<', now()->subMinutes($sla['resolve']))));
            }
        });
    }

    public function isOverdue(): bool
    {
        if (! in_array($this->status, TicketStatus::active(), true) || $this->created_at === null) {
            return false;
        }

        $sla = config("helpdesk.sla.{$this->priority->value}");

        return ($this->first_response_at === null && $this->created_at->copy()->addMinutes($sla['response'])->isPast())
            || $this->created_at->copy()->addMinutes($sla['resolve'])->isPast();
    }

    /** "Modul" untuk ITApps, "Layanan" untuk ITInfra. Butuh relasi category sudah dimuat. */
    public function typeFieldLabel(): string
    {
        return $this->category->code === Category::ITAPPS ? 'Modul' : 'Layanan';
    }

    /** Nama layanan/modul, atau isian teks jika memilih "Others". Butuh relasi service & module sudah dimuat. */
    public function typeLabel(): string
    {
        $item = $this->service ?? $this->module;

        if ($item === null) {
            return '-';
        }

        return $item->is_other ? (string) ($this->service_other ?? $this->module_other) : $item->name;
    }
}
