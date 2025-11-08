<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\DatabaseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

class OAuthController extends Controller
{
    /**
     * Redirect to Google's OAuth page
     */
    public function redirectToGoogle(): RedirectResponse
    {
        try {
            return Socialite::driver('google')->redirect();
        } catch (\Exception $e) {
            Log::error('Failed to redirect to Google OAuth', [
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('home')
                ->with('error', 'Unable to connect to Google. Please try again later.');
        }
    }

    /**
     * Handle the Google OAuth callback
     */
    public function handleGoogleCallback(): RedirectResponse
    {
        try {
            // Get user data from Google
            $googleUser = Socialite::driver('google')->user();

            // Validate required fields from OAuth provider
            if (! $googleUser->id || ! $googleUser->email) {
                Log::warning('Google OAuth returned incomplete user data', [
                    'has_id' => ! ! $googleUser->id,
                    'has_email' => ! ! $googleUser->email,
                ]);

                return redirect()->route('login')
                    ->with('error', 'Could not retrieve your account information from Google. Please try again.');
            }

            // Validate email format
            if (! filter_var($googleUser->email, FILTER_VALIDATE_EMAIL)) {
                Log::warning('Google OAuth returned invalid email', [
                    'email' => $googleUser->email,
                ]);

                return redirect()->route('login')
                    ->with('error', 'Invalid email address received from Google. Please try again.');
            }

            // Find or create user with proper error handling
            $user = $this->findOrCreateUser($googleUser);

            if (! $user) {
                return redirect()->route('login')
                    ->with('error', 'Failed to create your account. Please try again or contact support.');
            }

            // Log the user in
            Auth::login($user, remember: true);

            Log::info('User authenticated via Google OAuth', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            return redirect()->intended(route('prompt-optimizer.index'));

        } catch (\Laravel\Socialite\Two\InvalidStateException $e) {
            Log::warning('OAuth state validation failed', [
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('login')
                ->with('error', 'Authentication session expired. Please try logging in again.');

        } catch (\GuzzleHttp\Exception\ClientException $e) {
            Log::error('OAuth provider error', [
                'error' => $e->getMessage(),
                'status' => $e->getResponse()?->getStatusCode(),
            ]);

            return redirect()->route('login')
                ->with('error', 'Failed to communicate with Google. Please try again later.');

        } catch (\Exception $e) {
            Log::error('Unexpected error in Google OAuth callback', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('login')
                ->with('error', 'An unexpected error occurred. Please try again.');
        }
    }

    /**
     * Find or create user from OAuth data
     */
    protected function findOrCreateUser($oauthUser): ?User
    {
        try {
            // Find existing user by Google ID or email
            $user = User::where('google_id', $oauthUser->id)
                ->orWhere('email', $oauthUser->email)
                ->first();

            if ($user) {
                // Update existing user with Google ID and avatar if not already set
                if (! $user->google_id || $user->avatar !== $oauthUser->avatar) {
                    DatabaseService::retryOnDeadlock(function () use ($user, $oauthUser) {
                        $user->update([
                            'google_id' => $oauthUser->id,
                            'avatar' => $oauthUser->avatar,
                        ]);
                    });
                }

                return $user;
            }

            // Create new user with retry logic
            return DatabaseService::retryOnDeadlock(function () use ($oauthUser) {
                return User::create([
                    'name' => $oauthUser->name ?? 'Google User',
                    'email' => $oauthUser->email,
                    'google_id' => $oauthUser->id,
                    'avatar' => $oauthUser->avatar,
                    'email_verified_at' => now(),
                    'password' => null, // OAuth users don't need a password
                ]);
            });

        } catch (\Illuminate\Database\QueryException $e) {
            $errorCode = $e->errorInfo[1] ?? null;

            // Handle duplicate email
            if (in_array($errorCode, [1062, '23505'])) {
                Log::error('Email conflict in OAuth user creation', [
                    'email' => $oauthUser->email,
                    'google_id' => $oauthUser->id,
                ]);
            } else {
                Log::error('Database error in OAuth user creation', [
                    'error' => $e->getMessage(),
                    'error_code' => $errorCode,
                    'email' => $oauthUser->email,
                ]);
            }

            return null;

        } catch (\Exception $e) {
            Log::error('Unexpected error creating OAuth user', [
                'error' => $e->getMessage(),
                'email' => $oauthUser->email,
            ]);

            return null;
        }
    }
}
