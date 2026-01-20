<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FunnelDailyStat extends Model
{
    protected $table = 'funnel_daily_stats';

    protected $fillable = [
        'funnel_id',
        'date',
        'stage',
        'starts',
        'conversions',
        'conversion_rate',
    ];

    protected $casts = [
        'date' => 'date',
        'stage' => 'integer',
        'starts' => 'integer',
        'conversions' => 'integer',
        'conversion_rate' => 'decimal:2',
    ];

    public function funnel(): BelongsTo
    {
        return $this->belongsTo(Funnel::class);
    }

    // Scopes
    public function scopeInDateRange($query, $start, $end)
    {
        return $query->whereBetween('date', [$start, $end]);
    }

    public function scopeForFunnel($query, $funnelId)
    {
        return $query->where('funnel_id', $funnelId);
    }

    public function scopeForStage($query, $stage)
    {
        return $query->where('stage', $stage);
    }

    // Helper: Get conversion rate as percentage
    public function getConversionRatePercentage(): float
    {
        return $this->conversion_rate ?? 0.0;
    }
}
