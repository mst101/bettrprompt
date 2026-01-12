<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromptQualityMetric extends Model
{
    protected $fillable = [
        'prompt_run_id',
        'user_rating',
        'was_copied',
        'copy_count',
        'was_edited',
        'edit_percentage',
        'prompt_length',
        'questions_answered',
        'questions_skipped',
        'time_to_complete_ms',
        'task_category',
        'framework_used',
        'personality_type',
        'engagement_score',
        'quality_score',
    ];

    protected $casts = [
        'was_copied' => 'boolean',
        'was_edited' => 'boolean',
    ];

    public function promptRun(): BelongsTo
    {
        return $this->belongsTo(PromptRun::class);
    }

    /**
     * Scope: filter by user rating
     */
    public function scopeByRating($query, int $minRating = 1)
    {
        return $query->where('user_rating', '>=', $minRating);
    }

    /**
     * Scope: prompts that were copied
     */
    public function scopeCopied($query)
    {
        return $query->where('was_copied', true);
    }

    /**
     * Scope: prompts that were edited
     */
    public function scopeEdited($query)
    {
        return $query->where('was_edited', true);
    }

    /**
     * Scope: filter by framework used
     */
    public function scopeByFramework($query, string $framework)
    {
        return $query->where('framework_used', $framework);
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
     * Scope: filter by engagement score range
     */
    public function scopeByEngagementScore($query, float $minScore, float $maxScore = 100.0)
    {
        return $query->whereBetween('engagement_score', [$minScore, $maxScore]);
    }

    /**
     * Scope: filter by quality score range
     */
    public function scopeByQualityScore($query, float $minScore, float $maxScore = 100.0)
    {
        return $query->whereBetween('quality_score', [$minScore, $maxScore]);
    }

    /**
     * Scope: filter by completion time range (in milliseconds)
     */
    public function scopeByCompletionTime($query, int $minMs, int $maxMs)
    {
        return $query->whereBetween('time_to_complete_ms', [$minMs, $maxMs]);
    }

    /**
     * Scope: filter by prompt length range
     */
    public function scopeByLength($query, int $minLength, int $maxLength)
    {
        return $query->whereBetween('prompt_length', [$minLength, $maxLength]);
    }

    /**
     * Scope: high-quality prompts
     */
    public function scopeHighQuality($query, float $threshold = 75.0)
    {
        return $query->where('quality_score', '>=', $threshold);
    }

    /**
     * Scope: highly engaged prompts
     */
    public function scopeHighEngagement($query, float $threshold = 75.0)
    {
        return $query->where('engagement_score', '>=', $threshold);
    }

    /**
     * Calculate copy rate (percentage of prompts copied)
     */
    public static function calculateCopyRate(): float
    {
        $total = self::count();
        if ($total === 0) {
            return 0;
        }

        $copied = self::where('was_copied', true)->count();

        return ($copied / $total) * 100;
    }

    /**
     * Calculate edit rate (percentage of prompts edited)
     */
    public static function calculateEditRate(): float
    {
        $total = self::count();
        if ($total === 0) {
            return 0;
        }

        $edited = self::where('was_edited', true)->count();

        return ($edited / $total) * 100;
    }

    /**
     * Calculate average rating
     */
    public static function calculateAverageRating(): ?float
    {
        return self::avg('user_rating');
    }

    /**
     * Calculate average engagement score
     */
    public static function calculateAverageEngagementScore(): ?float
    {
        return self::avg('engagement_score');
    }

    /**
     * Calculate average quality score
     */
    public static function calculateAverageQualityScore(): ?float
    {
        return self::avg('quality_score');
    }
}
