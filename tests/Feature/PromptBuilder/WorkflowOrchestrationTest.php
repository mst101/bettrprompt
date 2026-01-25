<?php

use App\Enums\WorkflowStage;
use App\Models\PromptRun;
use App\Models\User;
use App\Models\Visitor;
use Illuminate\Support\Facades\Bus;

describe('Workflow 0: Pre-Analysis Initiation', function () {
    test('creates prompt run with workflow stage 0_processing', function () {
        Bus::fake();

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postCountry(route('prompt-builder.pre-analyse', [], false), [
                'task_description' => 'Create a detailed project plan for a new software product',
                'personality_type' => 'INTJ-A',
                'trait_percentages' => [
                    'mind' => 75,
                    'energy' => 55,
                    'nature' => 80,
                    'tactics' => 70,
                    'identity' => 65,
                ],
            ]);

        expect(PromptRun::count())->toBe(1);
        $promptRun = PromptRun::first();

        expect($promptRun->user_id)->toBe($user->id)
            ->and($promptRun->task_description)->toBe('Create a detailed project plan for a new software product')
            ->and($promptRun->personality_type)->toBe('INTJ-A')
            ->and($promptRun->workflow_stage)->toBe(WorkflowStage::PreAnalysisProcessing);
    });

    test('dispatches pre-analysis job', function () {
        Bus::fake();

        $user = User::factory()->create();

        $this->actingAs($user)
            ->postCountry(route('prompt-builder.pre-analyse', [], false), [
                'task_description' => 'Create a plan',
                'personality_type' => 'INTJ-A',
                'trait_percentages' => [
                    'mind' => 75,
                    'energy' => 55,
                    'nature' => 80,
                    'tactics' => 70,
                    'identity' => 65,
                ],
            ]);

        Bus::assertDispatched(\App\Jobs\ProcessPreAnalysis::class);
    });

    test('allows guest visitor to create prompt', function () {
        Bus::fake();

        $visitor = Visitor::factory()->create();
        $visitorId = $visitor->id;

        $response = $this->withCookie('visitor_id', (string) $visitorId)
            ->postCountry(route('prompt-builder.pre-analyse', [], false), [
                'task_description' => 'Create a plan',
                'personality_type' => 'INTJ-A',
                'trait_percentages' => [
                    'mind' => 75,
                    'energy' => 55,
                    'nature' => 80,
                    'tactics' => 70,
                    'identity' => 65,
                ],
            ]);

        expect(PromptRun::where('visitor_id', $visitorId)->count())->toBe(1);
    });

    test('redirects to prompt run show page after creation', function () {
        Bus::fake();

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postCountry(route('prompt-builder.pre-analyse', [], false), [
                'task_description' => 'Create a plan',
                'personality_type' => 'INTJ-A',
                'trait_percentages' => [
                    'mind' => 75,
                    'energy' => 55,
                    'nature' => 80,
                    'tactics' => 70,
                    'identity' => 65,
                ],
            ]);

        $promptRun = PromptRun::first();
        $response->assertRedirect(countryRoute('prompt-builder.show', ['promptRun' => $promptRun]));
    });
});

describe('Workflow 1: Analysis from Pre-Analysis', function () {
    test('transitions from workflow 0_completed to 1_processing', function () {
        Bus::fake();

        $user = User::factory()->create();
        $promptRun = PromptRun::factory()->create([
            'user_id' => $user->id,
            'workflow_stage' => '0_completed',
            'pre_analysis_questions' => [
                ['question' => 'What is the scope?'],
                ['question' => 'What is the timeline?'],
            ],
        ]);

        $this->actingAs($user)
            ->postCountry(route('prompt-builder.pre-analysis-answers', ['promptRun' => $promptRun], false), [
                'answers' => [
                    'Build a customer portal',
                    'We have 3 months',
                ],
            ]);

        $promptRun->refresh();
        expect($promptRun->workflow_stage)->toBe(WorkflowStage::AnalysisProcessing);
    });

    test('stores pre-analysis answers from user', function () {
        Bus::fake();

        $user = User::factory()->create();
        $promptRun = PromptRun::factory()->create([
            'user_id' => $user->id,
            'workflow_stage' => '0_completed',
            'pre_analysis_questions' => [
                ['question' => 'What is the scope?'],
            ],
        ]);

        $this->actingAs($user)
            ->postCountry(route('prompt-builder.pre-analysis-answers', ['promptRun' => $promptRun], false), [
                'answers' => ['Build a large system'],
            ]);

        $promptRun->refresh();
        expect($promptRun->pre_analysis_answers)->toBe(['Build a large system']);
    });

    test('dispatches ProcessAnalysis job when submitting answers', function () {
        Bus::fake();

        $user = User::factory()->create();
        $promptRun = PromptRun::factory()->create([
            'user_id' => $user->id,
            'workflow_stage' => '0_completed',
            'pre_analysis_questions' => [
                ['question' => 'What is the scope?'],
            ],
        ]);

        $this->actingAs($user)
            ->postCountry(route('prompt-builder.pre-analysis-answers', ['promptRun' => $promptRun], false), [
                'answers' => ['Test answer'],
            ]);

        Bus::assertDispatched(\App\Jobs\ProcessAnalysis::class);
    });

});

