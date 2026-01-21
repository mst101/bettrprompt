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
            $promptLimit = $user->getPromptLimit();
            $tier = $user->subscription_tier;

            // Suggest upgrade based on current tier
            $suggestedTier = match ($tier) {
                'free' => 'starter',
                'starter' => 'pro',
                'pro' => 'premium',
                default => null,
            };

            $errorMessage = __('messages.subscription.prompt_limit_reached');

            if ($suggestedTier) {
                $promptsForNext = config("stripe.tiers.{$suggestedTier}.monthly_prompt_limit");
                $promptsText = $promptsForNext === null ? 'unlimited' : $promptsForNext;
                $errorMessage .= ' '.__('messages.subscription.prompt_limit_reached_upgrade', [
                    'tier' => ucfirst($suggestedTier),
                    'prompts' => $promptsText,
                ]);
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'prompt_limit_reached',
                    'message' => $errorMessage,
                    'promptsUsed' => $user->monthly_prompt_count,
                    'promptLimit' => $promptLimit === PHP_INT_MAX ? null : $promptLimit,
                    'daysUntilReset' => $user->getDaysUntilPromptReset(),
                    'suggestedTier' => $suggestedTier,
                    'upgradeUrl' => route('pricing'),
                ], 403);
            }

            return redirect()->route('pricing')
                ->with('error', $errorMessage);
        }

        return $next($request);
    }
}
