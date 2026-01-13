<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FunnelProgress extends Model
{
    protected $fillable = [
        'funnel_id',
        'visitor_id',
        'stage',
        'stage_timestamps',
        'conversion_date',
        'is_converted',
    ];

    protected $casts = [
        'stage' => 'integer',
        'stage_timestamps' => 'array',
        'conversion_date' => 'datetime',
        'is_converted' => 'boolean',
    ];

    public function funnel(): BelongsTo
    {
        return $this->belongsTo(Funnel::class);
    }
}
