<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExperimentAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'experiment_id',
        'variant_id',
        'visitor_id',
        'user_id',
        'assigned_at',
        'segment_snapshot',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'segment_snapshot' => 'json',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the experiment this assignment belongs to
     */
    public function experiment(): BelongsTo
    {
        return $this->belongsTo(Experiment::class);
    }

    /**
     * Get the assigned variant
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ExperimentVariant::class);
    }

    /**
     * Get the visitor this assignment is for
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
     * Get exposures for this assignment
     */
    public function exposures(): HasMany
    {
        return $this->hasMany(ExperimentExposure::class);
    }

    /**
     * Scope: filter by visitor
     */
    public function scopeForVisitor($query, string $visitorId)
    {
        return $query->where('visitor_id', $visitorId);
    }
}
