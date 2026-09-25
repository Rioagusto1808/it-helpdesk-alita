<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;

/** Matriks hak akses PRD. User nonaktif sudah ditolak middleware "active" sebelum sampai sini. */
final class TicketPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Ticket $ticket): bool
    {
        return true;
    }

    public function changeStatus(User $user, Ticket $ticket): bool
    {
        return true;
    }

    public function changePriority(User $user, Ticket $ticket): bool
    {
        return ! $ticket->status->isFinal();
    }

    /** Agent mengambil tiket yang belum ada penanganannya; memindahkan dari orang lain hanya lewat assign (admin). */
    public function take(User $user, Ticket $ticket): bool
    {
        return $ticket->assigned_to === null && ! $ticket->status->isFinal();
    }

    public function assign(User $user, Ticket $ticket): bool
    {
        return $user->isAdmin() && ! $ticket->status->isFinal();
    }

    public function comment(User $user, Ticket $ticket): bool
    {
        return ! $ticket->status->isFinal();
    }

    public function delete(User $user, Ticket $ticket): bool
    {
        return $user->isAdmin();
    }

    public function export(User $user): bool
    {
        return $user->isAdmin();
    }
}
