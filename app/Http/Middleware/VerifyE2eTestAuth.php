<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class VerifyE2eTestAuth
{
    public function handle(Request $request, Closure $next)
    {
        // Security: Verify X-Test-Auth header
        if ($request->header('X-Test-Auth') !== 'playwright-e2e-tests') {
            abort(403, 'Unauthorized test endpoint access');
        }

        return $next($request);
    }
}
