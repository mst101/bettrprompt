<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
            \App\Http\Middleware\TrackVisitor::class,
        ]);

        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
        ]);

        // Trust all proxies for local development (Caddy reverse proxy)
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Handle CSRF token expiration (419 errors)
        $exceptions->render(function (\Illuminate\Session\TokenMismatchException $e, \Illuminate\Http\Request $request) {
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
