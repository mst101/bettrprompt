<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Funnel extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'description',
        'is_active',
        'attribution_window_days',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'attribution_window_days' => 'integer',
    ];

    public function stages(): HasMany
    {
        return $this->hasMany(FunnelStage::class)->orderBy('order');
    }

    public function progress(): HasMany
    {
        return $this->hasMany(FunnelProgress::class);
    }

    public function dailyStats(): HasMany
    {
        return $this->hasMany(FunnelDailyStats::class);
    }
}
