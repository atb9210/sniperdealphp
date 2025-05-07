<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // L'AuthServiceProvider viene caricato automaticamente da Laravel
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
