<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AlertRule extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'alert_type',
        'conditions',
        'email_enabled',
        'email_recipients',
        'in_app_enabled',
        'debounce_minutes',
        'is_active',
    ];

    protected $casts = [
        'conditions' => 'array',
        'email_enabled' => 'boolean',
        'in_app_enabled' => 'boolean',
        'is_active' => 'boolean',
        'debounce_minutes' => 'integer',
    ];

    public function history(): HasMany
    {
        return $this->hasMany(AlertHistory::class);
    }
}
