<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Tickets\AssignTicket;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AssignRequest;
use App\Models\Ticket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class TicketAssignmentController extends Controller
{
    /** "Ambil tiket": assign ke diri sendiri. */
    public function take(Request $request, Ticket $ticket, AssignTicket $assign): RedirectResponse
    {
        $this->authorize('take', $ticket);
        $assign->handle($ticket, $request->user(), $request->user());

        return back()->with('toast', 'Tiket sekarang kamu tangani.');
    }

    /** Admin: assign ke siapa pun atau lepas penanganan. */
    public function assign(AssignRequest $request, Ticket $ticket, AssignTicket $assign): RedirectResponse
    {
        $assignee = $request->assignee();
        $assign->handle($ticket, $assignee, $request->user());

        return back()->with('toast', $assignee ? "Tiket di-assign ke {$assignee->name}." : 'Penanganan tiket dilepas.');
    }
}
