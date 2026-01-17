<?php

declare(strict_types=1);

use App\Models\PromptRun;

describe('Domain Analytics Integration', function () {
    describe('Workflow Analytics Recording', function () {
        it('creates workflow analytics record when workflow starts', function () {
            $promptRun = PromptRun::factory()->create();

            // Simulate calling recordStart
            $service = app(\App\Services\WorkflowAnalyticsService::class);
            $analytic = $service->recordStart($promptRun, 0);

            expect($analytic)->not->toBeNull();
            expect($analytic->prompt_run_id)->toBe($promptRun->id);
            expect($analytic->workflow_stage)->toBe(0);
            expect($analytic->status)->toBe('processing');
            expect($analytic->started_at)->not->toBeNull();
        });

        it('records workflow completion with token counts', function () {
            $promptRun = PromptRun::factory()->create();
            $service = app(\App\Services\WorkflowAnalyticsService::class);

            // Start workflow
            $analytic = $service->recordStart($promptRun, 1);

            // Complete workflow
            $completed = $service->recordCompletion(
                analytic: $analytic,
                inputTokens: 1000,
                outputTokens: 500,
                estimatedCostUsd: 0.05,
                modelUsed: 'gpt-4',
            );

            expect($completed->status)->toBe('completed');
            expect($completed->input_tokens)->toBe(1000);
            expect($completed->output_tokens)->toBe(500);
            expect((float) $completed->cost_usd)->toEqual(0.05);
            expect($completed->model_used)->toBe('gpt-4');
            expect($completed->duration_ms)->toBeGreaterThan(0);
            expect($completed->completed_at)->not->toBeNull();
        });

        it('records workflow failure with error details', function () {
            $promptRun = PromptRun::factory()->create();
            $service = app(\App\Services\WorkflowAnalyticsService::class);

            // Start workflow
            $analytic = $service->recordStart($promptRun, 2);

            // Record failure
            $failed = $service->recordFailure(
                analytic: $analytic,
                errorCode: 'RATE_LIMIT',
                errorMessage: 'API rate limit exceeded',
            );

            expect($failed->status)->toBe('failed');
            expect($failed->error_code)->toBe('RATE_LIMIT');
            expect($failed->error_message)->toBe('API rate limit exceeded');
            expect($failed->completed_at)->not->toBeNull();
        });
    });

    describe('Framework Selection Analytics', function () {
        it('records framework selection', function () {
            $visitor = \App\Models\Visitor::factory()->create();
            $promptRun = PromptRun::factory()->create([
                'visitor_id' => $visitor->id,
            ]);

            $service = app(\App\Services\FrameworkSelectionService::class);
            $selection = $service->recordSelection(
                promptRun: $promptRun,
                visitorId: $visitor->id,
                userId: null,
                recommendedFramework: 'CO_STAR',
                chosenFramework: 'CO_STAR',
                recommendationScores: [
                    'CO_STAR' => 0.95,
                    'REACT' => 0.80,
                ],
                taskCategory: 'CONTENT_CREATION',
                personalityType: 'INTJ',
            );

            expect($selection)->not->toBeNull();
            expect($selection->recommended_framework)->toBe('CO_STAR');
            expect($selection->chosen_framework)->toBe('CO_STAR');
            expect($selection->accepted_recommendation)->toBeTrue();
            expect($selection->task_category)->toBe('CONTENT_CREATION');
            expect($selection->personality_type)->toBe('INTJ');
        });

        it('tracks when user chooses different framework', function () {
            $visitor = \App\Models\Visitor::factory()->create();
            $promptRun = PromptRun::factory()->create([
                'visitor_id' => $visitor->id,
            ]);

            $service = app(\App\Services\FrameworkSelectionService::class);
            $selection = $service->recordSelection(
                promptRun: $promptRun,
                visitorId: $visitor->id,
                userId: null,
                recommendedFramework: 'CO_STAR',
                chosenFramework: 'CO_STAR',
                recommendationScores: [],
                taskCategory: null,
                personalityType: null,
            );

            // Simulate user changing their choice
            $updated = $service->updateChosenFramework($selection, 'REACT');

            expect($updated->recommended_framework)->toBe('CO_STAR');
            expect($updated->chosen_framework)->toBe('REACT');
            expect($updated->accepted_recommendation)->toBeFalse();
        });
    });

    describe('Question Analytics Recording', function () {
        it('records question presentation', function () {
            $visitor = \App\Models\Visitor::factory()->create();
            $promptRun = PromptRun::factory()->create([
                'visitor_id' => $visitor->id,
            ]);

            $service = app(\App\Services\QuestionAnalyticsService::class);
            $analytic = $service->recordPresentation(
                promptRun: $promptRun,
                visitorId: $visitor->id,
                userId: null,
                questionId: 'U1',
                questionCategory: 'universal',
                personalityVariant: 'neutral',
                displayOrder: 1,
                wasRequired: true,
            );

            expect($analytic)->not->toBeNull();
            expect($analytic->question_id)->toBe('U1');
            expect($analytic->response_status)->toBe('not_shown');
            expect($analytic->display_order)->toBe(1);
            expect($analytic->was_required)->toBeTrue();
            expect($analytic->presented_at)->not->toBeNull();
        });

        it('records question response', function () {
            $visitor = \App\Models\Visitor::factory()->create();
            $promptRun = PromptRun::factory()->create([
                'visitor_id' => $visitor->id,
            ]);

            $service = app(\App\Services\QuestionAnalyticsService::class);
            $analytic = $service->recordPresentation(
                promptRun: $promptRun,
                visitorId: $visitor->id,
                userId: null,
                questionId: 'D1',
                questionCategory: 'decision',
                personalityVariant: null,
                displayOrder: 1,
                wasRequired: true,
            );

            // Record response
            $answered = $service->recordResponse(
                analytic: $analytic,
                responseLength: 150,
                timeToAnswerMs: 5000,
            );

            expect($answered->response_status)->toBe('answered');
            expect($answered->response_length)->toBe(150);
            expect($answered->time_to_answer_ms)->toBe(5000);
        });

        it('records question skip', function () {
            $visitor = \App\Models\Visitor::factory()->create();
            $promptRun = PromptRun::factory()->create([
                'visitor_id' => $visitor->id,
            ]);

            $service = app(\App\Services\QuestionAnalyticsService::class);
            $analytic = $service->recordPresentation(
                promptRun: $promptRun,
                visitorId: $visitor->id,
                userId: null,
                questionId: 'S1',
                questionCategory: 'strategy',
                personalityVariant: null,
                displayOrder: 2,
                wasRequired: false,
            );

            // Record skip
            $skipped = $service->recordSkip(
                analytic: $analytic,
                timeBeforeSkipMs: 2000,
            );

            expect($skipped->response_status)->toBe('skipped');
        });

        it('tracks question outcomes with prompt rating', function () {
            $visitor = \App\Models\Visitor::factory()->create();
            $promptRun = PromptRun::factory()->create([
                'visitor_id' => $visitor->id,
            ]);

            $service = app(\App\Services\QuestionAnalyticsService::class);
            $analytic = $service->recordPresentation(
                promptRun: $promptRun,
                visitorId: $visitor->id,
                userId: null,
                questionId: 'U1',
                questionCategory: 'universal',
                personalityVariant: null,
                displayOrder: 1,
                wasRequired: true,
            );

            // Simulate user rating the prompt later
            $analytic->update([
                'prompt_rating' => 5,
                'prompt_copied' => true,
            ]);

            $analytic->refresh();
            expect($analytic->prompt_rating)->toBe(5);
            expect($analytic->prompt_copied)->toBeTrue();
        });
    });
});
