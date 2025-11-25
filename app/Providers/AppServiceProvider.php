<?php

namespace App\Providers;

use App\Services\PromptFrameworkService;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(PromptFrameworkService::class, function ($app) {
            return new PromptFrameworkService;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        // Force HTTPS URLs when behind reverse proxy (Caddy)
        if ($this->app->environment('local')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }
    }
}
