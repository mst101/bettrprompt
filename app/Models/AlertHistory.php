<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AlertHistory extends Model
{
    protected $table = 'alert_history';

    protected $fillable = [
        'alert_rule_id',
        'triggered_count',
        'error_code',
        'error_message',
        'last_triggered_at',
        'acknowledged_at',
        'acknowledged_by',
    ];

    protected $casts = [
        'triggered_count' => 'integer',
        'last_triggered_at' => 'datetime',
        'acknowledged_at' => 'datetime',
    ];

    public function rule(): BelongsTo
    {
        return $this->belongsTo(AlertRule::class, 'alert_rule_id');
    }

    public function acknowledgedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(AlertNotification::class);
    }
}
