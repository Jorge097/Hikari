<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL; // <--- 1. IMPORTANTE: Agregar esta línea

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // CORRECCIÓN: Quitamos el ".app" para que detecte tu URL ".dev"
        if (str_contains(request()->url(), 'ngrok')) {
            URL::forceScheme('https');
        }
    }
}