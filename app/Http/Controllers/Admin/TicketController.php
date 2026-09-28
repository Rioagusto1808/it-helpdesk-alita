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
        // Lampiran awal pemohon ditampilkan di modal balasan.
        $tickets = $this->query($request, $filters)
            ->with(['attachments' => fn ($q) => $q->whereNull('comment_id')])
            ->paginate(20)->withQueryString();

        return view('admin.tickets.index', [
            'tickets' => $tickets,
            'respondData' => $tickets->getCollection()->mapWithKeys(fn (Ticket $t): array => [$t->id => $this->respondData($t)]),
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
     * Isi modal balasan (dibaca public/js/ticket-respond.js dari atribut data-ticket).
     *
     * @return array<string, mixed>
     */
    private function respondData(Ticket $ticket): array
    {
        $status = $ticket->status;

        return [
            'no' => $ticket->ticket_no,
            'type' => $ticket->category->name.' · '.$ticket->typeLabel(),
            'requester' => $ticket->requester_name,
            'email' => $ticket->requester_email,
            'created' => $ticket->created_at?->translatedFormat('d M Y, H.i'),
            'description' => $ticket->description,
            'status' => $status->value,
            'statusLabel' => $status->label(),
            'badge' => $status->badgeClass(),
            'allowed' => collect(TicketStatus::responses())
                ->filter(fn (TicketStatus $to): bool => $to === $status || $status->canTransitionTo($to))
                ->map(fn (TicketStatus $to): string => $to->value)->values(),
            'final' => $status->isFinal(),
            'action' => route('admin.tickets.respond', $ticket),
            'detail' => route('admin.tickets.show', $ticket),
            'files' => $ticket->attachments->map(fn (TicketAttachment $a): array => ['name' => $a->original_name, 'url' => route('admin.attachments.show', $a)])->values(),
        ];
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
