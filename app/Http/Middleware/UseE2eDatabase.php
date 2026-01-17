<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Log;
use Symfony\Component\HttpFoundation\Response;

class UseE2eDatabase
{
    /**
     * Handle an incoming request.
     *
     * When Playwright E2E tests make requests, they include the X-Test-Auth header.
     * This middleware detects that header and switches to the e2e database.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if this is a Playwright E2E test request
        if ($request->header('X-Test-Auth') === 'playwright-e2e-tests') {
            // Only switch to bettrprompt_e2e if not already switched to data_collection
            // (SwitchDataCollectionDatabase middleware runs first and takes priority)
            $currentDb = Config::get('database.connections.pgsql.database');
            if ($currentDb !== 'bettrprompt_data_collection') {
                // Ensure queued jobs run inline for E2E requests so they share the same DB context.
                Config::set('queue.default', 'sync');

                // Switch to the E2E database
                Config::set('database.connections.pgsql.database', 'bettrprompt_e2e');

                // Reconnect to apply the new database setting
                app('db')->purge('pgsql');

                // Log for debugging
                Log::info('UseE2eDatabase middleware: Switched to bettrprompt_e2e database', [
                    'url' => $request->url(),
                    'current_db' => Config::get('database.connections.pgsql.database'),
                ]);
            } else {
                // Data collection database is already set, don't override it
                Log::info('UseE2eDatabase middleware: Data collection database already set, skipping E2E override', [
                    'url' => $request->url(),
                    'current_db' => $currentDb,
                ]);
            }
        }

        return $next($request);
    }
}
