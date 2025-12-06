<?php

namespace Tests\Builders;

use App\Models\PromptRun;
use App\Models\User;

/**
 * Builder for creating complex PromptRun test fixtures
 *
 * Usage:
 *   $promptRun = PromptRunBuilder::new()
 *       ->withUser($user)
 *       ->answeringQuestions()
 *       ->withAnswers(['Goal 1', 'Measure 1'])
 *       ->build();
 */
class PromptRunBuilder
{
    private ?User $user = null;

    private array $attributes = [];

    public static function new(): self
    {
        return new self;
    }

    public function withUser(?User $user = null): self
    {
        $this->user = $user ?? User::factory()->create();

        return $this;
    }

    public function withTask(string $description, ?string $personalityType = null): self
    {
        $this->attributes['task_description'] = $description;
        if ($personalityType) {
            $this->attributes['personality_type'] = $personalityType;
        }

        return $this;
    }

    public function withFramework(array $framework): self
    {
        $this->attributes['selected_framework'] = $framework;

        return $this;
    }

    public function withQuestions(array $questions): self
    {
        $this->attributes['framework_questions'] = $questions;
        $this->attributes['current_question_index'] = 0;

        return $this;
    }

    public function withAnswers(array $answers): self
    {
        $this->attributes['clarifying_answers'] = $answers;
        $this->attributes['current_question_index'] = count($answers);

        return $this;
    }

    public function submitted(): self
    {
        return $this
            ->pending()
            ->workflow('submitted');
    }

    public function processing(): self
    {
        $this->attributes['status'] = 'processing';

        return $this;
    }

    public function pending(): self
    {
        $this->attributes['status'] = 'pending';

        return $this;
    }

    public function completed(): self
    {
        $this->attributes['status'] = 'completed';
        $this->attributes['workflow_stage'] = 'completed';
        $this->attributes['completed_at'] = now();
        if (! isset($this->attributes['optimized_prompt'])) {
            $this->attributes['optimized_prompt'] = 'Here is your optimised prompt...';
        }

        return $this;
    }

    public function failed(string $error = 'An error occurred'): self
    {
        $this->attributes['status'] = 'failed';
        $this->attributes['workflow_stage'] = 'failed';
        $this->attributes['error_message'] = $error;

        return $this;
    }

    public function workflow(string $stage): self
    {
        $this->attributes['workflow_stage'] = $stage;

        return $this;
    }

    public function analysisComplete(): self
    {
        return $this
            ->pending()
            ->workflow('analysis_complete')
            ->withFramework([
                'code' => 'SMART',
                'name' => 'SMART Goals',
                'components' => ['Specific', 'Measurable', 'Achievable', 'Relevant', 'Time-bound'],
                'rationale' => 'Ideal for structured planning',
            ])
            ->withQuestions([
                'What is your specific goal?',
                'How will you measure success?',
            ]);
    }

    public function answeringQuestions(?array $questions = null): self
    {
        return $this
            ->processing()
            ->workflow('answering_questions')
            ->withQuestions($questions ?? [
                'What is your specific goal?',
                'How will you measure success?',
            ]);
    }

    public function generatingPrompt(): self
    {
        return $this
            ->processing()
            ->workflow('generating_prompt');
    }

    public function withOptimisedPrompt(string $prompt): self
    {
        $this->attributes['optimized_prompt'] = $prompt;

        return $this;
    }

    public function withTraitPercentages(array $traits): self
    {
        $this->attributes['trait_percentages'] = $traits;

        return $this;
    }

    public function asChildOf(PromptRun $parentRun): self
    {
        $this->attributes['parent_id'] = $parentRun->id;

        return $this;
    }

    public function build(): PromptRun
    {
        if (! $this->user) {
            $this->withUser();
        }

        return PromptRun::factory()->create(
            array_merge(['user_id' => $this->user->id], $this->attributes)
        );
    }
}
