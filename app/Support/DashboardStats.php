<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\TicketStatus;
use App\Models\Category;
use App\Models\Ticket;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/** Angka dashboard admin. Agregasi di database, di-cache 60 detik. */
final class DashboardStats
{
    private const CACHE_SECONDS = 60;

    private const CHART_DAYS = 14;

    private const WINDOW_DAYS = 30;

    /** @return array{baru: int, diproses: int, menunggu: int, selesai_hari_ini: int, lewat_sla: int, avg_response_minutes: ?int, avg_resolve_minutes: ?int} */
    public static function summary(): array
    {
        return Cache::remember('dashboard.summary', self::CACHE_SECONDS, function (): array {
            $counts = Ticket::query()->whereIn('status', TicketStatus::active())
                ->groupBy('status')->selectRaw('status, count(*) as total')
                ->toBase()->pluck('total', 'status');
            $since = now()->subDays(self::WINDOW_DAYS);

            return [
                'baru' => (int) ($counts[TicketStatus::Baru->value] ?? 0),
                'diproses' => (int) ($counts[TicketStatus::Diproses->value] ?? 0),
                'menunggu' => (int) ($counts[TicketStatus::Menunggu->value] ?? 0),
                'selesai_hari_ini' => Ticket::query()->where('resolved_at', '>=', today())->count(),
                'lewat_sla' => Ticket::query()->overdue()->count(),
                // PostgreSQL: selisih timestamp → detik lewat extract(epoch).
                'avg_response_minutes' => self::avgMinutes('first_response_at', 'created_at', $since),
                'avg_resolve_minutes' => self::avgMinutes('resolved_at', 'resolved_at', $since),
            ];
        });
    }

    /**
     * Tiket masuk per hari (14 hari terakhir), dipisah ITApps dan ITInfra.
     *
     * @return list<array{date: Carbon, apps: int, infra: int}>
     */
    public static function daily(): array
    {
        return Cache::remember('dashboard.daily', self::CACHE_SECONDS, function (): array {
            $start = today()->subDays(self::CHART_DAYS - 1);
            $rows = Ticket::query()
                ->join('categories', 'categories.id', '=', 'tickets.category_id')
                ->where('tickets.created_at', '>=', $start)
                ->groupByRaw('date(tickets.created_at), categories.code')
                ->selectRaw('date(tickets.created_at) as day, categories.code as code, count(*) as total')
                ->toBase()->get();

            $days = [];
            for ($i = 0; $i < self::CHART_DAYS; $i++) {
                $date = $start->copy()->addDays($i);
                $forDay = $rows->where('day', $date->toDateString());
                $days[] = [
                    'date' => $date,
                    'apps' => (int) $forDay->firstWhere('code', Category::ITAPPS)?->total,
                    'infra' => (int) $forDay->firstWhere('code', Category::ITINFRA)?->total,
                ];
            }

            return $days;
        });
    }

    /**
     * Lima layanan/modul dengan tiket terbanyak dalam 30 hari.
     *
     * @return list<array{name: string, category: string, total: int}>
     */
    public static function topTypes(): array
    {
        return Cache::remember('dashboard.top_types', self::CACHE_SECONDS, fn (): array => Ticket::query()
            ->join('categories', 'categories.id', '=', 'tickets.category_id')
            ->leftJoin('services', 'services.id', '=', 'tickets.service_id')
            ->leftJoin('modules', 'modules.id', '=', 'tickets.module_id')
            ->where('tickets.created_at', '>=', now()->subDays(self::WINDOW_DAYS))
            ->groupByRaw("categories.name, coalesce(services.name, modules.name, '-')")
            ->selectRaw("categories.name as category, coalesce(services.name, modules.name, '-') as name, count(*) as total")
            ->orderByDesc('total')
            ->limit(5)
            ->toBase()->get()
            ->map(fn (object $row): array => ['name' => (string) $row->name, 'category' => (string) $row->category, 'total' => (int) $row->total])
            ->all());
    }

    /** Rata-rata ($end - created_at) dalam menit untuk tiket dengan $end dalam jendela waktu. */
    private static function avgMinutes(string $end, string $windowColumn, Carbon $since): ?int
    {
        $seconds = Ticket::query()
            ->whereNotNull($end)
            ->where($windowColumn, '>=', $since)
            ->selectRaw("avg(extract(epoch from ({$end} - created_at))) as seconds")
            ->toBase()->value('seconds');

        return $seconds === null ? null : (int) round((float) $seconds / 60);
    }
}
