<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
        'priority',
        'task_category_code',
        'framework_code',
        'is_universal',
        'is_conditional',
        'condition_text',
        'display_order',
        'is_active',
    ];

    protected $casts = [
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
     * Get the task category for this question.
     */
    public function taskCategory(): BelongsTo
    {
        return $this->belongsTo(TaskCategory::class, 'task_category_code', 'code');
    }

    /**
     * Get the framework for this question.
     */
    public function framework(): BelongsTo
    {
        return $this->belongsTo(Framework::class, 'framework_code', 'code');
    }

    /**
     * Get the cognitive requirements for this question.
     */
    public function cognitiveRequirements(): BelongsToMany
    {
        return $this->belongsToMany(
            CognitiveRequirement::class,
            'question_cognitive_requirements',
            'question_id',
            'cognitive_requirement_code'
        )->withPivot('requirement_level')->withTimestamps();
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
    public function scopeByCategory($query, string $categoryCode)
    {
        return $query->where('task_category_code', $categoryCode);
    }

    /**
     * Scope: Filter by framework.
     */
    public function scopeByFramework($query, string $frameworkCode)
    {
        return $query->where('framework_code', $frameworkCode);
    }

    /**
     * Scope: Filter universal questions.
     */
    public function scopeUniversal($query)
    {
        return $query->whereNull('task_category_code')->whereNull('framework_code');
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
