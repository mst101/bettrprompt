<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Models\PromptRun;
use App\Models\User;
use App\Models\Visitor;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(RegisterRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        event(new Registered($user));

        // Link visitor to newly registered user
        $visitorId = $request->cookie('visitor_id');
        $claimedCount = 0;

        if ($visitorId) {
            $visitor = Visitor::find($visitorId);
            if ($visitor && ! $visitor->user_id) {
                // Copy personality data and referrer from visitor to user
                $updates = [];
                if ($visitor->personality_type) {
                    $updates['personality_type'] = $visitor->personality_type;
                    $updates['trait_percentages'] = $visitor->trait_percentages;
                }
                if ($visitor->referred_by_user_id) {
                    $updates['referred_by_user_id'] = $visitor->referred_by_user_id;
                }
                if (! empty($updates)) {
                    $user->update($updates);
                }

                // Update visitor record
                $visitor->update([
                    'user_id' => $user->id,
                    'converted_at' => now(),
                ]);

                // Claim all guest prompt runs
                $claimedCount = PromptRun::where('visitor_id', $visitorId)
                    ->whereNull('user_id')
                    ->update(['user_id' => $user->id]);

                Log::info('Guest converted to user on registration', [
                    'user_id' => $user->id,
                    'visitor_id' => $visitorId,
                    'claimed_prompt_runs' => $claimedCount,
                    'copied_personality' => (bool) $visitor->personality_type,
                    'copied_referrer' => (bool) $visitor->referred_by_user_id,
                ]);
            }
        }

        Auth::login($user);

        // Redirect to history page if visitor had completed prompts, otherwise to dashboard
        if ($claimedCount > 0) {
            return redirect(route('prompt-builder.history', absolute: false));
        }

        return redirect(route('dashboard', absolute: false));
    }
}
