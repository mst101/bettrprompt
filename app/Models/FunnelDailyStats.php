<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FunnelDailyStats extends Model
{
    protected $fillable = [
        'funnel_id',
        'date',
        'stage',
        'starts',
        'conversions',
        'conversion_rate',
    ];

    protected $casts = [
        'date' => 'date',
        'stage' => 'integer',
        'starts' => 'integer',
        'conversions' => 'integer',
        'conversion_rate' => 'decimal:2',
    ];

    public function funnel(): BelongsTo
    {
        return $this->belongsTo(Funnel::class);
    }
}
