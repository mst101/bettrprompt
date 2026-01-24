<?php

namespace App\Models;

use App\Enums\WorkflowStage;
use App\Services\DatabaseService;
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
        // Privacy
        'is_encrypted',
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
        // Privacy
        'is_encrypted' => 'boolean',
        // Workflow
        'workflow_stage' => WorkflowStage::class,
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

    public function frameworkSelections(): HasMany
    {
        return $this->hasMany(FrameworkSelection::class);
    }

    public function questionAnalytics(): HasMany
    {
        return $this->hasMany(QuestionAnalytic::class);
    }

    public function workflowAnalytics(): HasMany
    {
        return $this->hasMany(WorkflowAnalytic::class);
    }

    public function promptQualityMetric()
    {
        return $this->hasOne(PromptQualityMetric::class);
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
    public bool $isProcessing {
        get => $this->workflow_stage?->isProcessing() ?? false;
    }

    /**
     * Check if the prompt run is completed successfully
     */
    public bool $isCompleted {
        get => $this->workflow_stage === WorkflowStage::GenerationCompleted;
    }

    /**
     * Check if the prompt run has failed at any stage
     */
    public bool $isFailed {
        get => $this->workflow_stage?->isFailed() ?? false;
    }

    /**
     * Get which workflow failed (0, 1, or 2), or null if not failed
     */
    public function getFailedWorkflow(): ?int
    {
        return $this->workflow_stage?->isFailed()
            ? $this->workflow_stage->getWorkflowNumber()
            : null;
    }

    /**
     * Check if the prompt run can be accessed by the given user ID or visitor ID
     */
    public function canBeAccessedBy(?int $userId, ?string $visitorId): bool
    {
        // Check if authenticated user owns this prompt run
        if ($userId && $this->user_id === $userId) {
            return true;
        }

        // Check if visitor owns this prompt run
        if ($visitorId && $this->visitor_id === $visitorId) {
            return true;
        }

        return false;
    }

    /**
     * Record and normalise a clarifying answer
     * Handles padding answers to match question count and advancing to next question
     */
    public function recordClarifyingAnswer(int $questionIndex, mixed $answer): array
    {
        $questions = $this->framework_questions ?? [];
        $questionCount = count($questions);

        if ($questionCount === 0) {
            return [];
        }

        $index = max(0, min($questionIndex, $questionCount - 1));
        $answers = array_values($this->clarifying_answers ?? []);

        // Pad answers to match question count
        for ($i = 0; $i < $questionCount; $i++) {
            if (! array_key_exists($i, $answers)) {
                $answers[$i] = null;
            }
        }

        $answers[$index] = $answer === null || $answer === '' ? null : $answer;
        $answers = array_values($answers);

        // After answering/skipping a question, move to the next one
        $nextIndex = min($index + 1, $questionCount);

        DatabaseService::retryOnDeadlock(function () use ($answers, $nextIndex) {
            $this->update([
                'clarifying_answers' => $answers,
                'current_question_index' => $nextIndex,
                'workflow_stage' => WorkflowStage::AnalysisCompleted,
            ]);
        });

        return $answers;
    }

    /**
     * Mark a workflow stage as completed with optional data
     */
    public function markWorkflowCompleted(int $workflow, array $data = []): void
    {
        $updateData = array_merge($data, [
            'workflow_stage' => "{$workflow}_completed",
            'error_message' => null,
        ]);

        // Workflow 2 completion should also set the completed_at timestamp
        if ($workflow === 2 && ! isset($updateData['completed_at'])) {
            $updateData['completed_at'] = now();
        }

        DatabaseService::retryOnDeadlock(function () use ($updateData) {
            $this->update($updateData);
        });
    }

    /**
     * Mark a workflow stage as failed with error message
     */
    public function markWorkflowFailed(int $workflow, string $errorMessage): void
    {
        DatabaseService::retryOnDeadlock(function () use ($workflow, $errorMessage) {
            $this->update([
                'workflow_stage' => "{$workflow}_failed",
                'error_message' => $errorMessage,
            ]);
        });
    }

    /**
     * Mark a workflow stage as processing
     */
    public function markWorkflowProcessing(int $workflow, array $data = []): void
    {
        $updateData = array_merge($data, [
            'workflow_stage' => "{$workflow}_processing",
            'error_message' => null,
        ]);

        DatabaseService::retryOnDeadlock(function () use ($updateData) {
            $this->update($updateData);
        });
    }
}
