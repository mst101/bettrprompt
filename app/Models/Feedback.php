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
        'personality_type',
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

    /**
     * Find feedback by user ID.
     */
    public static function findByUser(?int $userId): ?self
    {
        if ($userId === null) {
            return null;
        }

        return static::where('user_id', $userId)->first();
    }

    protected static function newFactory(): FeedbackFactory|Factory
    {
        return FeedbackFactory::new();
    }
}
