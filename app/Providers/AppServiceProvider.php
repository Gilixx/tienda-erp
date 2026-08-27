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
        // En produccion (Vercel) forzar https en todas las URLs generadas
        // (asset(), url(), @vite), ya que detras del proxy el esquema interno
        // es http y el CSP 'self' rechaza los assets http:// de una pagina https.
        if (! $this->app->environment('local')) {
            URL::forceScheme('https');
        }
    }
}
