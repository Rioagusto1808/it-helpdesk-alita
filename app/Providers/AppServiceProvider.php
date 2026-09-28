<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
use App\Support\AdminMenu;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $strict = ! $this->app->isProduction();

        Model::preventLazyLoading($strict);
        Model::preventSilentlyDiscardingAttributes($strict);

        View::composer('layouts.admin', function (ViewContract $view): void {
            $user = auth()->user();
            $view->with('menu', $user instanceof User ? AdminMenu::for($user) : []);
        });

        RateLimiter::for('login', fn (Request $request): Limit => Limit::perMinute(5)
            ->by(Str::lower((string) $request->input('email')).'|'.$request->ip()));

        // Per tiket, bukan per IP: satu pemohon bisa berganti jaringan.
        RateLimiter::for('tracking-reply', fn (Request $request): Limit => Limit::perHour(10)
            ->by('reply|'.$request->route()?->originalParameter('ticket')));
    }
}
