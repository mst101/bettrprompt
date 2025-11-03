<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromptRun extends Model
{
    protected $fillable = [
        'user_id',
        'personality_type',
        'trait_percentages',
        'task_description',
        'optimized_prompt',
        'n8n_request_payload',
        'n8n_response_payload',
        'status',
        'error_message',
        'completed_at',
    ];

    protected $casts = [
        'trait_percentages' => 'array',
        'n8n_request_payload' => 'array',
        'n8n_response_payload' => 'array',
        'completed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
