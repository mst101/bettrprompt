<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforcePromptLimit
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        // Check if user can create prompt
        if (! $user->canCreatePrompt()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'prompt_limit_reached',
                    'message' => __('messages.subscription.prompt_limit_reached'),
                    'promptsUsed' => $user->monthly_prompt_count,
                    'promptLimit' => config('stripe.free_tier.monthly_prompt_limit'),
                    'daysUntilReset' => $user->getDaysUntilPromptReset(),
                    'upgradeUrl' => route('pricing'),
                ], 403);
            }

            return redirect()->route('pricing')
                ->with('error', __('messages.subscription.prompt_limit_reached_upgrade'));
        }

        return $next($request);
    }
}
