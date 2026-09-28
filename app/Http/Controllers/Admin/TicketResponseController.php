<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Tickets\RespondToTicket;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RespondRequest;
use App\Models\Ticket;
use Illuminate\Http\RedirectResponse;

final class TicketResponseController extends Controller
{
    public function store(RespondRequest $request, Ticket $ticket, RespondToTicket $respond): RedirectResponse
    {
        $respond->handle($ticket, $request->status(), $request->validated('message'), $request->user(), $request->attachment());

        return back()->with('toast', "Balasan terkirim ke {$ticket->requester_email}.");
    }
}
