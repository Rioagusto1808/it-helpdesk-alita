<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Emails\RetryEmail;
use App\Enums\EmailStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LogIndexRequest;
use App\Models\EmailLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class EmailLogController extends Controller
{
    public function index(LogIndexRequest $request): View
    {
        $f = $request->filters();

        return view('admin.logs.email', [
            'logs' => EmailLog::query()
                ->with(['ticket:id,ticket_no'])
                ->when($f['status'] ?? null, fn (Builder $q, string $status) => $q->where('status', $status))
                ->when($f['tipe'] ?? null, fn (Builder $q, string $type) => $q->where('type', $type))
                ->latest('id')
                ->paginate(50)
                ->withQueryString(),
            'filters' => $f,
            'statuses' => EmailStatus::cases(),
            'types' => EmailLog::query()->distinct()->orderBy('type')->pluck('type'),
        ]);
    }

    public function retry(Request $request, EmailLog $emailLog, RetryEmail $retry): RedirectResponse
    {
        $retry->handle($emailLog, $request->user());

        return back()->with('toast', "Email ke {$emailLog->to_email} masuk antrian kirim ulang.");
    }
}
