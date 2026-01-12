<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExperimentVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'experiment_id',
        'slug',
        'name',
        'description',
        'is_control',
        'weight',
        'config',
    ];

    protected $casts = [
        'is_control' => 'boolean',
        'config' => 'json',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the experiment this variant belongs to
     */
    public function experiment(): BelongsTo
    {
        return $this->belongsTo(Experiment::class);
    }

    /**
     * Get all assignments to this variant
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(ExperimentAssignment::class);
    }

    /**
     * Get all exposures of this variant
     */
    public function exposures(): HasMany
    {
        return $this->hasMany(ExperimentExposure::class);
    }

    /**
     * Get conversion stats for this variant
     */
    public function conversions(): HasMany
    {
        return $this->hasMany(ExperimentConversion::class);
    }

    /**
     * Scope: only control variants
     */
    public function scopeControl($query)
    {
        return $query->where('is_control', true);
    }

    /**
     * Scope: only treatment variants
     */
    public function scopeTreatment($query)
    {
        return $query->where('is_control', false);
    }
}
