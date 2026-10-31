<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

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
        // En entorno de escritorio local NativePHP nunca forzar HTTPS (el servidor interno corre en HTTP)
        if (config('nativephp-internal.running') || env('NATIVEPHP_RUNNING')) {
            URL::forceScheme('http');
            return;
        }

        if (config('app.env') === 'production' || request()->server('HTTP_X_FORWARDED_PROTO') === 'https') {
            URL::forceScheme('https');
        }
    }
}

