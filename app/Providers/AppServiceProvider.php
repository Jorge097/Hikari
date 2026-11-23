<?php

namespace App\Providers;

use App\Services\Payments\PaymentGateway;
use App\Services\Payments\StripeGateway;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Stripe\StripeClient;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Stripe solo se instancia cuando se usa
        $this->app->bind(PaymentGateway::class, fn () => new StripeGateway(
            new StripeClient((string) config('services.stripe.secret'))
        ));
    }

    public function boot(): void
    {
        // Máx. 10 intentos de checkout por minuto por IP
        RateLimiter::for('checkout', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));

        // HTTPS en producción y al exponer el sitio con ngrok
        if ($this->app->isProduction() || str_contains(request()->url(), 'ngrok')) {
            URL::forceScheme('https');
        }
    }
}
