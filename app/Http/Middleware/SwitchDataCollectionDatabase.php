<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class SwitchDataCollectionDatabase
{
    /**
     * Handle an incoming request.
     *
     * When the X-Data-Collection-Test header is present, switch to the
     * personality_data_collection database instead of personality_e2e.
     * This allows data collection tests to persist data separately.
     */
    public function handle(Request $request, Closure $next)
    {
        if ($request->hasHeader('X-Data-Collection-Test')) {
            // Switch the default database connection to use personality_data_collection
            Config::set('database.connections.pgsql.database', 'personality_data_collection');

            // Reconnect to apply the new database configuration
            DB::purge('pgsql');

            // Mark this request as a data collection test for PromptRun models
            $request->attributes->set('is_data_collection', true);
        }

        return $next($request);
    }
}
