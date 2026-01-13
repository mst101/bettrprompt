<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlertNotification extends Model
{
    protected $fillable = [
        'alert_history_id',
        'user_id',
        'type',
        'status',
        'sent_at',
        'error_message',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function alertHistory(): BelongsTo
    {
        return $this->belongsTo(AlertHistory::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
