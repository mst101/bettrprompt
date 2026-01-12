<?php

namespace App\Providers;

use App\Models\PromptRun;
use App\Services\N8nWorkflowClient;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Stripe\Stripe;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(N8nWorkflowClient::class, function () {
            return new N8nWorkflowClient;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Initialize Stripe with API key from config
        if (config('stripe.secret')) {
            Stripe::setApiKey(config('stripe.secret'));
        }

        Vite::prefetch(concurrency: 3);

        Route::model('promptRun', PromptRun::class);
        Route::model('parentPromptRun', PromptRun::class);

        // Force HTTPS URLs when behind reverse proxy (Caddy)
        if ($this->app->environment('local')) {
            URL::forceScheme('https');
        }

        // Customise password reset URL to use modal format
        ResetPassword::createUrlUsing(function ($user, string $token) {
            return url('/').'?'.http_build_query([
                'modal' => 'reset-password',
                'token' => $token,
                'email' => $user->getEmailForPasswordReset(),
            ]);
        });
    }
}
