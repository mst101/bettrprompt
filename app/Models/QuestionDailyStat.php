<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuestionDailyStat extends Model
{
    protected $table = 'question_daily_stats';

    protected $fillable = [
        'date',
        'question_id',
        'times_shown',
        'times_answered',
        'times_skipped',
        'answer_rate',
        'skip_rate',
        'avg_response_length',
        'avg_time_to_answer_ms',
        'avg_prompt_rating_when_answered',
        'avg_prompt_rating_when_skipped',
        'copy_rate_when_answered',
        'copy_rate_when_skipped',
        'by_personality_variant',
    ];

    protected $casts = [
        'date' => 'date',
        'by_personality_variant' => 'json',
    ];

    /**
     * Scope: filter by question ID
     */
    public function scopeByQuestionId($query, string $questionId)
    {
        return $query->where('question_id', $questionId);
    }

    /**
     * Scope: filter by date range
     */
    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    /**
     * Scope: filter by minimum answer rate
     */
    public function scopeMinimumAnswerRate($query, float $rate)
    {
        return $query->where('answer_rate', '>=', $rate);
    }

    /**
     * Scope: filter by maximum skip rate
     */
    public function scopeMaximumSkipRate($query, float $rate)
    {
        return $query->where('skip_rate', '<=', $rate);
    }

    /**
     * Scope: filter by minimum average rating when answered
     */
    public function scopeMinimumRatingWhenAnswered($query, float $rating)
    {
        return $query->where('avg_prompt_rating_when_answered', '>=', $rating);
    }

    /**
     * Get aggregated stats for a date range
     */
    public static function aggregateForRange($startDate, $endDate, ?string $questionId = null)
    {
        $query = self::whereBetween('date', [$startDate, $endDate]);

        if ($questionId) {
            $query->where('question_id', $questionId);
        }

        return $query->get()->groupBy('question_id')->map(function ($stats) {
            return [
                'times_shown' => $stats->sum('times_shown'),
                'times_answered' => $stats->sum('times_answered'),
                'times_skipped' => $stats->sum('times_skipped'),
                'avg_answer_rate' => $stats->avg('answer_rate'),
                'avg_skip_rate' => $stats->avg('skip_rate'),
                'avg_response_length' => $stats->avg('avg_response_length'),
                'avg_time_to_answer_ms' => $stats->avg('avg_time_to_answer_ms'),
                'avg_rating_when_answered' => $stats->avg('avg_prompt_rating_when_answered'),
                'avg_rating_when_skipped' => $stats->avg('avg_prompt_rating_when_skipped'),
                'avg_copy_rate_when_answered' => $stats->avg('copy_rate_when_answered'),
                'avg_copy_rate_when_skipped' => $stats->avg('copy_rate_when_skipped'),
            ];
        });
    }

    /**
     * Determine question effectiveness based on metrics
     */
    public function isEffective(): bool
    {
        // A question is effective if:
        // - High answer rate (people answer it)
        // - High rating correlation when answered (improves outcomes)
        // - Low skip rate (people don't skip it)
        return ($this->answer_rate ?? 0) >= 0.75
            && ($this->avg_prompt_rating_when_answered ?? 0) >= 3
            && ($this->skip_rate ?? 0) <= 0.25;
    }
}
