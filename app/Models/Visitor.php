<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Visitor extends Model
{
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_term',
        'utm_content',
        'current_utm_source',
        'current_utm_medium',
        'current_utm_campaign',
        'current_utm_term',
        'current_utm_content',
        'referrer',
        'landing_page',
        'user_agent',
        'ip_address',
        'first_visit_at',
        'last_visit_at',
        'converted_at',
        'personality_type',
        'trait_percentages',
        'ui_complexity',
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
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'first_visit_at' => 'datetime',
            'last_visit_at' => 'datetime',
            'converted_at' => 'datetime',
            'trait_percentages' => 'array',
            'ui_complexity' => 'string',
            // Location
            'location_detected_at' => 'datetime',
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    /**
     * Get the user associated with this visitor.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all prompt runs created by this visitor.
     */
    public function promptRuns(): HasMany
    {
        return $this->hasMany(PromptRun::class);
    }

    /**
     * Determine if the visitor has converted to a registered user.
     */
    public function hasConverted(): bool
    {
        return $this->user_id !== null && $this->converted_at !== null;
    }

    /**
     * Determine if this is a returning visitor.
     * A visitor is considered returning if they have visited more than once
     * (i.e. first_visit_at differs significantly from last_visit_at).
     */
    public function isReturning(): bool
    {
        if ($this->first_visit_at && $this->last_visit_at) {
            // Consider returning if more than 1 hour has passed between first and last visit
            return $this->first_visit_at->diffInHours($this->last_visit_at) >= 1;
        }

        return false;
    }

    /**
     * Determine if the visitor has completed at least one prompt run.
     */
    public function hasCompletedPrompts(): bool
    {
        return $this->promptRuns()
            ->where('workflow_stage', '2_completed')
            ->whereNotNull('optimized_prompt')
            ->exists();
    }

    /**
     * Check if visitor has location data
     */
    public function hasLocationData(): bool
    {
        return ! is_null($this->country_code) && ! is_null($this->timezone);
    }

    /**
     * Get a summary of the visitor's location
     */
    public function getLocationSummary(): string
    {
        if (! $this->hasLocationData()) {
            return __('messages.location.unknown');
        }

        if (is_null($this->city)) {
            return "$this->region, $this->country_name";
        }

        return "$this->city, $this->region, $this->country_name";
    }
}
