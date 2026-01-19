<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class ShareSubscriptionStatus
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($user = $request->user()) {
            $subscription = $user->getSubscriptionStatus();
            Inertia::share('subscription', fn () => $subscription);
            // Debug: log the subscription for test users
            if ($user->email === 'test@example.com') {
                \Illuminate\Support\Facades\Log::debug('[TEST] ShareSubscriptionStatus', [
                    'user_id' => $user->id,
                    'subscription' => $subscription,
                ]);
            }
        }

        return $next($request);
    }
}
