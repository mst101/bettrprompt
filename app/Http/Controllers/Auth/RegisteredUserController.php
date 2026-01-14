<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Middleware\SetCountry;
use App\Http\Requests\RegisterRequest;
use App\Models\AnalyticsEvent;
use App\Models\User;
use App\Services\GeolocationService;
use App\Services\VisitorMigrationService;
use Exception;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RegisteredUserController extends Controller
{
    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(RegisterRequest $request): RedirectResponse
    {
        $country = SetCountry::detectCountry($request);
        $validated = $request->validated();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        event(new Registered($user));

        // Migrate visitor data to newly registered user
        $visitorId = $request->cookie('visitor_id');
        $claimedCount = 0;

        if ($visitorId) {
            $migrationService = new VisitorMigrationService;
            $claimedCount = $migrationService->migrateVisitorToUser($user, $visitorId);
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

                    Log::info('Location detected from IP for new user', [
                        'user_id' => $user->id,
                        'country' => $locationData->countryCode,
                        'ip' => $request->ip(),
                    ]);
                }
            } catch (Exception $e) {
                Log::error('Failed to lookup location for new user', [
                    'user_id' => $user->id,
                    'ip' => $request->ip(),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Update profile completion percentage
        $user->updateProfileCompletion();

        Auth::login($user);

        // Track registration completion
        AnalyticsEvent::create([
            'event_id' => (string) Str::uuid(),
            'name' => 'registration_completed',
            'visitor_id' => $request->cookie('visitor_id'),
            'user_id' => $user->id,
            'source' => 'server',
            'occurred_at' => now(),
            'properties' => [
                'registration_method' => 'email',
            ],
        ]);

        // Redirect to history page if visitor had completed prompts, otherwise to prompt builder
        if ($claimedCount > 0) {
            return redirect(countryRoute('prompt-builder.history', [], false));
        }

        return redirect(countryRoute('prompt-builder.index', [], false));
    }
}
