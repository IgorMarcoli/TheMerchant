<?php

namespace App\Providers;

use App\Services\Payments\AbacatePayGateway;
use App\Services\Payments\PaymentGateway;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Console\ServeCommand;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PaymentGateway::class, AbacatePayGateway::class);
    }

    public function boot(): void
    {
        if ($this->app->environment('production') || str_starts_with((string) config('app.url'), 'https://')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        // No Windows a variável é "SystemRoot", mas o artisan serve só repassa "SYSTEMROOT";
        // sem ela o servidor embutido não abre a porta ("Failed to listen ... reason: ?").
        if (! in_array('SystemRoot', ServeCommand::$passthroughVariables, true)) {
            ServeCommand::$passthroughVariables[] = 'SystemRoot';
        }

        // A view padrão do Laravel usa fundo branco; todas as listagens seguem o tema escuro do catálogo.
        Paginator::defaultView('vendor.pagination.catalog');

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
