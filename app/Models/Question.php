<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    use HasFactory;

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'question_text',
        'purpose',
        'cognitive_requirements',
        'priority',
        'category',
        'framework',
        'is_universal',
        'is_conditional',
        'condition_text',
        'display_order',
        'is_active',
    ];

    protected $casts = [
        'cognitive_requirements' => 'array',
        'is_universal' => 'boolean',
        'is_conditional' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get the variants for this question.
     */
    public function variants(): HasMany
    {
        return $this->hasMany(QuestionVariant::class, 'question_id', 'id');
    }

    /**
     * Scope: Filter active questions only.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Filter by category.
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope: Filter by framework.
     */
    public function scopeByFramework($query, string $framework)
    {
        return $query->where('framework', $framework);
    }

    /**
     * Scope: Filter universal questions.
     */
    public function scopeUniversal($query)
    {
        return $query->where('is_universal', true);
    }

    /**
     * Scope: Filter conditional questions.
     */
    public function scopeConditional($query)
    {
        return $query->where('is_conditional', true);
    }

    /**
     * Get the variant for a specific personality pattern.
     * Falls back to 'neutral' if pattern not found or pattern is null.
     */
    public function getVariantForPersonality(?string $personalityPattern): ?QuestionVariant
    {
        if (! $personalityPattern) {
            return $this->variants()->where('personality_pattern', 'neutral')->first();
        }

        return $this->variants()->where('personality_pattern', $personalityPattern)->first();
    }
}
