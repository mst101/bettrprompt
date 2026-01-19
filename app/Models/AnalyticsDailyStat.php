<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnalyticsDailyStat extends Model
{
    protected $table = 'analytics_daily_stats';

    protected $fillable = [
        'date',
        'unique_visitors',
        'total_sessions',
        'total_page_views',
        'avg_session_duration_seconds',
        'bounce_rate',
        'registrations',
        'subscriptions_free',
        'subscriptions_pro',
        'subscriptions_private',
        'total_revenue_usd',
        'prompts_started',
        'prompts_completed',
        'prompt_completion_rate',
        'avg_prompt_rating',
        'by_utm_source',
        'by_country',
        'by_device_type',
    ];

    protected $casts = [
        'date' => 'date',
        'by_utm_source' => 'json',
        'by_country' => 'json',
        'by_device_type' => 'json',
    ];

    /**
     * Scope: filter by date range
     */
    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    /**
     * Scope: filter by minimum unique visitors
     */
    public function scopeHighTraffic($query, int $minVisitors)
    {
        return $query->where('unique_visitors', '>=', $minVisitors);
    }

    /**
     * Scope: filter by minimum conversion rate
     */
    public function scopeHighConversion($query, float $minRate)
    {
        $minCount = 1; // Need at least 1 conversion to calculate rate

        return $query->whereRaw('(registrations::float / NULLIF(unique_visitors, 0)) >= ?', [$minRate])
            ->where('registrations', '>=', $minCount);
    }

    /**
     * Calculate conversion rate: registrations / unique_visitors
     */
    public function getConversionRate(): ?float
    {
        if ($this->unique_visitors === 0) {
            return null;
        }

        return $this->registrations / $this->unique_visitors;
    }

    /**
     * Get aggregated stats for a date range
     */
    public static function aggregateForRange($startDate, $endDate)
    {
        $stats = self::whereBetween('date', [$startDate, $endDate])->get();

        if ($stats->isEmpty()) {
            return null;
        }

        return [
            'total_unique_visitors' => $stats->sum('unique_visitors'),
            'total_sessions' => $stats->sum('total_sessions'),
            'total_page_views' => $stats->sum('total_page_views'),
            'avg_session_duration_seconds' => $stats->avg('avg_session_duration_seconds'),
            'avg_bounce_rate' => $stats->avg('bounce_rate'),
            'total_registrations' => $stats->sum('registrations'),
            'total_subscriptions_free' => $stats->sum('subscriptions_free'),
            'total_subscriptions_pro' => $stats->sum('subscriptions_pro'),
            'total_subscriptions_private' => $stats->sum('subscriptions_private'),
            'total_revenue_usd' => $stats->sum('total_revenue_usd'),
            'total_prompts_started' => $stats->sum('prompts_started'),
            'total_prompts_completed' => $stats->sum('prompts_completed'),
            'avg_prompt_completion_rate' => $stats->avg('prompt_completion_rate'),
            'avg_prompt_rating' => $stats->avg('avg_prompt_rating'),
        ];
    }
}
