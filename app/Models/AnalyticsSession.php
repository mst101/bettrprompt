<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'page_count',
        'event_count',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_term',
        'utm_content',
        'referrer',
        'device_type',
        'country_code',
        'is_bounce',
        'converted',
        'conversion_type',
        'prompts_started',
        'prompts_completed',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'is_bounce' => 'boolean',
        'converted' => 'boolean',
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

    public function scopeForVisitor($query, string $visitorId)
    {
        return $query->where('visitor_id', $visitorId);
    }

    public function scopeConverted($query)
    {
        return $query->where('converted', true);
    }
}