describe('Create Child from Pre-Analysis Answers', function () {
    test('creates child prompt run with updated answers', function () {
        Bus::fake();

        $user = User::factory()->create();
        $parentPromptRun = PromptRun::factory()->create([
            'user_id' => $user->id,
            'workflow_stage' => '1_completed',
            'pre_analysis_questions' => [
                ['id' => 'q1', 'question' => 'What is the scope?'],
            ],
            'pre_analysis_answers' => ['q1' => 'Original answer'],
        ]);

        expect(PromptRun::count())->toBe(1);

        $this->actingAs($user)
            ->postCountry(route('prompt-builder.create-child-from-pre-analysis-answers', ['parentPromptRun' => $parentPromptRun], false), [
                'answers' => ['q1' => 'Updated answer'],
            ]);

        expect(PromptRun::count())->toBe(2);
        $childPromptRun = PromptRun::where('parent_id', $parentPromptRun->id)->first();

        expect($childPromptRun)
            ->not->toBeNull()
            ->and($childPromptRun->pre_analysis_answers)->toBe(['q1' => 'Updated answer'])
            ->and($childPromptRun->workflow_stage)->toBe(WorkflowStage::AnalysisProcessing);

        // Parent should remain unchanged
        $parentPromptRun->refresh();
        expect($parentPromptRun->pre_analysis_answers)->toBe(['q1' => 'Original answer']);
    });

    test('dispatches ProcessAnalysis job on child prompt run', function () {
        Bus::fake();

        $user = User::factory()->create();
        $parentPromptRun = PromptRun::factory()->create([
            'user_id' => $user->id,
            'workflow_stage' => '1_completed',
            'pre_analysis_questions' => [
                ['id' => 'q1', 'question' => 'What is the scope?'],
            ],
        ]);

        $this->actingAs($user)
            ->postCountry(route('prompt-builder.create-child-from-pre-analysis-answers', ['parentPromptRun' => $parentPromptRun], false), [
                'answers' => ['q1' => 'Updated answer'],
            ]);

        Bus::assertDispatched(\App\Jobs\ProcessAnalysis::class);
    });

});

describe('Show Prompt Run', function () {
    test('displays prompt run page with current question', function () {
        $user = User::factory()->create();
        $promptRun = PromptRun::factory()->create([
            'user_id' => $user->id,
            'workflow_stage' => '1_processing',
            'framework_questions' => [
                ['question' => 'What is the expected output?'],
                ['question' => 'What are the constraints?'],
            ],
            'current_question_index' => 0,
        ]);

        $response = $this->actingAs($user)
            ->getCountry(route('prompt-builder.show', ['promptRun' => $promptRun], false));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('PromptBuilder/Show')
            );
    });

    test('loads parent and children relationships', function () {
        $user = User::factory()->create();
        $parentRun = PromptRun::factory()->create(['user_id' => $user->id]);
        $childRun = PromptRun::factory()->create([
            'user_id' => $user->id,
            'parent_id' => $parentRun->id,
        ]);

        $response = $this->actingAs($user)
            ->getCountry(route('prompt-builder.show', ['promptRun' => $childRun], false));

        $response->assertOk();
    });
});

describe('Authorization', function () {
    test('prevents other user from accessing prompt run', function () {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $promptRun = PromptRun::factory()->create(['user_id' => $user1->id]);

        $response = $this->actingAs($user2)
            ->getCountry(route('prompt-builder.show', ['promptRun' => $promptRun], false));

        $response->assertForbidden();
    });

    test('prevents guest from accessing authenticated user prompt', function () {
        $user = User::factory()->create();
        $promptRun = PromptRun::factory()->create(['user_id' => $user->id]);

        $response = $this->getCountry(route('prompt-builder.show', ['promptRun' => $promptRun], false));

        $response->assertForbidden();
    });

    test('allows guest to access their own visitor prompt', function () {
        $visitor = Visitor::factory()->create();
        $promptRun = PromptRun::factory()->create([
            'visitor_id' => $visitor->id,
            'user_id' => null,
        ]);

        $response = $this->withCookie('visitor_id', (string) $visitor->id)
            ->getCountry(route('prompt-builder.show', ['promptRun' => $promptRun], false));

        $response->assertOk();
    });
});
