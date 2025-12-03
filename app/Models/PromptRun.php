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
        'pre_analysis_questions',
        'pre_analysis_answers',
        'pre_analysis_context',
        'pre_analysis_reasoning',
        'pre_analysis_skipped',
        'pre_analysis_api_usage',
        'framework_questions',
        'clarifying_answers',
        'current_question_index',
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
        'pre_analysis_questions' => 'array',
        'pre_analysis_answers' => 'array',
        'pre_analysis_context' => 'array',
        'pre_analysis_skipped' => 'boolean',
        'pre_analysis_api_usage' => 'array',
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

        $currentIndex = $this->current_question_index ?? 0;

        return $this->framework_questions[$currentIndex] ?? null;
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
        $currentIndex = $this->current_question_index ?? 0;

        return $currentIndex >= $totalQuestions;
    }

    /**
     * Get the number of answered questions.
     */
    public function getAnsweredQuestionsCount(): int
    {
        return $this->current_question_index ?? 0;
    }

    /**
     * Get the total number of questions.
     */
    public function getTotalQuestionsCount(): int
    {
        return count($this->framework_questions ?? []);
    }
}
