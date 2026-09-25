<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Tickets\AddComment;
use App\Enums\AuthorType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CommentRequest;
use App\Models\Ticket;
use Illuminate\Http\RedirectResponse;

final class TicketCommentController extends Controller
{
    public function store(CommentRequest $request, Ticket $ticket, AddComment $addComment): RedirectResponse
    {
        $addComment->handle(
            ticket: $ticket,
            body: $request->validated('body'),
            author: AuthorType::Agent,
            actor: $request->user(),
            internal: $request->isInternal(),
            attachment: $request->attachment(),
        );

        return back()->with('toast', $request->isInternal() ? 'Catatan internal disimpan.' : 'Komentar terkirim ke pemohon.');
    }
}
