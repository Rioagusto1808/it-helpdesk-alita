<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Ticket;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Export CSV daftar tiket, di-stream per 500 baris supaya hemat memori. */
final class TicketCsv
{
    private const HEADERS = [
        'Nomor tiket', 'Status', 'Prioritas', 'Kategori', 'Layanan/modul', 'Pemohon', 'Email',
        'Petugas', 'Masuk', 'Respons pertama', 'Selesai', 'Ditutup', 'Aktivitas terakhir', 'Lewat SLA', 'Deskripsi',
    ];

    /** @param Builder<Ticket> $query */
    public static function download(Builder $query): StreamedResponse
    {
        return response()->streamDownload(function () use ($query): void {
            $out = fopen('php://output', 'w');
            if ($out === false) {
                return;
            }

            fwrite($out, "\xEF\xBB\xBF"); // BOM agar Excel membaca UTF-8
            fputcsv($out, self::HEADERS);
            $query->lazy(500)->each(fn (Ticket $ticket) => fputcsv($out, array_map(self::safe(...), self::row($ticket))));
            fclose($out);
        }, 'tiket-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** @return list<string> */
    private static function row(Ticket $t): array
    {
        $format = fn (?\DateTimeInterface $date): string => $date?->format('Y-m-d H:i') ?? '';

        return [
            (string) $t->ticket_no, $t->status->label(), $t->priority->label(), $t->category->name, $t->typeLabel(),
            $t->requester_name, $t->requester_email, $t->assignee->name ?? '',
            $format($t->created_at), $format($t->first_response_at), $format($t->resolved_at), $format($t->closed_at),
            $format($t->last_activity_at), $t->isOverdue() ? 'Ya' : 'Tidak', $t->description,
        ];
    }

    /** Cegah CSV injection: isian pemohon yang diawali = + - @ tidak boleh dibaca Excel sebagai rumus. */
    private static function safe(string $value): string
    {
        return preg_match('/^[=+\-@\t\r]/', $value) ? "'".$value : $value;
    }
}
