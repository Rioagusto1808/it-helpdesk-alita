<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Tickets\ChangePriority;
use App\Actions\Tickets\ChangeTicketStatus;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdatePriorityRequest;
use App\Http\Requests\Admin\UpdateStatusRequest;
use App\Models\Ticket;
use Illuminate\Http\RedirectResponse;

/** Status dan prioritas tiket. */
final class TicketStatusController extends Controller
{
    public function update(UpdateStatusRequest $request, Ticket $ticket, ChangeTicketStatus $changeStatus): RedirectResponse
    {
        $changeStatus->handle(
            ticket: $ticket,
            to: TicketStatus::from($request->validated('status')),
            actor: $request->user(),
            note: $request->validated('note'),
        );

        return back()->with('toast', 'Status tiket diperbarui.');
    }

    public function priority(UpdatePriorityRequest $request, Ticket $ticket, ChangePriority $changePriority): RedirectResponse
    {
        $changePriority->handle($ticket, TicketPriority::from($request->validated('priority')), $request->user());

        return back()->with('toast', 'Prioritas tiket diperbarui.');
    }
}
