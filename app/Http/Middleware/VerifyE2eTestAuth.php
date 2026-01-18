<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class VerifyE2eTestAuth
{
    public function handle(Request $request, Closure $next)
    {
        // Security: Verify X-Test-Auth header first
        // If the header is present and valid, allow access regardless of environment
        // This is the primary security check
        if ($request->header('X-Test-Auth') === 'playwright-e2e-tests') {
            return $next($request);
        }

        // If no valid header, check environment as additional safety net
        if (! app()->environment('e2e')) {
            abort(404);
        }

        // If we get here, environment is e2e but header wasn't provided
        abort(403, 'Unauthorized test endpoint access');
    }
}
