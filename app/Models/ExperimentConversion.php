<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExperimentConversion extends Model
{
    use HasFactory;

    protected $fillable = [
        'experiment_id',
        'variant_id',
        'exposures',
        'conversions',
        'unique_visitors_exposed',
        'unique_visitors_converted',
        'total_revenue',
        'conversion_rate',
        'revenue_per_visitor',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the experiment
     */
    public function experiment(): BelongsTo
    {
        return $this->belongsTo(Experiment::class);
    }

    /**
     * Get the variant
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ExperimentVariant::class);
    }

    /**
     * Calculate conversion rate
     */
    public function calculateConversionRate(): float
    {
        if ($this->exposures === 0) {
            return 0;
        }

        return round($this->conversions / $this->exposures, 6);
    }

    /**
     * Calculate revenue per visitor
     */
    public function calculateRevenuePerVisitor(): float
    {
        if ($this->unique_visitors_exposed === 0) {
            return 0;
        }

        return round($this->total_revenue / $this->unique_visitors_exposed, 4);
    }
}
