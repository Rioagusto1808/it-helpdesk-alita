<?php

namespace App\Models;

use App\Enums\TicketStatus;
use App\Observers\TicketObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy(TicketObserver::class)]
class Ticket extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'category_id', 'service_id', 'service_other', 'module_id', 'module_other',
        'requester_name', 'requester_email', 'description',
        'status', 'priority', 'assigned_to', 'ip_address',
        'first_response_at', 'resolved_at', 'closed_at',
    ];

    protected $casts = [
        'status' => TicketStatus::class,
        'first_response_at' => 'datetime',
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => 'baru',
        'priority' => 'sedang',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TicketAttachment::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TicketComment::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(TicketStatusHistory::class)->orderBy('id');
    }

    /** Label jenis kendala: nama layanan/modul, atau isian "Others". */
    public function typeLabel(): string
    {
        if ($this->service) {
            return $this->service->is_other ? 'Others: '.$this->service_other : $this->service->name;
        }

        if ($this->module) {
            return $this->module->is_other ? 'Others: '.$this->module_other : $this->module->name;
        }

        return '-';
    }

    /** Label field sesuai kategori: "Layanan" untuk ITInfra, "Modul" untuk ITPass. */
    public function typeFieldLabel(): string
    {
        return $this->category?->code === 'ITPASS' ? 'Modul' : 'Layanan';
    }

    /** Posisi antrian FIFO. Null kalau tiket sudah tidak di antrian. */
    public function queuePosition(): ?int
    {
        if (! in_array($this->status?->value, TicketStatus::queued(), true)) {
            return null;
        }

        return static::whereIn('status', TicketStatus::queued())
            ->where('id', '<', $this->id)
            ->count() + 1;
    }

    /** Warna aksen per kategori, dipakai di email. */
    public function accentColor(): string
    {
        return $this->category?->code === 'ITPASS' ? '#0F7A64' : '#1F5FBF';
    }
}
