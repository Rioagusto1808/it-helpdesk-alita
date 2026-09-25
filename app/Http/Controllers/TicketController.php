<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Tickets\CreateTicket;
use App\Enums\TicketStatus;
use App\Http\Requests\StoreTicketRequest;
use App\Models\Category;
use App\Models\Module;
use App\Models\Service;
use App\Models\Ticket;
use App\Support\QueuePosition;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class TicketController extends Controller
{
    public function create(): View
    {
        return view('tickets.create', [
            'categories' => Category::query()->orderBy('id')->get(),
            'services' => Service::query()->active()->get(),
            'modules' => Module::query()->active()->get(),
            'activeCount' => Ticket::query()->whereIn('status', TicketStatus::active())->count(),
        ]);
    }

    public function store(StoreTicketRequest $request, CreateTicket $createTicket): RedirectResponse
    {
        $ticket = $createTicket->handle($request->ticketData(), $request->attachment(), $request->ip());

        return to_route('tickets.submitted')->with('submitted_ticket_id', $ticket->id);
    }

    /** Hanya bisa dibuka lewat session setelah submit, jadi nomor tiket tidak bisa ditebak dari sini. */
    public function submitted(Request $request): View|RedirectResponse
    {
        $ticket = Ticket::query()
            ->with(['category', 'service', 'module'])
            ->find($request->session()->get('submitted_ticket_id'));

        if ($ticket === null) {
            return to_route('tickets.create');
        }

        return view('tickets.submitted', ['ticket' => $ticket, 'position' => QueuePosition::for($ticket)]);
    }
}
