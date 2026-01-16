<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FrameworkAnalytic extends Model
{
    protected $table = 'framework_analytics';

    protected $fillable = [
        'prompt_run_id',
        'visitor_id',
        'user_id',
        'recommended_framework',
        'chosen_framework',
        'accepted_recommendation',
        'task_category',
        'personality_type',
        'recommendation_scores',
        'prompt_rating',
        'rating_explanation',
        'prompt_copied',
        'prompt_edited',
        'edit_percentage',
        'selected_at',
    ];

    protected $casts = [
        'recommendation_scores' => 'json',
        'accepted_recommendation' => 'boolean',
        'prompt_copied' => 'boolean',
        'prompt_edited' => 'boolean',
        'selected_at' => 'datetime',
    ];

    public function promptRun(): BelongsTo
    {
        return $this->belongsTo(PromptRun::class);
    }

    public function visitor(): BelongsTo
    {
        return $this->belongsTo(Visitor::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function framework(): BelongsTo
    {
        return $this->belongsTo(Framework::class, 'chosen_framework', 'code');
    }

    public function recommendedFrameworkModel(): BelongsTo
    {
        return $this->belongsTo(Framework::class, 'recommended_framework', 'code');
    }

    public function taskCategoryModel(): BelongsTo
    {
        return $this->belongsTo(TaskCategory::class, 'task_category', 'code');
    }

    /**
     * Scope: filter by recommended framework
     */
    public function scopeByRecommendedFramework($query, string $framework)
    {
        return $query->where('recommended_framework', $framework);
    }

    /**
     * Scope: filter by chosen framework
     */
    public function scopeByChosenFramework($query, string $framework)
    {
        return $query->where('chosen_framework', $framework);
    }

    /**
     * Scope: filter by acceptance of recommendation
     */
    public function scopeAcceptedRecommendation($query, bool $accepted = true)
    {
        return $query->where('accepted_recommendation', $accepted);
    }

    /**
     * Scope: filter by personality type
     */
    public function scopeByPersonalityType($query, string $personality)
    {
        return $query->where('personality_type', $personality);
    }

    /**
     * Scope: filter by task category
     */
    public function scopeByTaskCategory($query, string $category)
    {
        return $query->where('task_category', $category);
    }

    /**
     * Scope: filter by prompt rating
     */
    public function scopeWithRating($query, int $minRating = 1)
    {
        return $query->whereNotNull('prompt_rating')->where('prompt_rating', '>=', $minRating);
    }

    /**
     * Scope: filter by time range
     */
    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('selected_at', [$startDate, $endDate]);
    }
}
