<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/** Filter, cari, dan urut daftar tiket admin. Dipakai halaman daftar dan export CSV. */
final class TicketFilters
{
    public const SORTS = [
        'antrian' => 'Posisi antrian',
        'terbaru' => 'Terbaru',
        'aktivitas' => 'Aktivitas terakhir',
        'prioritas' => 'Prioritas',
    ];

    /**
     * @param  Builder<Ticket>  $query
     * @param  array<string, mixed>  $filters  sudah divalidasi TicketIndexRequest::filters()
     * @return Builder<Ticket>
     */
    public static function apply(Builder $query, array $filters, User $viewer): Builder
    {
        $status = $filters['status'] ?? 'aktif';

        $query
            ->when($status === 'aktif', fn (Builder $q) => $q->whereIn('status', TicketStatus::active()))
            ->when(! in_array($status, ['aktif', 'semua'], true), fn (Builder $q) => $q->where('status', $status))
            ->when($filters['kategori'] ?? null, fn (Builder $q, string $code) => $q->whereRelation('category', 'code', $code))
            ->when($filters['jenis'] ?? null, function (Builder $q, string $type): void {
                [$kind, $id] = explode(':', $type);
                $q->where($kind === 'm' ? 'module_id' : 'service_id', (int) $id);
            })
            ->when($filters['prioritas'] ?? null, fn (Builder $q, string $priority) => $q->where('priority', $priority))
            ->when($filters['petugas'] ?? null, fn (Builder $q, string $assignee) => match ($assignee) {
                'none' => $q->whereNull('assigned_to'),
                'me' => $q->where('assigned_to', $viewer->id),
                default => $q->where('assigned_to', (int) $assignee),
            })
            ->when($filters['dari'] ?? null, fn (Builder $q, string $date) => $q->where('created_at', '>=', Carbon::parse($date)->startOfDay()))
            ->when($filters['sampai'] ?? null, fn (Builder $q, string $date) => $q->where('created_at', '<=', Carbon::parse($date)->endOfDay()))
            ->when($filters['sla'] ?? false, fn (Builder $q) => $q->overdue())
            // whereLike = ILIKE di PostgreSQL (tidak peka huruf besar-kecil).
            ->when($filters['cari'] ?? null, fn (Builder $q, string $term) => $q->where(fn (Builder $q) => $q
                ->whereLike('ticket_no', "%{$term}%")
                ->orWhereLike('requester_name', "%{$term}%")
                ->orWhereLike('requester_email', "%{$term}%")
                ->orWhereLike('description', "%{$term}%")));

        return match ($filters['urut'] ?? 'antrian') {
            'terbaru' => $query->orderByDesc('id'),
            'aktivitas' => $query->orderByDesc('last_activity_at')->orderByDesc('id'),
            'prioritas' => $query->orderByPriority()->orderBy('id'),
            default => $query->queueOrder(),
        };
    }
}
