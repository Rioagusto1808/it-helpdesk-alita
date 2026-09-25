<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LogIndexRequest;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\View\View;

/** Audit log: hanya baca, terbaru di atas. */
final class ActivityLogController extends Controller
{
    public function index(LogIndexRequest $request): View
    {
        $f = $request->filters();

        return view('admin.logs.activity', [
            'logs' => ActivityLog::query()
                ->with('subject')
                ->filter($f)
                ->latest('id')
                ->paginate(50)
                ->withQueryString(),
            'filters' => $f,
            'actions' => ActivityLog::query()->distinct()->orderBy('action')->pluck('action'),
            'actors' => User::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
