<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Experiment extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'name',
        'description',
        'hypothesis',
        'status',
        'started_at',
        'ended_at',
        'minimum_runtime_hours',
        'goal_event',
        'goal_type',
        'targeting_rules',
        'traffic_percentage',
        'minimum_sample_size',
        'minimum_detectable_effect',
        'winner_variant_id',
        'winner_declared_at',
        'is_personality_research',
        'personality_hypothesis',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'winner_declared_at' => 'datetime',
        'targeting_rules' => 'json',
        'is_personality_research' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get all variants for this experiment
     */
    public function variants(): HasMany
    {
        return $this->hasMany(ExperimentVariant::class);
    }

    /**
     * Get all assignments for this experiment
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(ExperimentAssignment::class);
    }

    /**
     * Get all exposures for this experiment
     */
    public function exposures(): HasMany
    {
        return $this->hasMany(ExperimentExposure::class);
    }

    /**
     * Get conversion stats for this experiment
     */
    public function conversions(): HasMany
    {
        return $this->hasMany(ExperimentConversion::class);
    }

    /**
     * Scope: only active/running experiments
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'running')
            ->where('started_at', '<=', now())
            ->where(function ($q) {
                $q->whereNull('ended_at')
                    ->orWhere('ended_at', '>=', now());
            });
    }

    /**
     * Scope: only draft experiments
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    /**
     * Scope: only completed experiments
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Check if experiment is currently running
     */
    public bool $isRunning {
        get => $this->status === 'running' &&
               $this->started_at?->isPast() &&
               (! $this->ended_at || $this->ended_at->isFuture());
    }

    /**
     * Get the control variant
     */
    public function getControlVariant(): ?ExperimentVariant
    {
        return $this->variants()->where('is_control', true)->first();
    }
}
