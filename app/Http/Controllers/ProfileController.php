<?php

namespace App\Http\Controllers;

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
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

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

        // Get reference data for forms
        $countries = Country::sortedByName()->map(fn ($country) => [
            'value' => $country->id,
            'label' => $country->name,
        ])->values();

        $currencies = Currency::all()->map(fn ($currency) => [
            'value' => $currency->id,
            'label' => "{$currency->symbol} ({$currency->id})",
        ])->values();

        $languages = Language::active()->map(fn ($language) => [
            'value' => $language->id,
            'label' => $language->name,
        ])->values();

        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $user instanceof MustVerifyEmail,
            'status' => session('status'),
            'personalityTypes' => $personalityTypes,
            'uiComplexity' => $user->ui_complexity ?? 'advanced',
            // Profile data
            'profileCompletion' => $user->profile_completion_percentage,
            'locationData' => [
                'countryCode' => $user->country_code,
                'countryName' => $user->country_name,
                'region' => $user->region,
                'city' => $user->city,
                'timezone' => $user->timezone,
                'currencyCode' => $user->currency_code,
                'languageCode' => $user->language_code,
                'detectedAt' => $user->location_detected_at,
                'manuallySet' => $user->location_manually_set,
            ],
            // Reference data
            'countries' => $countries,
            'currencies' => $currencies,
            'languages' => $languages,
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

        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Failed to update user profile', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return Redirect::back()->with('error',
                'Failed to update profile. Please try again.');
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

        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Failed to update user personality type', [
                'user_id' => $request->user()->id,
                'personality_type' => $request->validated('personalityType'),
                'error' => $e->getMessage(),
            ]);

            return Redirect::back()->with('error',
                'Failed to update personality type. Please try again.');
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
                ->with('status', 'Your account has been deleted.');

        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Failed to delete user account', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            // Re-login the user since we logged them out
            Auth::login($user);

            return Redirect::back()->with('error',
                'Failed to delete account. Please try again or contact support.');
        } catch (\Throwable $e) {
            Log::error('Unexpected error during account deletion', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            // Re-login the user
            Auth::login($user);

            return Redirect::back()->with('error',
                'An unexpected error occurred. Please contact support.');
        }
    }

    /**
     * Update user location information.
     */
    public function updateLocation(UpdateLocationRequest $request): RedirectResponse
    {
        try {
            DatabaseService::retryOnDeadlock(function () use ($request) {
                $request->user()->update([
                    'country_code' => $request->validated('country_code'),
                    'region' => $request->validated('region'),
                    'city' => $request->validated('city'),
                    'timezone' => $request->validated('timezone'),
                    'currency_code' => $request->validated('currency_code'),
                    'language_code' => $request->validated('language_code'),
                    'location_manually_set' => true,
                ]);
                $request->user()->updateProfileCompletion();
            });

            return Redirect::route('profile.edit')
                ->with('status', 'location-updated');
        } catch (\Exception $e) {
            Log::error('Failed to update user location', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return Redirect::back()->with('error', 'Failed to update location. Please try again.');
        }
    }

    /**
     * Update user professional context.
     */
    public function updateProfessional(UpdateProfessionalRequest $request): RedirectResponse
    {
        try {
            DatabaseService::retryOnDeadlock(function () use ($request) {
                $request->user()->update([
                    'job_title' => $request->validated('job_title'),
                    'industry' => $request->validated('industry'),
                    'experience_level' => $request->validated('experience_level'),
                    'company_size' => $request->validated('company_size'),
                ]);
                $request->user()->updateProfileCompletion();
            });

            return Redirect::route('profile.edit')
                ->with('status', 'professional-updated');
        } catch (\Exception $e) {
            Log::error('Failed to update professional context', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return Redirect::back()->with('error', 'Failed to update professional context. Please try again.');
        }
    }

    /**
     * Update user team context.
     */
    public function updateTeam(UpdateTeamRequest $request): RedirectResponse
    {
        try {
            DatabaseService::retryOnDeadlock(function () use ($request) {
                $request->user()->update([
                    'team_size' => $request->validated('team_size'),
                    'team_role' => $request->validated('team_role'),
                    'work_mode' => $request->validated('work_mode'),
                ]);
                $request->user()->updateProfileCompletion();
            });

            return Redirect::route('profile.edit')
                ->with('status', 'team-updated');
        } catch (\Exception $e) {
            Log::error('Failed to update team context', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return Redirect::back()->with('error', 'Failed to update team context. Please try again.');
        }
    }

    /**
     * Update user budget preferences.
     */
    public function updateBudget(UpdateBudgetRequest $request): RedirectResponse
    {
        try {
            DatabaseService::retryOnDeadlock(function () use ($request) {
                $request->user()->update([
                    'budget_consciousness' => $request->validated('budget_consciousness'),
                ]);
                $request->user()->updateProfileCompletion();
            });

            return Redirect::route('profile.edit')
                ->with('status', 'budget-updated');
        } catch (\Exception $e) {
            Log::error('Failed to update budget preferences', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return Redirect::back()->with('error', 'Failed to update budget preferences. Please try again.');
        }
    }

    /**
     * Update user tool preferences.
     */
    public function updateTools(UpdateToolsRequest $request): RedirectResponse
    {
        try {
            DatabaseService::retryOnDeadlock(function () use ($request) {
                $request->user()->update([
                    'preferred_tools' => $request->validated('preferred_tools'),
                    'primary_programming_language' => $request->validated('primary_programming_language'),
                ]);
                $request->user()->updateProfileCompletion();
            });

            return Redirect::route('profile.edit')
                ->with('status', 'tools-updated');
        } catch (\Exception $e) {
            Log::error('Failed to update tool preferences', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return Redirect::back()->with('error', 'Failed to update tool preferences. Please try again.');
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
                return Redirect::back()->with('error',
                    'Could not detect location from your IP address. Please set it manually.');
            }

            DatabaseService::retryOnDeadlock(function () use ($request, $locationData) {
                $request->user()->update($locationData->toArray() + [
                    'location_manually_set' => false,
                ]);
                $request->user()->updateProfileCompletion();
            });

            return Redirect::route('profile.edit')
                ->with('status', 'location-detected-updated');
        } catch (\Exception $e) {
            Log::error('Failed to detect location', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return Redirect::back()->with('error', 'Failed to detect location. Please try again.');
        }
    }

    /**
     * Clear user location data.
     */
    public function clearLocation(Request $request): RedirectResponse
    {
        try {
            DatabaseService::retryOnDeadlock(function () use ($request) {
                $request->user()->update([
                    'country_code' => null,
                    'country_name' => null,
                    'region' => null,
                    'city' => null,
                    'timezone' => null,
                    'currency_code' => null,
                    'latitude' => null,
                    'longitude' => null,
                    'language_code' => null,
                    'location_detected_at' => null,
                    'location_manually_set' => false,
                    'language_manually_set' => false,
                ]);
                $request->user()->updateProfileCompletion();
            });

            return Redirect::route('profile.edit')
                ->with('status', 'location-cleared');
        } catch (\Exception $e) {
            Log::error('Failed to clear location', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return Redirect::back()->with('error', 'Failed to clear location. Please try again.');
        }
    }
}
