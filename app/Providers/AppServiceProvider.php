<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->environment('production') || str_starts_with((string) config('app.url'), 'https://')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        RateLimiter::for('account-recovery', function (Request $request): array {
            $email = $request->input('email');
            $key = hash('sha256', is_string($email) ? mb_strtolower($email) : '');

            return [
                Limit::perMinute(10)->by('ip:'.$request->ip()),
                Limit::perMinute(3)->by('email:'.$key),
            ];
        });
    }
}
