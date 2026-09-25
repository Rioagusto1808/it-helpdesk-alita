<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Tickets\DeleteTicket;
use App\Actions\Tickets\SendTrackingLink;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TicketIndexRequest;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Module;
use App\Models\Service;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\User;
use App\Support\QueuePosition;
use App\Support\TicketCsv;
use App\Support\TicketFilters;
use App\Support\TicketTimeline;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class TicketController extends Controller
{
    public function index(TicketIndexRequest $request): View
    {
        $filters = $request->filters();

        return view('admin.tickets.index', [
            'tickets' => $this->query($request, $filters)->paginate(20)->withQueryString(),
            'filters' => $filters,
            'options' => $this->filterOptions(),
        ]);
    }

    public function export(TicketIndexRequest $request): StreamedResponse
    {
        $this->authorize('export', Ticket::class);
        ActivityLog::record('ticket.exported', null, ['filters' => $request->filters()], $request->user());

        return TicketCsv::download($this->query($request, $request->filters()));
    }

    public function show(Request $request, Ticket $ticket): View
    {
        $this->authorize('view', $ticket);
        $ticket->load(['category', 'service', 'module', 'assignee:id,name', 'attachments']);

        return view('admin.tickets.show', [
            'ticket' => $ticket,
            'position' => QueuePosition::for($ticket),
            'timeline' => TicketTimeline::forAgent($ticket),
            'fileUrls' => $ticket->attachments->mapWithKeys(fn (TicketAttachment $a): array => [$a->id => route('admin.attachments.show', $a)]),
            'agents' => $request->user()?->can('assign', $ticket) ? User::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']) : collect(),
        ]);
    }

    public function sendLink(Request $request, Ticket $ticket, SendTrackingLink $sendLink): RedirectResponse
    {
        $this->authorize('view', $ticket);
        $sendLink->handle($ticket, $request->user());

        return back()->with('toast', "Link tracking dikirim ke {$ticket->requester_email}.");
    }

    public function destroy(Request $request, Ticket $ticket, DeleteTicket $deleteTicket): RedirectResponse
    {
        $this->authorize('delete', $ticket);
        $deleteTicket->handle($ticket, $request->user());

        return to_route('admin.tickets.index')->with('toast', "Tiket {$ticket->ticket_no} dihapus.");
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<Ticket>
     */
    private function query(Request $request, array $filters): Builder
    {
        $query = Ticket::query()->with(['category:id,code,name', 'service:id,name,is_other', 'module:id,name,is_other', 'assignee:id,name']);

        return TicketFilters::apply($query, $filters, $request->user());
    }

    /** @return array<string, mixed> */
    private function filterOptions(): array
    {
        return [
            'statuses' => TicketStatus::cases(),
            'priorities' => TicketPriority::cases(),
            'categories' => Category::query()->orderBy('id')->get(['code', 'name']),
            'services' => Service::query()->orderBy('sort_order')->get(['id', 'name']),
            'modules' => Module::query()->orderBy('sort_order')->get(['id', 'name']),
            'agents' => User::query()->orderBy('name')->get(['id', 'name']),
            'sorts' => TicketFilters::SORTS,
        ];
    }
}
