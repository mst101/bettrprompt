<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Apply SwitchDataCollectionDatabase middleware FIRST (for data collection tests)
        // This must run before UseE2eDatabase so it can take priority
        $middleware->append(\App\Http\Middleware\SwitchDataCollectionDatabase::class);

        // Apply UseE2eDatabase middleware globally to detect Playwright test requests
        // This runs after SwitchDataCollectionDatabase and defaults to bettrprompt_e2e for regular E2E tests
        $middleware->append(\App\Http\Middleware\UseE2eDatabase::class);

        $middleware->web(append: [
            // SetCountry must run before HandleInertiaRequests so locale is set when Inertia props are generated
            \App\Http\Middleware\SetCountry::class,
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
            \App\Http\Middleware\TrackVisitor::class,
            \App\Http\Middleware\ShareSubscriptionStatus::class,
        ]);

        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
            'mailgun.signature' => \App\Http\Middleware\VerifyMailgunSignature::class,
            'prompt.limit' => \App\Http\Middleware\EnforcePromptLimit::class,
            'prompt.track' => \App\Http\Middleware\TrackPromptUsage::class,
            'privacy.unlock' => \App\Http\Middleware\RequirePrivacyUnlock::class,
            'locale' => \App\Http\Middleware\SetLocale::class,
            'country' => \App\Http\Middleware\SetCountry::class,
        ]);

        // Trust all proxies for local development (Caddy reverse proxy)
        $middleware->trustProxies(at: '*');

        // Exempt test endpoints from CSRF protection
        // These routes are protected by X-Test-Auth header instead
        $middleware->validateCsrfTokens(except: [
            'test/login',
            'test/oauth-login',
            'test/broadcast/*',
            'test/create-prompt-run',
            'test/set-personality',
            'test/echo-info',
            'webhook/api/n8n/webhook/*',
            '*/debug/workflow/*',  // Debug workflow endpoints (locale-prefixed)
            '*/workflow/docs/api/*',  // Reference documents API endpoints (locale-prefixed)
            'webhooks/mailgun/*',  // Mailgun webhooks (protected by signature verification)
            'api/stripe/webhook',  // Stripe webhooks (protected by Stripe signature verification)
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->reportable(function (Throwable $e): void {
            // Sentry error reporting disabled - package not installed
            // if (!app()->bound('sentry')) {
            //     return;
            // }
            //
            // \Sentry\configureScope(function ($scope): void {
            //     $scope->setContext('circuit_breaker', [
            //         'failures' => Cache::get('n8n_circuit_breaker_failures', 0),
            //         'circuit_open' => Cache::has('n8n_circuit_breaker_open_until'),
            //         'open_until' => Cache::get('n8n_circuit_breaker_open_until'),
            //     ]);
            //
            //     if (Auth::check()) {
            //         $user = Auth::user();
            //
            //         $scope->setUser([
            //             'id' => $user?->id,
            //             'email' => $user?->email,
            //             'username' => $user?->name,
            //         ]);
            //     }
            //
            //     if (app()->runningInConsole()) {
            //         $scope->setContext('queue', [
            //             'connection' => config('queue.default'),
            //             'queue' => config('queue.connections.'.config('queue.default').'.queue', 'default'),
            //         ]);
            //
            //         $scope->setTag('context', 'queue');
            //     } else {
            //         $scope->setTag('context', 'http');
            //     }
            // });
        });

        // Handle CSRF token expiration (419 errors)
        $exceptions->render(function (
            \Illuminate\Session\TokenMismatchException $e,
            \Illuminate\Http\Request $request
        ) {
            // If it's a logout request and session expired, redirect to home
            if ($request->routeIs('logout')) {
                return redirect('/')->with('status', 'Your session has expired. You have been logged out.');
            }

            // For Inertia requests, return 419 with metadata (client will handle reload)
            if ($request->header('X-Inertia')) {
                return response()->json([
                    'message' => 'Your session has expired. The page will reload automatically.',
                    'errors' => [],
                ], 419);
            }

            // For regular HTTP requests (forms without Inertia)
            // Check if user is authenticated
            if (auth()->check()) {
                // Authenticated user - redirect to login
                return redirect()->route('login')
                    ->with('error', 'Your session has expired. Please log in again.');
            } else {
                // Guest user - redirect back with instructions to refresh
                return back()
                    ->with('error', 'Your session has expired. Please refresh the page and try again.');
            }
        });
    })->create();
