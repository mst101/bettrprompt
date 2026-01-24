<?php

namespace App\Http\Controllers\Test;

use Illuminate\Http\Request;

/**
 * Test-only controller for e2e tests to manipulate user state
 * Only available when X-Test-Auth header is present
 */
class UserTestController
{
    /**
     * Update test user's prompt count for testing
     * This endpoint is test-only and protected by VerifyE2eTestAuth header
     *
     * Since this is a test-only endpoint with strict header validation,
     * the test can provide a user identifier (email or ID) in the request.
     * In a real API, this would be restricted to the authenticated user.
     */
    public function updatePrompts(Request $request)
    {
        $validated = $request->validate([
            'monthly_prompt_count' => 'required|integer|min:0|max:1000',
            'prompt_count_reset_at' => 'nullable|date',
            'email' => 'nullable|email',  // Allow test to specify which user to update
            'user_id' => 'nullable|integer',  // Or specify by ID
        ]);

        // Get the user to update
        // For tests: allow email or ID to be specified
        // For normal flow: use the authenticated user
        $user = null;

        if ($validated['email'] ?? null) {
            $user = \App\Models\User::where('email', $validated['email'])->first();
        } elseif ($validated['user_id'] ?? null) {
            $user = \App\Models\User::find($validated['user_id']);
        } else {
            // Try to get authenticated user
            $user = auth()->user() ?? auth('web')->user() ?? $request->user();
        }

        if (! $user) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'No user found. Specify email/user_id or ensure you are authenticated.',
            ], 401);
        }

        $user->update([
            'monthly_prompt_count' => $validated['monthly_prompt_count'],
            'prompt_count_reset_at' => $validated['prompt_count_reset_at'] ?? now(),
        ]);

        // Refresh to get updated data
        $user->refresh();

        return response()->json([
            'success' => true,
            'monthly_prompt_count' => $user->monthly_prompt_count,
            'prompt_count_reset_at' => $user->prompt_count_reset_at,
            'subscription_tier' => $user->subscription_tier,
            'isFree' => $user->isFree,
            'promptsRemaining' => $user->getPromptsRemaining(),
        ]);
    }
}
