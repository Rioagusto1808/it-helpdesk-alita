<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\ActivityLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        if (! Auth::attempt($request->credentials(), $request->boolean('remember'))) {
            ActivityLog::record('auth.login_failed', null, ['email' => $request->validated('email')]);

            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }

        $request->session()->regenerate();
        $user = $request->user();
        $user?->forceFill(['last_login_at' => now()])->save();
        ActivityLog::record('auth.login', $user, [], $user);

        return redirect()->intended(route('admin.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        ActivityLog::record('auth.logout', $request->user(), [], $request->user());

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('admin.login');
    }
}
