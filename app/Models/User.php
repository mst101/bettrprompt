<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Services\DatabaseService;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use Billable, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'google_id',
        'avatar',
        'personality_type',
        'trait_percentages',
        'ui_complexity',
        'referral_code',
        'referred_by_user_id',
        // Location fields
        'country_code',
        'country_name',
        'region',
        'city',
        'timezone',
        'currency_code',
        'latitude',
        'longitude',
        'language_code',
        'location_detected_at',
        'location_manually_set',
        'language_manually_set',
        // Professional context
        'job_title',
        'industry',
        'experience_level',
        'company_size',
        // Team and budget context
        'team_size',
        'team_role',
        'budget_consciousness',
        'work_mode',
        // Tool preferences
        'preferred_tools',
        'primary_programming_language',
        'profile_completion_percentage',
        'profile_last_updated_at',
        // Subscription fields
        'subscription_tier',
        'subscription_ends_at',
        'monthly_prompt_count',
        'prompt_count_reset_at',
        // Privacy fields
        'privacy_enabled',
        'encrypted_dek',
        'recovery_dek',
        'dek_created_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'encrypted_dek',
        'recovery_dek',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'trait_percentages' => 'array',
            'ui_complexity' => 'string',
            'is_admin' => 'boolean',
            // Location
            'location_detected_at' => 'datetime',
            'location_manually_set' => 'boolean',
            'language_manually_set' => 'boolean',
            'latitude' => 'float',
            'longitude' => 'float',
            // Tool preferences
            'preferred_tools' => 'array',
            'profile_last_updated_at' => 'datetime',
            'profile_completion_percentage' => 'integer',
            // Subscription
            'subscription_ends_at' => 'datetime',
            'prompt_count_reset_at' => 'datetime',
            'trial_ends_at' => 'datetime',
            // Privacy
            'privacy_enabled' => 'boolean',
            'dek_created_at' => 'datetime',
        ];
    }

    /**
     * Get the visitor records linked to this user
     */
    public function visitors(): HasMany
    {
        return $this->hasMany(Visitor::class);
    }

    /**
     * Get the prompt runs owned by this user
     */
    public function promptRuns(): HasMany
    {
        return $this->hasMany(PromptRun::class);
    }

    /**
     * Generate and set a unique referral code for this user
     */
    public function generateReferralCode(): string
    {
        do {
            $code = strtoupper(substr(md5(uniqid($this->id, true)), 0, 8));
        } while (self::where('referral_code', $code)->exists());

        $this->referral_code = $code;
        $this->save();

        return $code;
    }

    /**
     * Get or generate the referral code for this user
     */
    public function getReferralCode(): string
    {
        if (! $this->referral_code) {
            return $this->generateReferralCode();
        }

        return $this->referral_code;
    }

    /**
     * Check if user has location data
     */
    public function hasLocationData(): bool
    {
        return ! is_null($this->country_code) && ! is_null($this->timezone);
    }

    /**
     * Calculate user profile completion percentage
     */
    public function calculateProfileCompletion(): int
    {
        $totalFields = 0;
        $completedFields = 0;

        // Location fields (weight: 2)
        $locationFields = ['country_code', 'timezone', 'currency_code', 'language_code'];
        $totalFields += count($locationFields) * 2;
        foreach ($locationFields as $field) {
            if (! is_null($this->$field)) {
                $completedFields += 2;
            }
        }

        // Professional fields (weight: 1.5)
        $professionalFields = ['job_title', 'industry', 'experience_level', 'company_size'];
        $totalFields += count($professionalFields) * 1.5;
        foreach ($professionalFields as $field) {
            if (! is_null($this->$field)) {
                $completedFields += 1.5;
            }
        }

        // Team fields (weight: 1)
        $teamFields = ['team_size', 'team_role', 'work_mode'];
        $totalFields += count($teamFields);
        foreach ($teamFields as $field) {
            if (! is_null($this->$field)) {
                $completedFields += 1;
            }
        }

        // Budget preference (weight: 1)
        $totalFields += 1;
        if (! is_null($this->budget_consciousness)) {
            $completedFields += 1;
        }

        // Tools (weight: 0.5)
        $totalFields += 0.5;
        if (! is_null($this->preferred_tools) && count($this->preferred_tools) > 0) {
            $completedFields += 0.5;
        }

        return $totalFields === 0 ? 0 : (int) round(($completedFields / $totalFields) * 100);
    }

    /**
     * Update profile completion percentage
     */
    public function updateProfileCompletion(): void
    {
        $this->profile_completion_percentage = $this->calculateProfileCompletion();
        $this->profile_last_updated_at = now();
        $this->save();
    }

    /**
     * Get UI complexity level (defaults to 'advanced')
     */
    public function getUiComplexity(): string
    {
        return $this->ui_complexity ?? 'advanced';
    }

    /**
     * Build user context for workflow usage
     */
    public function getUserContext(): array
    {
        return [
            'location' => [
                'country' => $this->country_name,
                'country_code' => $this->country_code,
                'region' => $this->region,
                'city' => $this->city,
                'timezone' => $this->timezone,
                'currency' => $this->currency_code,
                'language' => $this->language_code,
            ],
            'professional' => [
                'job_title' => $this->job_title,
                'industry' => $this->industry,
                'experience_level' => $this->experience_level,
                'company_size' => $this->company_size,
            ],
            'team' => [
                'size' => $this->team_size,
                'role' => $this->team_role,
                'work_mode' => $this->work_mode,
            ],
            'preferences' => [
                'budget_consciousness' => $this->budget_consciousness,
                'preferred_tools' => $this->preferred_tools ?? [],
                'primary_programming_language' => $this->primary_programming_language,
            ],
            'personality' => [
                'type' => $this->personality_type,
                'trait_percentages' => $this->trait_percentages,
            ],
        ];
    }

    /**
     * Update user location data
     */
    public function updateLocation(array $locationData): void
    {
        DatabaseService::retryOnDeadlock(function () use ($locationData) {
            // Build update array with only provided keys, supporting partial updates
            $updates = array_filter([
                'country_code' => $locationData['country_code'] ?? null,
                'region' => $locationData['region'] ?? null,
                'city' => $locationData['city'] ?? null,
                'timezone' => $locationData['timezone'] ?? null,
                'currency_code' => $locationData['currency_code'] ?? null,
                'language_code' => $locationData['language_code'] ?? null,
            ], fn ($value) => $value !== null, ARRAY_FILTER_USE_BOTH);

            // Always mark location as manually set when updating
            $updates['location_manually_set'] = true;

            if (! empty($updates)) {
                $this->update($updates);
                $this->updateProfileCompletion();
            }
        });
    }

    /**
     * Update user professional context
     */
    public function updateProfessional(array $professionalData): void
    {
        DatabaseService::retryOnDeadlock(function () use ($professionalData) {
            // Build update array with only provided keys, supporting partial updates
            $updates = array_filter([
                'job_title' => $professionalData['job_title'] ?? null,
                'industry' => $professionalData['industry'] ?? null,
                'experience_level' => $professionalData['experience_level'] ?? null,
                'company_size' => $professionalData['company_size'] ?? null,
            ], fn ($value) => $value !== null, ARRAY_FILTER_USE_BOTH);

            if (! empty($updates)) {
                $this->update($updates);
                $this->updateProfileCompletion();
            }
        });
    }

    /**
     * Update user team context
     */
    public function updateTeam(array $teamData): void
    {
        DatabaseService::retryOnDeadlock(function () use ($teamData) {
            // Build update array with only provided keys, supporting partial updates
            $updates = array_filter([
                'team_size' => $teamData['team_size'] ?? null,
                'team_role' => $teamData['team_role'] ?? null,
                'work_mode' => $teamData['work_mode'] ?? null,
            ], fn ($value) => $value !== null, ARRAY_FILTER_USE_BOTH);

            if (! empty($updates)) {
                $this->update($updates);
                $this->updateProfileCompletion();
            }
        });
    }

    /**
     * Update user budget preferences
     */
    public function updateBudget(array $budgetData): void
    {
        DatabaseService::retryOnDeadlock(function () use ($budgetData) {
            // Build update array with only provided keys, supporting partial updates
            $updates = array_filter([
                'budget_consciousness' => $budgetData['budget_consciousness'] ?? null,
            ], fn ($value) => $value !== null, ARRAY_FILTER_USE_BOTH);

            if (! empty($updates)) {
                $this->update($updates);
                $this->updateProfileCompletion();
            }
        });
    }

    /**
     * Update user tool preferences
     */
    public function updateTools(array $toolsData): void
    {
        DatabaseService::retryOnDeadlock(function () use ($toolsData) {
            // Build update array with only provided keys, supporting partial updates
            $updates = array_filter([
                'preferred_tools' => $toolsData['preferred_tools'] ?? null,
                'primary_programming_language' => $toolsData['primary_programming_language'] ?? null,
            ], fn ($value) => $value !== null, ARRAY_FILTER_USE_BOTH);

            if (! empty($updates)) {
                $this->update($updates);
                $this->updateProfileCompletion();
            }
        });
    }

    /**
     * Clear user location data
     */
    public function clearLocation(): void
    {
        DatabaseService::retryOnDeadlock(function () {
            $this->update([
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
            $this->updateProfileCompletion();
        });
    }

    /**
     * Clear user professional context
     */
    public function clearProfessional(): void
    {
        DatabaseService::retryOnDeadlock(function () {
            $this->update([
                'job_title' => null,
                'industry' => null,
                'experience_level' => null,
                'company_size' => null,
            ]);
            $this->updateProfileCompletion();
        });
    }

    /**
     * Clear user team context
     */
    public function clearTeam(): void
    {
        DatabaseService::retryOnDeadlock(function () {
            $this->update([
                'team_size' => null,
                'team_role' => null,
                'work_mode' => null,
            ]);
            $this->updateProfileCompletion();
        });
    }

    /**
     * Clear user budget preferences
     */
    public function clearBudget(): void
    {
        DatabaseService::retryOnDeadlock(function () {
            $this->update([
                'budget_consciousness' => null,
            ]);
            $this->updateProfileCompletion();
        });
    }

    /**
     * Clear user tool preferences
     */
    public function clearTools(): void
    {
        DatabaseService::retryOnDeadlock(function () {
            $this->update([
                'preferred_tools' => null,
                'primary_programming_language' => null,
            ]);
            $this->updateProfileCompletion();
        });
    }

    /**
     * Check if user is on Pro tier (either via active subscription or grace period)
     */
    public function isPro(): bool
    {
        return $this->subscribed('default') ||
               ($this->subscription_ends_at && $this->subscription_ends_at->isFuture());
    }

    /**
     * Check if user is on free tier
     */
    public function isFree(): bool
    {
        return ! $this->isPro();
    }

    /**
     * Get remaining prompts for free tier users
     */
    public function getPromptsRemaining(): int
    {
        if ($this->isPro()) {
            return PHP_INT_MAX; // Unlimited
        }

        $limit = config('stripe.free_tier.monthly_prompt_limit', 10);

        return max(0, $limit - $this->monthly_prompt_count);
    }

    /**
     * Check if user can create a prompt
     */
    public function canCreatePrompt(): bool
    {
        return $this->isPro() || $this->getPromptsRemaining() > 0;
    }

    /**
     * Increment prompt count (for free tier tracking)
     */
    public function incrementPromptCount(): void
    {
        // Reset if new month
        if (! $this->prompt_count_reset_at || $this->prompt_count_reset_at->isLastMonth()) {
            $this->update([
                'monthly_prompt_count' => 1,
                'prompt_count_reset_at' => now(),
            ]);
        } else {
            $this->increment('monthly_prompt_count');
        }
    }

    /**
     * Get subscription status for frontend
     */
    public function getSubscriptionStatus(): array
    {
        return [
            'tier' => $this->isPro() ? 'pro' : 'free',
            'isPro' => $this->isPro(),
            'promptsUsed' => $this->monthly_prompt_count ?? 0,
            'promptsRemaining' => $this->getPromptsRemaining(),
            'promptLimit' => config('stripe.free_tier.monthly_prompt_limit', 10),
            'subscriptionEndsAt' => $this->subscription_ends_at?->toIso8601String(),
            'onGracePeriod' => $this->subscription('default')?->onGracePeriod() ?? false,
        ];
    }

    // ========================
    // Privacy Methods
    // ========================

    /**
     * Check if user has privacy encryption enabled
     */
    public function hasPrivacyEnabled(): bool
    {
        return $this->privacy_enabled && $this->encrypted_dek !== null;
    }

    /**
     * Check if user can enable privacy (must be Pro)
     */
    public function canEnablePrivacy(): bool
    {
        return $this->isPro() && ! $this->privacy_enabled;
    }

    /**
     * Check if user needs a password to enable privacy (OAuth users)
     */
    public function needsPasswordForPrivacy(): bool
    {
        // OAuth users have no password (google_id set but password may be the hash of OAuth token)
        return $this->google_id !== null && $this->password === null;
    }

    /**
     * Get privacy status for frontend
     */
    public function getPrivacyStatus(): array
    {
        return [
            'enabled' => $this->hasPrivacyEnabled(),
            'canEnable' => $this->canEnablePrivacy(),
            'needsPassword' => $this->needsPasswordForPrivacy(),
            'setupAt' => $this->dek_created_at?->toIso8601String(),
        ];
    }
}
