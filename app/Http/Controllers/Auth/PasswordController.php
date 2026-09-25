<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\UpdatePasswordRequest;
use App\Models\ActivityLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/** Ganti password sendiri dari panel admin. */
final class PasswordController extends Controller
{
    public function edit(): View
    {
        return view('admin.profile.password');
    }

    public function update(UpdatePasswordRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user?->forceFill(['password' => $request->validated('password')])->save();
        ActivityLog::record('auth.password_changed', $user, [], $user);

        return back()->with('toast', 'Password berhasil diganti.');
    }
}
