<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\TicketAttachment;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Unduh lampiran dari panel admin. Pemohon memakai TrackingController@attachment (signed URL). */
final class AttachmentController extends Controller
{
    public function show(TicketAttachment $attachment): StreamedResponse
    {
        // Relasi ticket mengabaikan tiket yang sudah dihapus (soft delete) → 404.
        $ticket = $attachment->ticket;
        abort_if($ticket === null, 404);
        $this->authorize('view', $ticket);

        return Storage::disk('local')->download($attachment->stored_path, $attachment->original_name);
    }
}
