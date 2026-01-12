<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AnalyticsEvent extends Model
{
    use HasFactory;

    protected $primaryKey = 'event_id';

    protected $keyType = 'string';

    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'event_id',
        'name',
        'type',
        'properties',
        'visitor_id',
        'user_id',
        'session_id',
        'source',
        'page_path',
        'referrer',
        'device_type',
        'browser',
        'os',
        'country_code',
        'prompt_run_id',
        'occurred_at',
        'received_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'properties' => 'json',
        'occurred_at' => 'datetime',
        'received_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the visitor that owns this event.
     */
    public function visitor(): BelongsTo
    {
        return $this->belongsTo(Visitor::class);
    }

    /**
     * Get the user that owns this event.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the prompt run that owns this event.
     */
    public function promptRun(): BelongsTo
    {
        return $this->belongsTo(PromptRun::class);
    }

    /**
     * Get experiment attributions for this event
     */
    public function eventExperiments(): HasMany
    {
        return $this->hasMany(AnalyticsEventExperiment::class, 'event_id', 'event_id');
    }

    /**
     * Scope: filter events by type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope: filter conversion events
     */
    public function scopeConversions($query)
    {
        return $query->where('type', 'conversion');
    }

    /**
     * Scope: filter engagement events
     */
    public function scopeEngagement($query)
    {
        return $query->where('type', 'engagement');
    }

    /**
     * Scope: filter exposure events
     */
    public function scopeExposures($query)
    {
        return $query->where('type', 'exposure');
    }

    /**
     * Scope: filter events for a visitor
     */
    public function scopeForVisitor($query, string $visitorId)
    {
        return $query->where('visitor_id', $visitorId);
    }

    /**
     * Scope: filter events for a session
     */
    public function scopeForSession($query, string $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    /**
     * Scope: filter events in a time range
     */
    public function scopeInTimeRange($query, $startTime, $endTime)
    {
        return $query->whereBetween('occurred_at', [$startTime, $endTime]);
    }
}
