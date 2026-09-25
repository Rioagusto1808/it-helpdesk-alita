<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTicketRequest;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Module;
use App\Models\Service;
use App\Models\Ticket;
use App\Support\TicketNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class TicketController extends Controller
{
    public function create()
    {
        return view('tickets.create', [
            'categories' => Category::orderBy('id')->get(),
            'services' => Service::active()->whereHas('category', fn ($q) => $q->where('code', 'ITINFRA'))->get(),
            'modules' => Module::active()->get(),
        ]);
    }

    public function store(StoreTicketRequest $request, TicketNotifier $notifier)
    {
        $storedPath = null;

        // File disimpan di disk "local" (storage/app/private), tidak bisa diakses publik.
        if ($file = $request->file('attachment')) {
            $storedPath = $file->store('attachments/'.now()->format('Y/m'), 'local');
        }

        try {
            $ticket = DB::transaction(function () use ($request, $file, $storedPath) {
                $ticket = Ticket::create($request->ticketData() + ['ip_address' => $request->ip()]);

                if ($storedPath) {
                    $ticket->attachments()->create([
                        'original_name' => Str::limit($file->getClientOriginalName(), 250, ''),
                        'stored_path' => $storedPath,
                        'mime' => $file->getMimeType(),
                        'size' => $file->getSize(),
                    ]);
                }

                ActivityLog::record('ticket.created', $ticket, ['ticket_no' => $ticket->ticket_no]);

                return $ticket;
            });
        } catch (Throwable $e) {
            if ($storedPath) {
                Storage::disk('local')->delete($storedPath); // jangan tinggalkan file yatim
            }
            throw $e;
        }

        $notifier->ticketCreated($ticket);

        return redirect()
            ->route('tickets.submitted')
            ->with('submitted_ticket_id', $ticket->id);
    }

    public function submitted(Request $request)
    {
        // Hanya bisa dibuka sekali setelah submit (via session), jadi nomor tiket tidak bisa ditebak-tebak.
        $id = $request->session()->get('submitted_ticket_id');

        if (! $id) {
            return redirect()->route('tickets.create');
        }

        $ticket = Ticket::with(['category', 'service', 'module'])->findOrFail($id);

        return view('tickets.submitted', compact('ticket'));
    }
}
