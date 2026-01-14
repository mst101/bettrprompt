<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'question_id',
        'personality_pattern',
        'phrasing',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the question this variant belongs to.
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class, 'question_id', 'id');
    }

    /**
     * Scope: Filter active variants only.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
