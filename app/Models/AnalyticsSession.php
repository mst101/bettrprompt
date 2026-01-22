<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AnalyticsSession extends Model
{
    use HasFactory;

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'visitor_id',
        'user_id',
        'started_at',
        'ended_at',
        'duration_seconds',
        'entry_page',
        'exit_page',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_term',
        'utm_content',
        'referrer',
        'device_type',
        'country_code',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function visitor(): BelongsTo
    {
        return $this->belongsTo(Visitor::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(AnalyticsEvent::class, 'session_id', 'id');
    }

    public function scopeForVisitor($query, string $visitorId)
    {
        return $query->where('visitor_id', $visitorId);
    }

    /**
     * Get whether this session bounced (had ≤1 page view)
     */
    public function isBounce(): bool
    {
        if ($this->relationLoaded('events')) {
            return $this->events->where('name', 'page_view')->count() <= 1;
        }

        return AnalyticsEvent::where('session_id', $this->id)
            ->where('name', 'page_view')
            ->count() <= 1;
    }

    /**
     * Get whether this session had a conversion
     */
    public function isConverted(): bool
    {
        if ($this->relationLoaded('events')) {
            return $this->events->where('type', 'conversion')->isNotEmpty();
        }

        return AnalyticsEvent::where('session_id', $this->id)
            ->where('type', 'conversion')
            ->exists();
    }

    /**
     * Get the conversion type from conversion events
     */
    public function getConversionType(): ?string
    {
        $conversions = $this->relationLoaded('events')
            ? $this->events->where('type', 'conversion')
            : AnalyticsEvent::where('session_id', $this->id)
                ->where('type', 'conversion')
                ->get();

        if ($conversions->isEmpty()) {
            return null;
        }

        // Priority: subscription > registration > other
        foreach ($conversions as $event) {
            if (str_contains($event['name'], 'subscription')) {
                return 'subscribed_'.($event['properties']['tier'] ?? 'unknown');
            }
        }

        foreach ($conversions as $event) {
            if (str_contains($event['name'], 'registration')) {
                return 'registered';
            }
        }

        return $conversions->first()?->name ?? null;
    }

    /**
     * Get count of prompts started in this session
     */
    public function getPromptsStarted(): int
    {
        if ($this->relationLoaded('events')) {
            return $this->events->where('name', 'prompt_started')->count();
        }

        return AnalyticsEvent::where('session_id', $this->id)
            ->where('name', 'prompt_started')
            ->count();
    }

    /**
     * Get count of prompts completed in this session
     */
    public function getPromptsCompleted(): int
    {
        if ($this->relationLoaded('events')) {
            return $this->events->where('name', 'prompt_completed')->count();
        }

        return AnalyticsEvent::where('session_id', $this->id)
            ->where('name', 'prompt_completed')
            ->count();
    }
}
