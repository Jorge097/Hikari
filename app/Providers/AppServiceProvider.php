<?php

namespace App\Providers;

use App\Services\Invoicing\FacturamaProvider;
use App\Services\Invoicing\InvoiceProvider;
use App\Services\Payments\PaymentGateway;
use App\Services\Payments\StripeGateway;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
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

        // PAC para CFDI
        $this->app->bind(InvoiceProvider::class, fn () => new FacturamaProvider(
            config('services.facturama.user'),
            config('services.facturama.password'),
            (bool) config('services.facturama.sandbox'),
        ));
    }

    public function boot(): void
    {
        // Panel: pesos con formato mexicano ($1,234.50)
        Table::configureUsing(fn (Table $table) => $table->defaultCurrency('MXN')->defaultNumberLocale('es_MX'));
        Schema::configureUsing(fn (Schema $schema) => $schema->defaultCurrency('MXN')->defaultNumberLocale('es_MX'));

        // Máx. 10 intentos de checkout por minuto por IP
        RateLimiter::for('checkout', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));

        // Máx. 5 solicitudes de factura por minuto por IP
        RateLimiter::for('invoice', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));

        // HTTPS en producción, staging y al exponer el sitio con ngrok
        if ($this->app->environment('production', 'staging') || str_contains(request()->url(), 'ngrok')) {
            URL::forceScheme('https');
        }
    }
}
