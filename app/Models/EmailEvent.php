<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailEvent extends Model
{
    protected $fillable = [
        'event_type',
        'message_id',
        'recipient',
        'user_id',
        'payload',
        'event_timestamp',
        'processed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'event_timestamp' => 'datetime',
        'processed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
