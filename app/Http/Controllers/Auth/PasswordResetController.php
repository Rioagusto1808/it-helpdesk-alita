<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/** Alur reset bawaan Laravel (password broker, token 60 menit). */
final class PasswordResetController extends Controller
{
    public function request(): View
    {
        return view('auth.forgot-password');
    }

    /** Pesan selalu sama agar halaman ini tidak bisa dipakai mengecek email mana yang terdaftar. */
    public function email(ForgotPasswordRequest $request): RedirectResponse
    {
        Password::sendResetLink($request->only('email'));

        return back()->with('status', 'Jika email terdaftar, link reset password sudah kami kirim. Link berlaku 60 menit.');
    }

    public function edit(Request $request, string $token): View
    {
        return view('auth.reset-password', ['token' => $token, 'email' => $request->string('email')->toString()]);
    }

    public function update(ResetPasswordRequest $request, string $token): RedirectResponse
    {
        $status = Password::reset(
            [...$request->only('email', 'password', 'password_confirmation'), 'token' => $token],
            function (User $user, string $password): void {
                $user->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();
                ActivityLog::record('auth.password_reset', $user, [], $user);
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => __($status)]);
        }

        return to_route('admin.login')->with('status', __($status));
    }
}
