<?php

namespace App\Providers;

use App\Models\PromptRun;
use App\Models\Question;
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
        // Initialize Stripe with API key EARLY (in register, not boot)
        // This ensures Cashier can use it when it initializes
        $stripeSecret = env('STRIPE_SECRET');
        $stripePublic = env('STRIPE_PUBLIC');

        if ($stripeSecret) {
            Stripe::setApiKey($stripeSecret);
        }

        $this->app->singleton(N8nWorkflowClient::class, function () {
            return new N8nWorkflowClient;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        Route::model('promptRun', PromptRun::class);
        Route::model('parentPromptRun', PromptRun::class);
        Route::model('question', Question::class);

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
