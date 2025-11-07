<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class OAuthController extends Controller
{
    /**
     * Redirect to Google's OAuth page
     */
    public function redirectToGoogle(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Handle the Google OAuth callback
     */
    public function handleGoogleCallback(): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();

            \Log::info('Google OAuth callback', [
                'google_user_id' => $googleUser->id,
                'google_user_email' => $googleUser->email,
                'google_user_name' => $googleUser->name,
            ]);

            // Find or create user
            $user = User::where('google_id', $googleUser->id)
                ->orWhere('email', $googleUser->email)
                ->first();

            if ($user) {
                \Log::info('Existing user found', ['user_id' => $user->id]);
                // Update existing user with Google ID if not already set
                if (! $user->google_id) {
                    $user->update([
                        'google_id' => $googleUser->id,
                        'avatar' => $googleUser->avatar,
                    ]);
                    \Log::info('Updated existing user with Google ID');
                }
            } else {
                \Log::info('Creating new user from Google OAuth');
                // Create new user
                $user = User::create([
                    'name' => $googleUser->name,
                    'email' => $googleUser->email,
                    'google_id' => $googleUser->id,
                    'avatar' => $googleUser->avatar,
                    'email_verified_at' => now(),
                    'password' => null, // OAuth users don't need a password
                ]);
                \Log::info('New user created', ['user_id' => $user->id]);
            }

            // Log the user in
            Auth::login($user, remember: true);
            \Log::info('User logged in successfully', ['user_id' => $user->id]);

            return redirect()->intended(route('prompt-optimizer.index'));
        } catch (\Exception $e) {
            \Log::error('Google OAuth failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()->route('home')
                ->with('error', 'Failed to authenticate with Google. Please try again.');
        }
    }
}
