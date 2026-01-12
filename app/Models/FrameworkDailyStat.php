<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FrameworkDailyStat extends Model
{
    protected $table = 'framework_daily_stats';

    protected $fillable = [
        'date',
        'framework',
        'times_recommended',
        'times_chosen',
        'times_accepted',
        'acceptance_rate',
        'avg_rating',
        'prompts_copied',
        'prompts_edited',
        'copy_rate',
        'by_personality_type',
        'by_task_category',
    ];

    protected $casts = [
        'date' => 'date',
        'by_personality_type' => 'json',
        'by_task_category' => 'json',
    ];

    /**
     * Scope: filter by framework
     */
    public function scopeByFramework($query, string $framework)
    {
        return $query->where('framework', $framework);
    }

    /**
     * Scope: filter by date range
     */
    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    /**
     * Scope: filter by minimum acceptance rate
     */
    public function scopeMinimumAcceptanceRate($query, float $rate)
    {
        return $query->where('acceptance_rate', '>=', $rate);
    }

    /**
     * Scope: filter by minimum average rating
     */
    public function scopeMinimumRating($query, float $rating)
    {
        return $query->where('avg_rating', '>=', $rating);
    }

    /**
     * Get aggregated stats for a date range
     */
    public static function aggregateForRange($startDate, $endDate, ?string $framework = null)
    {
        $query = self::whereBetween('date', [$startDate, $endDate]);

        if ($framework) {
            $query->where('framework', $framework);
        }

        return $query->get()->groupBy('framework')->map(function ($stats) {
            return [
                'times_recommended' => $stats->sum('times_recommended'),
                'times_chosen' => $stats->sum('times_chosen'),
                'times_accepted' => $stats->sum('times_accepted'),
                'avg_acceptance_rate' => $stats->avg('acceptance_rate'),
                'avg_rating' => $stats->avg('avg_rating'),
                'prompts_copied' => $stats->sum('prompts_copied'),
                'prompts_edited' => $stats->sum('prompts_edited'),
                'avg_copy_rate' => $stats->avg('copy_rate'),
            ];
        });
    }
}
