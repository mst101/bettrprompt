<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExperimentExposure extends Model
{
    use HasFactory;

    protected $fillable = [
        'experiment_id',
        'variant_id',
        'assignment_id',
        'visitor_id',
        'user_id',
        'session_id',
        'page_path',
        'component',
        'occurred_at',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
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
     * Get the assignment
     */
    public function assignment(): BelongsTo
    {
        return $this->belongsTo(ExperimentAssignment::class);
    }

    /**
     * Get the visitor
     */
    public function visitor(): BelongsTo
    {
        return $this->belongsTo(Visitor::class);
    }

    /**
     * Get the user (if authenticated)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope: filter by experiment and visitor
     */
    public function scopeForExperimentAndVisitor($query, int $experimentId, string $visitorId)
    {
        return $query->where('experiment_id', $experimentId)
            ->where('visitor_id', $visitorId);
    }
}
