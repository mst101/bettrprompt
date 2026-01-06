<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InboundEmail extends Model
{
    protected $fillable = [
        'message_id',
        'from',
        'to',
        'subject',
        'body_plain',
        'body_html',
        'stripped_text',
        'stripped_signature',
        'headers',
        'attachments',
        'user_id',
        'received_at',
        'processed_at',
    ];

    protected $casts = [
        'headers' => 'array',
        'attachments' => 'array',
        'received_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
