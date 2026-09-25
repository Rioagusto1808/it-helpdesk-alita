<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Users\CreateUser;
use App\Actions\Users\ToggleUserActive;
use App\Actions\Users\UpdateUser;
use App\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Tambah dan ubah di halaman yang sama (edit = index dengan form terisi). */
final class UserController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', User::class);

        return $this->page(null);
    }

    public function edit(User $user): View
    {
        $this->authorize('update', $user);

        return $this->page($user);
    }

    public function store(UserRequest $request, CreateUser $createUser): RedirectResponse
    {
        $password = $request->validated('password');
        $user = $createUser->handle($request->validated('name'), $request->validated('email'), $password, $request->role(), $request->user());

        return to_route('admin.users.index')->with('toast', $password
            ? "User {$user->name} ditambahkan."
            : "User {$user->name} ditambahkan. Link buat password dikirim ke {$user->email}.");
    }

    public function update(UserRequest $request, User $user, UpdateUser $updateUser): RedirectResponse
    {
        $updateUser->handle($user, $request->validated('name'), $request->validated('email'), $request->role(), $request->validated('password'), $request->user());

        return to_route('admin.users.index')->with('toast', "User {$user->name} diperbarui.");
    }

    public function toggle(Request $request, User $user, ToggleUserActive $toggle): RedirectResponse
    {
        $this->authorize('toggle', $user);
        $toggle->handle($user, $request->user());

        return back()->with('toast', $user->is_active ? "{$user->name} diaktifkan." : "{$user->name} dinonaktifkan.");
    }

    private function page(?User $editing): View
    {
        return view('admin.users.index', [
            'users' => User::query()
                ->withCount(['assignedTickets as active_tickets_count' => fn ($q) => $q->whereIn('status', TicketStatus::active())])
                ->orderByDesc('is_active')->orderBy('name')
                ->get(),
            'editing' => $editing,
        ]);
    }
}
