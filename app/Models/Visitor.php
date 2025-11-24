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
        'referrer',
        'landing_page',
        'user_agent',
        'ip_address',
        'first_visit_at',
        'last_visit_at',
        'visit_count',
        'converted_at',
        'personality_type',
        'trait_percentages',
        'referred_by_user_id',
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
            'visit_count' => 'integer',
            'trait_percentages' => 'array',
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
     */
    public function isReturning(): bool
    {
        return $this->visit_count > 1;
    }

    /**
     * Determine if the visitor has completed at least one prompt run.
     */
    public function hasCompletedPrompts(): bool
    {
        return $this->promptRuns()
            ->where('status', 'completed')
            ->where('workflow_stage', 'completed')
            ->whereNotNull('optimized_prompt')
            ->exists();
    }
}
