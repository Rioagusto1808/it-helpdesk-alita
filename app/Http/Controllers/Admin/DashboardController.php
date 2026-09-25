<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Support\DashboardStats;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class DashboardController extends Controller
{
    private const TABLE_LIMIT = 10;

    public function index(Request $request): View
    {
        $relations = ['category:id,code,name', 'service:id,name,is_other', 'module:id,name,is_other'];

        return view('admin.dashboard', [
            'summary' => DashboardStats::summary(),
            'daily' => DashboardStats::daily(),
            'topTypes' => DashboardStats::topTypes(),
            // Urutan antrian: baris ke-n = posisi ke-n.
            'queue' => Ticket::query()->with([...$relations, 'assignee:id,name'])->inQueue()->queueOrder()->limit(self::TABLE_LIMIT)->get(),
            'mine' => Ticket::query()->with($relations)->where('assigned_to', $request->user()?->id)
                ->whereIn('status', TicketStatus::active())->orderByDesc('last_activity_at')->limit(self::TABLE_LIMIT)->get(),
        ]);
    }

    public function data(): JsonResponse
    {
        return response()->json([
            'data' => DashboardStats::summary(),
            'meta' => ['updated_at' => now()->toIso8601String()],
        ]);
    }
}
