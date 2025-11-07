<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackVisitor
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Check if the visitor cookie doesn't exist
        if (! $request->hasCookie('returning_visitor')) {
            // Set a cookie that expires in 1 year
            $cookie = cookie('returning_visitor', true, 525600); // 525600 minutes = 1 year
            $response->withCookie($cookie);
        }

        return $response;
    }
}
