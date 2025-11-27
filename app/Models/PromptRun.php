<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PromptRun extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'visitor_id',
        'parent_id',
        'personality_type',
        'trait_percentages',
        'task_description',
        'framework_questions',
        'clarifying_answers',
        'optimized_prompt',
        'status',
        'workflow_stage',
        'error_message',
        'completed_at',
        // Prompt Builder specific fields
        'task_classification',
        'cognitive_requirements',
        'selected_framework',
        'alternative_frameworks',
        'personality_tier',
        'task_trait_alignment',
        'personality_adjustments_preview',
        'question_rationale',
        'framework_used',
        'personality_adjustments_summary',
        'model_recommendations',
        'iteration_suggestions',
        'analysis_api_usage',
        'generation_api_usage',
    ];

    protected $casts = [
        'trait_percentages' => 'array',
        'framework_questions' => 'array',
        'clarifying_answers' => 'array',
        'completed_at' => 'datetime',
        // Prompt Builder specific casts
        'task_classification' => 'array',
        'cognitive_requirements' => 'array',
        'selected_framework' => 'array',
        'alternative_frameworks' => 'array',
        'task_trait_alignment' => 'array',
        'personality_adjustments_preview' => 'array',
        'framework_used' => 'array',
        'personality_adjustments_summary' => 'array',
        'model_recommendations' => 'array',
        'iteration_suggestions' => 'array',
        'analysis_api_usage' => 'array',
        'generation_api_usage' => 'array',
    ];

    public function visitor(): BelongsTo
    {
        return $this->belongsTo(Visitor::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(PromptRun::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(PromptRun::class, 'parent_id');
    }

    /**
     * Get the current unanswered question.
     */
    public function getCurrentQuestion(): ?string
    {
        if (! $this->framework_questions || ! is_array($this->framework_questions)) {
            return null;
        }

        $answers = $this->clarifying_answers ?? [];
        $answeredCount = count($answers);

        return $this->framework_questions[$answeredCount] ?? null;
    }

    /**
     * Check if all questions have been answered.
     */
    public function hasAnsweredAllQuestions(): bool
    {
        if (! $this->framework_questions) {
            return false;
        }

        $totalQuestions = count($this->framework_questions);
        $answeredQuestions = count($this->clarifying_answers ?? []);

        return $answeredQuestions >= $totalQuestions;
    }

    /**
     * Get the number of answered questions.
     */
    public function getAnsweredQuestionsCount(): int
    {
        return count($this->clarifying_answers ?? []);
    }

    /**
     * Get the total number of questions.
     */
    public function getTotalQuestionsCount(): int
    {
        return count($this->framework_questions ?? []);
    }
}
