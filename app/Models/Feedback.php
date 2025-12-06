<?php

namespace App\Models;

use Database\Factories\FeedbackFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected static function newFactory()
    {
        return FeedbackFactory::new();
    }
}
