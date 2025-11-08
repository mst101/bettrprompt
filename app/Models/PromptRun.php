<?php

namespace App\Models;

use App\Casts\N8nResponsePayload;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromptRun extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'personality_type',
        'trait_percentages',
        'task_description',
        'selected_framework',
        'framework_reasoning',
        'framework_questions',
        'clarifying_answers',
        'optimized_prompt',
        'n8n_request_payload',
        'n8n_response_payload',
        'status',
        'workflow_stage',
        'error_message',
        'completed_at',
    ];

    protected $casts = [
        'trait_percentages' => 'array',
        'framework_questions' => 'array',
        'clarifying_answers' => 'array',
        'n8n_request_payload' => 'array',
        'n8n_response_payload' => N8nResponsePayload::class,
        'completed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
