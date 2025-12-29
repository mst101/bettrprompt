<?php

namespace App\Models;

use Database\Factories\FeedbackFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Feedback extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'experience_level',
        'usefulness',
        'usage_intent',
        'suggestions',
        'desired_features',
        'desired_features_other',
    ];

    protected $casts = [
        'desired_features' => 'array',
        'experience_level' => 'integer',
        'usefulness' => 'integer',
        'usage_intent' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected static function newFactory(): FeedbackFactory|Factory
    {
        return FeedbackFactory::new();
    }
}
