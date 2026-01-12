<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionAnalytic extends Model
{
    protected $fillable = [
        'prompt_run_id',
        'visitor_id',
        'user_id',
        'question_id',
        'question_category',
        'personality_variant',
        'display_order',
        'was_required',
        'response_status',
        'response_length',
        'time_to_answer_ms',
        'prompt_rating',
        'prompt_copied',
        'presented_at',
    ];

    protected $casts = [
        'was_required' => 'boolean',
        'prompt_copied' => 'boolean',
        'presented_at' => 'datetime',
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

    /**
     * Scope: filter by question ID (U1, D1, S1, etc.)
     */
    public function scopeByQuestionId($query, string $questionId)
    {
        return $query->where('question_id', $questionId);
    }

    /**
     * Scope: filter by question category
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('question_category', $category);
    }

    /**
     * Scope: filter by response status
     */
    public function scopeByResponseStatus($query, string $status)
    {
        return $query->where('response_status', $status);
    }

    /**
     * Scope: answered questions only
     */
    public function scopeAnswered($query)
    {
        return $query->where('response_status', 'answered');
    }

    /**
     * Scope: skipped questions only
     */
    public function scopeSkipped($query)
    {
        return $query->where('response_status', 'skipped');
    }

    /**
     * Scope: not shown questions only
     */
    public function scopeNotShown($query)
    {
        return $query->where('response_status', 'not_shown');
    }

    /**
     * Scope: filter by personality variant
     */
    public function scopeByPersonalityVariant($query, string $variant)
    {
        return $query->where('personality_variant', $variant);
    }

    /**
     * Scope: filter by required status
     */
    public function scopeRequired($query, bool $required = true)
    {
        return $query->where('was_required', $required);
    }

    /**
     * Scope: filter by time to answer range (in milliseconds)
     */
    public function scopeByResponseTime($query, int $minMs, int $maxMs)
    {
        return $query->whereBetween('time_to_answer_ms', [$minMs, $maxMs]);
    }

    /**
     * Scope: filter by time range
     */
    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('presented_at', [$startDate, $endDate]);
    }

    /**
     * Scope: questions with prompt ratings
     */
    public function scopeWithRating($query, int $minRating = 1)
    {
        return $query->whereNotNull('prompt_rating')->where('prompt_rating', '>=', $minRating);
    }
}
