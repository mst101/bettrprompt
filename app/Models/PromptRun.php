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
        'workflow_stage',
        'error_message',
        'error_context',
        'retry_count',
        'last_error_at',
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
        'error_context' => 'array',
        'last_error_at' => 'datetime',
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
     * Get user context for workflow optimisation
     * Falls back to visitor context if no authenticated user
     */
    public function getUserContext(): ?array
    {
        // Try authenticated user first
        if ($this->user) {
            return $this->user->getUserContext();
        }

        // Fall back to visitor context (location only)
        if ($this->visitor && $this->visitor->hasLocationData()) {
            return [
                'location' => [
                    'country' => $this->visitor->country_name,
                    'country_code' => $this->visitor->country_code,
                    'region' => $this->visitor->region,
                    'city' => $this->visitor->city,
                    'timezone' => $this->visitor->timezone,
                    'currency' => $this->visitor->currency_code,
                    'language' => $this->visitor->language_code,
                ],
                'professional' => null,
                'team' => null,
                'preferences' => null,
                'personality' => null,
            ];
        }

        return null;
    }

    /**
     * Check if the prompt run is actively processing (background job running)
     */
    public function isProcessing(): bool
    {
        return in_array($this->workflow_stage, [
            '0_processing',
            '1_processing',
            '2_processing',
        ]);
    }

    /**
     * Check if the prompt run is completed successfully
     */
    public function isCompleted(): bool
    {
        return $this->workflow_stage === '2_completed';
    }

    /**
     * Check if the prompt run has failed at any stage
     */
    public function isFailed(): bool
    {
        return in_array($this->workflow_stage, [
            '0_failed',
            '1_failed',
            '2_failed',
        ]);
    }

    /**
     * Get which workflow failed (0, 1, or 2), or null if not failed
     */
    public function getFailedWorkflow(): ?int
    {
        return match ($this->workflow_stage) {
            '0_failed' => 0,
            '1_failed' => 1,
            '2_failed' => 2,
            default => null,
        };
    }
}
