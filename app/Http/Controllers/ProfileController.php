<?php

namespace App\Http\Controllers;

use App\Http\Middleware\SetCountry;
use App\Http\Requests\DeleteProfileRequest;
use App\Http\Requests\ProfileUpdateRequest;
use App\Http\Requests\UpdateBudgetRequest;
use App\Http\Requests\UpdateLocationRequest;
use App\Http\Requests\UpdatePersonalityTypeRequest;
use App\Http\Requests\UpdateProfessionalRequest;
use App\Http\Requests\UpdateTeamRequest;
use App\Http\Requests\UpdateToolsRequest;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Language;
use App\Services\DatabaseService;
use App\Services\GeolocationService;
use Exception;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        $personalityTypes = [
            'INTJ' => 'Architect',
            'INTP' => 'Logician',
            'ENTJ' => 'Commander',
            'ENTP' => 'Debater',
            'INFJ' => 'Advocate',
            'INFP' => 'Mediator',
            'ENFJ' => 'Protagonist',
            'ENFP' => 'Campaigner',
            'ISTJ' => 'Logistician',
            'ISFJ' => 'Defender',
            'ESTJ' => 'Executive',
            'ESFJ' => 'Consul',
            'ISTP' => 'Virtuoso',
            'ISFP' => 'Adventurer',
            'ESTP' => 'Entrepreneur',
            'ESFP' => 'Entertainer',
        ];

        $user = $request->user();
        $routeCountry = $request->route('country');
        $useRouteDefaults = $routeCountry
            && (! $user->country_code || strtolower($routeCountry) !== strtolower($user->country_code));
        $routeCountryModel = $routeCountry
            ? Country::with(['language', 'currency'])->find($routeCountry)
            : null;
        $resolvedCurrencyCode = $useRouteDefaults && $routeCountry
            ? (new SetCountry)->resolveCurrencyCode($routeCountry, $request)
            : $user->currency_code;
        $resolvedLanguageCode = $useRouteDefaults
            ? app()->getLocale()
            : $user->language_code;
        $resolvedCountryCode = $useRouteDefaults && $routeCountry
            ? $routeCountry
            : $user->country_code;
        $resolvedCountryName = $useRouteDefaults
            ? ($routeCountryModel?->name ?? $user->country_name)
            : $user->country_name;
        $resolvedRegion = $useRouteDefaults ? null : $user->region;
        $resolvedCity = $useRouteDefaults ? null : $user->city;
        $resolvedTimezone = $useRouteDefaults ? null : $user->timezone;

        // Get reference data for forms
        $countriesCollection = Country::with(['language', 'currency'])
            ->orderBy('name', 'asc')
            ->get();
        $countries = $countriesCollection->map(fn ($country) => [
            'value' => $country->id,
            'label' => $country->name,
        ])->values();

        $activeCurrencies = Currency::where('active', true)->get();
        $activeCurrencyIds = $activeCurrencies->pluck('id')->all();
        $currencies = $activeCurrencies->map(fn ($currency) => [
            'value' => $currency->id,
            'label' => "$currency->symbol ($currency->id)",
        ])->values();

        // Use supported locales from config instead of database languages
        // This ensures all UI languages are always available
        $supportedLocales = config('app.supported_locales');
        $languageLabels = [
            'en-US' => 'English (US)',
            'en-GB' => 'English (UK)',
            'de-DE' => 'Deutsch',
            'fr-FR' => 'Français',
            'es-ES' => 'Español',
        ];
        $languages = collect($supportedLocales)
            ->map(fn ($locale) => [
                'value' => $locale,
                'label' => $languageLabels[$locale] ?? $locale,
            ])
            ->values();
        $countryDefaults = $countriesCollection->mapWithKeys(function ($country) use (
            $activeCurrencyIds
        ) {
            $languageCode = $country->language?->id;
            $normalizedLanguage = $languageCode
                ? SetCountry::normalizeLocaleToSupported($languageCode)
                : null;

            $currencyCode = $country->currency_id;
            $normalizedCurrency = $currencyCode && in_array($currencyCode, $activeCurrencyIds, true)
                ? $currencyCode
                : config('app.fallback_currency', 'USD');

            return [
                $country->id => [
                    'currencyCode' => $normalizedCurrency,
                    'languageCode' => $normalizedLanguage
                        ?? config('app.fallback_locale', 'en-US'),
                ],
            ];
        });

        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $user instanceof MustVerifyEmail,
            'status' => session('status'),
            'personalityTypes' => $personalityTypes,
            'uiComplexity' => $user->ui_complexity ?? 'advanced',
            // Profile data
            'profileCompletion' => $user->profile_completion_percentage,
            'locationData' => [
                'countryCode' => $resolvedCountryCode,
                'countryName' => $resolvedCountryName,
                'region' => $resolvedRegion,
                'city' => $resolvedCity,
                'timezone' => $resolvedTimezone,
                'currencyCode' => $resolvedCurrencyCode,
                'languageCode' => $resolvedLanguageCode,
                'detectedAt' => $user->location_detected_at,
                'manuallySet' => $user->location_manually_set,
            ],
            // Reference data
            'countries' => $countries,
            'currencies' => $currencies,
            'languages' => $languages,
            'countryDefaults' => $countryDefaults,
            'professionalData' => [
                'jobTitle' => $user->job_title,
                'industry' => $user->industry,
                'experienceLevel' => $user->experience_level,
                'companySize' => $user->company_size,
            ],
            'teamData' => [
                'teamSize' => $user->team_size,
                'teamRole' => $user->team_role,
                'workMode' => $user->work_mode,
            ],
            'budgetData' => [
                'budgetConsciousness' => $user->budget_consciousness,
            ],
            'toolsData' => [
                'preferredTools' => $user->preferred_tools ?? [],
                'primaryProgrammingLanguage' => $user->primary_programming_language,
            ],
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        try {
            DatabaseService::retryOnDeadlock(function () use ($request) {
                $request->user()->fill($request->validated());

                if ($request->user()->isDirty('email')) {
                    $request->user()->email_verified_at = null;
                }

                $request->user()->save();
            });

            return Redirect::route('profile.edit')
                ->with('status', 'profile-updated');

        } catch (QueryException $e) {
            Log::error('Failed to update user profile', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return Redirect::back()->with('error', __('messages.profile.update_failed'));
        }
    }

    /**
     * Update the user's personality type.
     */
    public function updatePersonality(UpdatePersonalityTypeRequest $request): RedirectResponse
    {
        try {
            DatabaseService::retryOnDeadlock(function () use ($request) {
                $request->user()->update([
                    'personality_type' => $request->validated('personalityType'),
                    'trait_percentages' => $request->validated('traitPercentages'),
                ]);
            });

            return Redirect::route('profile.edit')
                ->with('status', 'personality-updated');

        } catch (QueryException $e) {
            Log::error('Failed to update user personality type', [
                'user_id' => $request->user()->id,
                'personality_type' => $request->validated('personalityType'),
                'error' => $e->getMessage(),
            ]);

            return Redirect::back()->with('error', __('messages.profile.personality_update_failed'));
        }
    }

    /**
     * Update the user's UI complexity preference.
     */
    public function updateUiComplexity(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'uiComplexity' => ['required', 'in:simple,advanced'],
        ]);

        $request->user()->update([
            'ui_complexity' => $validated['uiComplexity'],
        ]);

        return Redirect::route('profile.edit')
            ->with('status', 'ui-complexity-updated');
    }

    /**
     * Update the user's language preference.
     * Also updates any associated visitor record to keep them in sync.
     */
    public function updateLanguage(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'language_code' => ['required', 'string', 'max:10', Rule::in(config('app.supported_locales'))],
        ]);

        $user = $request->user();

        // Update user's language preference
        $user->update([
            'language_code' => $validated['language_code'],
            'language_manually_set' => true,
        ]);

        // Also update any associated visitor record (from before conversion)
        // This keeps both tables in sync when a converted user switches languages
        \App\Models\Visitor::where('user_id', $user->id)
            ->update(['language_code' => $validated['language_code']]);

        // Invalidate language cache so middleware fetches fresh value on next request
        Cache::forget("user.{$user->id}.language");

        return response()->json(['success' => true]);
    }

    /**
     * Delete the user's account.
     */
    public function destroy(DeleteProfileRequest $request): RedirectResponse
    {
        $user = $request->user();

        try {
            // Use transaction to ensure all operations succeed or fail together
            DatabaseService::transaction(function () use ($user, $request) {
                Auth::logout();
                $user->delete();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            });

            return Redirect::to('/')
                ->with('status', __('messages.profile.account_deleted'));

        } catch (QueryException $e) {
            Log::error('Failed to delete user account', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            // Re-login the user since we logged them out
            Auth::login($user);

            return Redirect::back()->with('error', __('messages.profile.delete_account_failed'));
        } catch (Throwable $e) {
            Log::error('Unexpected error during account deletion', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            // Re-login the user
            Auth::login($user);

            return Redirect::back()->with('error', __('messages.profile.unexpected_error'));
        }
    }

    /**
     * Update user location information.
     */
    public function updateLocation(UpdateLocationRequest $request): RedirectResponse|JsonResponse
    {
        try {
            $user = $request->user();
            $validated = $request->validated();
            $currentCountry = $request->route('country');

            // Update location
            $user->updateLocation($validated);

            // Invalidate language cache if language was updated
            if (array_key_exists('language_code', $validated)) {
                Cache::forget("user.{$user->id}.language");
            }
            if (array_key_exists('currency_code', $validated)) {
                Cache::forget("user.{$user->id}.currency");
            }

            // Redirect to profile page (language preference is stored in user profile,
            // country code in URL stays the same)
            if ($request->wantsJson()) {
                return response()->json(['success' => true]);
            }

            return Redirect::route('profile.edit')
                ->with('status', 'location-updated');
        } catch (Exception $e) {
            Log::error('Failed to update user location', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return Redirect::back()->with('error', __('messages.profile.location_update_failed'));
        }
    }

    /**
     * Update user professional context.
     */
    public function updateProfessional(UpdateProfessionalRequest $request): RedirectResponse
    {
        try {
            $request->user()->updateProfessional($request->validated());

            return Redirect::route('profile.edit')
                ->with('status', 'professional-updated');
        } catch (Exception $e) {
            Log::error('Failed to update professional context', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return Redirect::back()->with('error', __('messages.profile.professional_update_failed'));
        }
    }

    /**
     * Update user team context.
     */
    public function updateTeam(UpdateTeamRequest $request): RedirectResponse
    {
        try {
            $request->user()->updateTeam($request->validated());

            return Redirect::route('profile.edit')
                ->with('status', 'team-updated');
        } catch (Exception $e) {
            Log::error('Failed to update team context', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return Redirect::back()->with('error', __('messages.profile.team_update_failed'));
        }
    }

    /**
     * Update user budget preferences.
     */
    public function updateBudget(UpdateBudgetRequest $request): RedirectResponse
    {
        try {
            $request->user()->updateBudget($request->validated());

            return Redirect::route('profile.edit')
                ->with('status', 'budget-updated');
        } catch (Exception $e) {
            Log::error('Failed to update budget preferences', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return Redirect::back()->with('error', __('messages.profile.budget_update_failed'));
        }
    }

    /**
     * Update user tool preferences.
     */
    public function updateTools(UpdateToolsRequest $request): RedirectResponse
    {
        try {
            $request->user()->updateTools($request->validated());

            return Redirect::route('profile.edit')
                ->with('status', 'tools-updated');
        } catch (Exception $e) {
            Log::error('Failed to update tool preferences', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return Redirect::back()->with('error', __('messages.profile.tools_update_failed'));
        }
    }

    /**
     * Detect and update user location from current IP.
     */
    public function detectLocation(Request $request): RedirectResponse
    {
        try {
            $geolocationService = new GeolocationService;
            $locationData = $geolocationService->lookupIp($request->ip());

            if ($locationData === null) {
                return Redirect::back()->with('error', __('messages.profile.location_detect_failed'));
            }

            $user = $request->user();
            $oldLanguageCode = $user->language_code;
            $newLanguageCode = null;
            $currentCountry = $request->route('country');
            $detectedCountry = $locationData->countryCode ?? $currentCountry;

            DatabaseService::retryOnDeadlock(function () use ($user, $locationData, &$newLanguageCode) {
                $updateData = $locationData->toArray() + [
                    'location_manually_set' => false,
                ];

                $user->update($updateData);

                // Also update visitor record if language is detected
                if (isset($updateData['language_code'])) {
                    $newLanguageCode = $updateData['language_code'];
                    \App\Models\Visitor::where('user_id', $user->id)
                        ->update(['language_code' => $updateData['language_code']]);
                }

                $user->updateProfileCompletion();
            });

            // Invalidate language cache if language was detected and changed
            if ($newLanguageCode && $newLanguageCode !== $oldLanguageCode) {
                Cache::forget("user.{$user->id}.language");
            }

            // If language was detected and changed, redirect to the detected country's profile page
            // Use the detected country code from the geolocation, or current country if not changed
            if ($newLanguageCode && $newLanguageCode !== $oldLanguageCode) {
                return Redirect::to("/{$detectedCountry}/profile")
                    ->with('status', 'location-detected-updated');
            }

            if ($detectedCountry && $detectedCountry !== $currentCountry) {
                return Redirect::to("/{$detectedCountry}/profile")
                    ->with('status', 'location-detected-updated');
            }

            return Redirect::route('profile.edit')
                ->with('status', 'location-detected-updated');
        } catch (Exception $e) {
            Log::error('Failed to detect location', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return Redirect::back()->with('error', __('messages.profile.location_detection_failed'));
        }
    }

    /**
     * Clear user location data.
     */
    public function clearLocation(Request $request): RedirectResponse|JsonResponse
    {
        try {
            $request->user()->clearLocation();

            Cache::forget("user.{$request->user()->id}.language");
            Cache::forget("user.{$request->user()->id}.currency");

            if ($request->wantsJson()) {
                return response()->json(['success' => true]);
            }

            return Redirect::route('profile.edit')
                ->with('status', 'location-cleared');
        } catch (Exception $e) {
            Log::error('Failed to clear location', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return Redirect::back()->with('error', __('messages.profile.location_clear_failed'));
        }
    }

    /**
     * Update the user's location prompt preference.
     */
    public function updateLocationPromptPreference(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'dismissed' => ['required', 'boolean'],
        ]);

        $request->user()->update([
            'location_prompt_dismissed' => $validated['dismissed'],
        ]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return Redirect::back();
    }

    public function clearProfessional(Request $request): RedirectResponse
    {
        try {
            $request->user()->clearProfessional();

            return Redirect::route('profile.edit')
                ->with('status', 'professional-cleared');
        } catch (Exception $e) {
            Log::error('Failed to clear professional information', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return Redirect::back()->with('error', __('messages.profile.professional_clear_failed'));
        }
    }

    public function clearTeam(Request $request): RedirectResponse
    {
        try {
            $request->user()->clearTeam();

            return Redirect::route('profile.edit')
                ->with('status', 'team-cleared');
        } catch (Exception $e) {
            Log::error('Failed to clear team information', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return Redirect::back()->with('error', __('messages.profile.team_clear_failed'));
        }
    }

    public function clearBudget(Request $request): RedirectResponse
    {
        try {
            $request->user()->clearBudget();

            return Redirect::route('profile.edit')
                ->with('status', 'budget-cleared');
        } catch (Exception $e) {
            Log::error('Failed to clear budget preferences', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return Redirect::back()->with('error', __('messages.profile.budget_clear_failed'));
        }
    }

    public function clearTools(Request $request): RedirectResponse
    {
        try {
            $request->user()->clearTools();

            return Redirect::route('profile.edit')
                ->with('status', 'tools-cleared');
        } catch (Exception $e) {
            Log::error('Failed to clear tools & technologies', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return Redirect::back()->with('error', __('messages.profile.tools_clear_failed'));
        }
    }
}
