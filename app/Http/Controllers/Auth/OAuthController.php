<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Middleware\SetCountry;
use App\Models\AnalyticsEvent;
use App\Models\PromptRun;
use App\Models\User;
use App\Models\Visitor;
use App\Services\DatabaseService;
use App\Services\GeolocationService;
use Exception;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;

class OAuthController extends Controller
{
    /**
     * Redirect to Google's OAuth page
     */
    public function redirectToGoogle(Request $request): RedirectResponse
    {
        try {
            return Socialite::driver('google')->redirect();
        } catch (Exception $e) {
            $country = SetCountry::detectCountry($request);

            Log::error('Failed to redirect to Google OAuth', [
                'error' => $e->getMessage(),
            ]);

            return redirect(countryRoute('home'))
                ->with('error', __('messages.auth.google_connection_failed'));
        }
    }

    /**
     * Handle the Google OAuth callback
     */
    public function handleGoogleCallback(Request $request): RedirectResponse
    {
        $country = SetCountry::detectCountry($request);

        try {
            // Get user data from Google
            $googleUser = Socialite::driver('google')->user();

            // Validate required fields from OAuth provider
            if (! $googleUser->id || ! $googleUser->email) {
                Log::warning('Google OAuth returned incomplete user data', [
                    'has_id' => (bool) $googleUser->id,
                    'has_email' => (bool) $googleUser->email,
                ]);

                return redirect(countryRoute('login'))
                    ->with('error', __('messages.auth.google_account_info_failed'));
            }

            // Validate email format
            if (! filter_var($googleUser->email, FILTER_VALIDATE_EMAIL)) {
                Log::warning('Google OAuth returned invalid email', [
                    'email' => $googleUser->email,
                ]);

                return redirect(countryRoute('login'))
                    ->with('error', __('messages.auth.google_invalid_email'));
            }

            // Find or create user with proper error handling
            $user = $this->findOrCreateUser($googleUser);

            if (! $user) {
                return redirect(countryRoute('login'))
                    ->with('error', __('messages.auth.account_creation_failed'));
            }

            // Check if we need to link visitor and copy data
            $isNewUser = $user->wasRecentlyCreated;
            $visitorId = $request->cookie('visitor_id');
            $claimedCount = 0;

            if ($visitorId) {
                $visitor = Visitor::find($visitorId);
                if ($visitor && ! $visitor->user_id) {
                    // Copy personality data, location data, and referrer from visitor to user
                    $updates = [];
                    if ($visitor->personality_type) {
                        $updates['personality_type'] = $visitor->personality_type;
                        $updates['trait_percentages'] = $visitor->trait_percentages;
                    }
                    if ($visitor->referred_by_user_id) {
                        $updates['referred_by_user_id'] = $visitor->referred_by_user_id;
                    }
                    // Copy location data from visitor
                    if ($visitor->hasLocationData()) {
                        $updates['country_code'] = $visitor->country_code;
                        $updates['country_name'] = $visitor->country_name;
                        $updates['region'] = $visitor->region;
                        $updates['city'] = $visitor->city;
                        $updates['timezone'] = $visitor->timezone;
                        $updates['currency_code'] = $visitor->currency_code;
                        $updates['latitude'] = $visitor->latitude;
                        $updates['longitude'] = $visitor->longitude;
                        $updates['language_code'] = $visitor->language_code;
                        $updates['location_detected_at'] = $visitor->location_detected_at;
                        $updates['location_manually_set'] = false; // Auto-detected
                        $updates['language_manually_set'] = false; // Auto-detected
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

                    Log::info('Guest converted to user on OAuth registration', [
                        'user_id' => $user->id,
                        'visitor_id' => $visitorId,
                        'claimed_prompt_runs' => $claimedCount,
                        'copied_personality' => (bool) $visitor->personality_type,
                        'copied_location' => $visitor->hasLocationData(),
                        'copied_referrer' => (bool) $visitor->referred_by_user_id,
                    ]);
                }
            }

            // Fallback: If user still doesn't have location data, look it up from IP
            if (! $user->hasLocationData() && config('geoip.enabled') && config('geoip.features.lookup_on_registration')) {
                try {
                    $geolocationService = new GeolocationService;
                    $locationData = $geolocationService->lookupIp($request->ip());

                    if ($locationData !== null) {
                        $user->update([
                            'country_code' => $locationData->countryCode,
                            'country_name' => $locationData->countryName,
                            'region' => $locationData->region,
                            'city' => $locationData->city,
                            'timezone' => $locationData->timezone,
                            'currency_code' => $locationData->currencyCode,
                            'latitude' => $locationData->latitude,
                            'longitude' => $locationData->longitude,
                            'language_code' => $locationData->languageCode,
                            'location_detected_at' => $locationData->detectedAt,
                            'location_manually_set' => false,
                            'language_manually_set' => false,
                        ]);

                        Log::info('Location detected from IP for OAuth user', [
                            'user_id' => $user->id,
                            'country' => $locationData->countryCode,
                            'ip' => $request->ip(),
                        ]);
                    }
                } catch (Exception $e) {
                    Log::error('Failed to lookup location for OAuth user', [
                        'user_id' => $user->id,
                        'ip' => $request->ip(),
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Update profile completion percentage
            $user->updateProfileCompletion();

            // Log the user in
            Auth::login($user, remember: true);

            // Track registration completion (only for new users)
            if ($isNewUser) {
                AnalyticsEvent::create([
                    'event_id' => (string) Str::uuid(),
                    'name' => 'registration_completed',
                    'visitor_id' => $request->cookie('visitor_id'),
                    'user_id' => $user->id,
                    'source' => 'server',
                    'occurred_at' => now(),
                    'properties' => [
                        'registration_method' => 'google',
                    ],
                ]);
            }

            Log::info('User authenticated via Google OAuth', [
                'user_id' => $user->id,
                'email' => $user->email,
                'is_new' => $isNewUser,
            ]);

            // Redirect to history page if visitor had completed prompts, otherwise to prompt builder
            if ($claimedCount > 0) {
                return redirect()->intended(countryRoute('prompt-builder.history'));
            }

            return redirect()->intended(countryRoute('prompt-builder.index'));

        } catch (InvalidStateException $e) {
            Log::warning('OAuth state validation failed', [
                'error' => $e->getMessage(),
            ]);

            return redirect(countryRoute('login'))
                ->with('error', __('messages.auth.session_expired'));

        } catch (ClientException $e) {
            Log::error('OAuth provider error', [
                'error' => $e->getMessage(),
                'status' => $e->getResponse()?->getStatusCode(),
            ]);

            return redirect(countryRoute('login'))
                ->with('error', __('messages.auth.google_communication_failed'));

        } catch (Exception $e) {
            Log::error('Unexpected error in Google OAuth callback', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect(countryRoute('login'))
                ->with('error', __('messages.auth.unexpected_error'));
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

        } catch (QueryException $e) {
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

        } catch (Exception $e) {
            Log::error('Unexpected error creating OAuth user', [
                'error' => $e->getMessage(),
                'email' => $oauthUser->email,
            ]);

            return null;
        }
    }
}
