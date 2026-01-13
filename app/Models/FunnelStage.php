<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FunnelStage extends Model
{
    protected $fillable = [
        'funnel_id',
        'order',
        'name',
        'event_name',
        'event_conditions',
    ];

    protected $casts = [
        'order' => 'integer',
        'event_conditions' => 'array',
    ];

    public function funnel(): BelongsTo
    {
        return $this->belongsTo(Funnel::class);
    }
}
