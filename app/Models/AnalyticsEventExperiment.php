<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnalyticsEventExperiment extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'experiment_id',
        'variant_id',
        'exposure_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the event
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(AnalyticsEvent::class, 'event_id', 'event_id');
    }

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
     * Get the exposure
     */
    public function exposure(): BelongsTo
    {
        return $this->belongsTo(ExperimentExposure::class);
    }
}
