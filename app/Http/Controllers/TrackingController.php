<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Tickets\AddComment;
use App\Actions\Tickets\ChangeTicketStatus;
use App\Actions\Tickets\SendTrackingLink;
use App\Enums\AuthorType;
use App\Enums\TicketStatus;
use App\Http\Requests\RequesterReplyRequest;
use App\Http\Requests\TrackingLookupRequest;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Support\QueuePosition;
use App\Support\TicketTimeline;
use App\Support\TrackingUrl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Halaman pemohon. Semua route kecuali lookup memakai middleware "signed" sebagai bukti kepemilikan. */
final class TrackingController extends Controller
{
    public const LOOKUP_MESSAGE = 'Jika data cocok, link tracking sudah dikirim ke email tersebut.';

    public function lookup(): View
    {
        return view('tracking.lookup');
    }

    /** Pesan selalu sama, cocok atau tidak, agar tidak bisa dipakai mengecek data orang lain. */
    public function sendLink(TrackingLookupRequest $request, SendTrackingLink $sendLink): RedirectResponse
    {
        $ticket = $request->ticket();

        if ($ticket !== null) {
            $sendLink->handle($ticket);
        }

        return to_route('tracking.lookup')->with('status', self::LOOKUP_MESSAGE);
    }

    public function show(Request $request, Ticket $ticket): View
    {
        $ticket->load(['category', 'service', 'module', 'assignee:id,name', 'attachments.comment']);
        $expires = $this->expires($request);

        return view('tracking.show', [
            'ticket' => $ticket,
            'position' => QueuePosition::for($ticket),
            'timeline' => TicketTimeline::forRequester($ticket),
            'requesterFiles' => $ticket->attachments->whereNull('comment_id'),
            'fileUrls' => $ticket->attachments
                ->filter(fn (TicketAttachment $a): bool => $a->isPublic())
                ->mapWithKeys(fn (TicketAttachment $a): array => [$a->id => TrackingUrl::make($ticket, 'tracking.attachment', ['attachment' => $a->id], $expires)]),
            'replyUrl' => TrackingUrl::make($ticket, 'tracking.reply', expires: $expires),
            'cancelUrl' => TrackingUrl::make($ticket, 'tracking.cancel', expires: $expires),
        ]);
    }

    public function reply(RequesterReplyRequest $request, Ticket $ticket, AddComment $addComment): RedirectResponse
    {
        $addComment->handle($ticket, $request->validated('body'), AuthorType::Requester, attachment: $request->attachment());

        return redirect()->to(TrackingUrl::make($ticket, expires: $this->expires($request)))->with('toast', 'Balasan terkirim.');
    }

    public function cancel(Request $request, Ticket $ticket, ChangeTicketStatus $changeStatus): RedirectResponse
    {
        // Pemohon hanya boleh membatalkan tiket yang belum diproses (tim IT boleh menolak di status lain).
        if ($ticket->status !== TicketStatus::Baru) {
            throw ValidationException::withMessages(['status' => 'Tiket yang sudah diproses tidak bisa dibatalkan.']);
        }

        $changeStatus->handle($ticket, TicketStatus::Dibatalkan, null, 'Dibatalkan oleh pemohon', AuthorType::Requester->label());

        return redirect()->to(TrackingUrl::make($ticket, expires: $this->expires($request)))->with('toast', 'Tiket dibatalkan.');
    }

    public function attachment(Ticket $ticket, TicketAttachment $attachment): StreamedResponse
    {
        abort_unless($attachment->load('comment')->isPublic(), 404);

        return Storage::disk('local')->download($attachment->stored_path, $attachment->original_name);
    }

    /** Form dan link di halaman ikut masa berlaku link yang sedang dibuka. */
    private function expires(Request $request): Carbon
    {
        return Carbon::createFromTimestamp($request->integer('expires'));
    }
}
