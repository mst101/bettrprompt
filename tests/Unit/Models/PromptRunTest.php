<?php

use App\Enums\WorkflowStage;
use App\Models\PromptRun;
use App\Models\User;
use App\Models\Visitor;

/**
 * Unit tests for PromptRun model business logic
 * These test workflow state management without HTTP/Jobs
 */
describe('PromptRun authorisation', function () {
    test('canBeAccessedBy returns true for owning user', function () {
        $user = User::factory()->create();
        $promptRun = PromptRun::factory()->create(['user_id' => $user->id]);

        expect($promptRun->canBeAccessedBy($user->id, null))->toBeTrue();
    });

    test('canBeAccessedBy returns true for owning visitor', function () {
        $visitor = Visitor::factory()->create();
        $promptRun = PromptRun::factory()->create(['visitor_id' => $visitor->id]);

        expect($promptRun->canBeAccessedBy(null, $visitor->id))->toBeTrue();
    });

    test('canBeAccessedBy returns false for different user', function () {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $promptRun = PromptRun::factory()->create(['user_id' => $owner->id]);

        expect($promptRun->canBeAccessedBy($other->id, null))->toBeFalse();
    });

    test('canBeAccessedBy returns false for different visitor', function () {
        $visitor1 = Visitor::factory()->create();
        $promptRun = PromptRun::factory()->create(['visitor_id' => $visitor1->id]);

        expect($promptRun->canBeAccessedBy(null, 'different-visitor-id'))->toBeFalse();
    });

    test('canBeAccessedBy returns false when both user and visitor are null', function () {
        $promptRun = PromptRun::factory()->create();

        expect($promptRun->canBeAccessedBy(null, null))->toBeFalse();
    });

    test('canBeAccessedBy prioritises user over visitor', function () {
        $user = User::factory()->create();
        $visitor = Visitor::factory()->create();
        $promptRun = PromptRun::factory()->create([
            'user_id' => $user->id,
            'visitor_id' => $visitor->id,
        ]);

        // User owns it, so returns true regardless of visitor ID mismatch
        expect($promptRun->canBeAccessedBy($user->id, 'different-visitor-id'))->toBeTrue();
    });
});

describe('PromptRun clarifying answers', function () {
    test('recordClarifyingAnswer stores answer at correct index', function () {
        $promptRun = PromptRun::factory()->create([
            'framework_questions' => [
                ['question' => 'Q1'],
                ['question' => 'Q2'],
                ['question' => 'Q3'],
            ],
            'clarifying_answers' => null,
        ]);

        $answers = $promptRun->recordClarifyingAnswer(0, 'Answer 1');

        expect($answers)
            ->toHaveCount(3)
            ->and($answers[0])->toBe('Answer 1')
            ->and($answers[1])->toBeNull()
            ->and($answers[2])->toBeNull()
            ->and($promptRun->fresh())
            ->clarifying_answers->toBe($answers)
            ->current_question_index->toBe(1)
            ->workflow_stage->toBe(WorkflowStage::AnalysisCompleted);

    });

    test('recordClarifyingAnswer pads missing answers with null', function () {
        $promptRun = PromptRun::factory()->create([
            'framework_questions' => [
                ['question' => 'Q1'],
                ['question' => 'Q2'],
                ['question' => 'Q3'],
            ],
            'clarifying_answers' => null,
        ]);

        // Answer question 2 (index 1) without answering question 1
        $answers = $promptRun->recordClarifyingAnswer(1, 'Answer 2');

        expect($answers)
            ->toHaveCount(3)
            ->and($answers[0])->toBeNull()
            ->and($answers[1])->toBe('Answer 2')
            ->and($answers[2])->toBeNull();
    });

    test('recordClarifyingAnswer converts empty string to null', function () {
        $promptRun = PromptRun::factory()->create([
            'framework_questions' => [['question' => 'Q1']],
        ]);

        $answers = $promptRun->recordClarifyingAnswer(0, '');

        expect($answers[0])->toBeNull();
    });

    test('recordClarifyingAnswer advances question index', function () {
        $promptRun = PromptRun::factory()->create([
            'framework_questions' => [
                ['question' => 'Q1'],
                ['question' => 'Q2'],
            ],
            'current_question_index' => 0,
        ]);

        $promptRun->recordClarifyingAnswer(0, 'Answer 1');

        expect($promptRun->fresh()->current_question_index)->toBe(1);
    });

    test('recordClarifyingAnswer does not exceed question count', function () {
        $promptRun = PromptRun::factory()->create([
            'framework_questions' => [
                ['question' => 'Q1'],
                ['question' => 'Q2'],
            ],
        ]);

        $promptRun->recordClarifyingAnswer(1, 'Answer 2');

        // Should be 2 (question count), not 3
        expect($promptRun->fresh()->current_question_index)->toBe(2);
    });

    test('recordClarifyingAnswer returns empty array when no questions', function () {
        $promptRun = PromptRun::factory()->create([
            'framework_questions' => null,
        ]);

        $answers = $promptRun->recordClarifyingAnswer(0, 'Answer');

        expect($answers)->toBe([]);
    });

    test('recordClarifyingAnswer clamps index to valid range', function () {
        $promptRun = PromptRun::factory()->create([
            'framework_questions' => [
                ['question' => 'Q1'],
                ['question' => 'Q2'],
            ],
        ]);

        // Try to answer index 99 (out of bounds)
        $answers = $promptRun->recordClarifyingAnswer(99, 'Answer');

        // Should clamp to last question (index 1)
        expect($answers[1])->toBe('Answer');
    });
});

describe('PromptRun workflow state transitions', function () {
    test('markWorkflowCompleted sets correct stage and clears error', function () {
        $promptRun = PromptRun::factory()->create([
            'workflow_stage' => WorkflowStage::AnalysisProcessing,
            'error_message' => 'Previous error',
        ]);

        $promptRun->markWorkflowCompleted(1);

        expect($promptRun->fresh())
            ->workflow_stage->toBe(WorkflowStage::AnalysisCompleted)
            ->error_message->toBeNull();
    });

    test('markWorkflowCompleted accepts additional data', function () {
        $promptRun = PromptRun::factory()->create([
            'workflow_stage' => WorkflowStage::PreAnalysisProcessing,
        ]);

        $promptRun->markWorkflowCompleted(0, [
            'pre_analysis_questions' => [['question' => 'Test']],
            'pre_analysis_reasoning' => 'Need clarification',
        ]);

        expect($promptRun->fresh())
            ->workflow_stage->toBe(WorkflowStage::PreAnalysisCompleted)
            ->pre_analysis_questions->toBe([['question' => 'Test']])
            ->pre_analysis_reasoning->toBe('Need clarification')
            ->error_message->toBeNull();
    });

    test('markWorkflowCompleted sets completed_at for workflow 2', function () {
        $promptRun = PromptRun::factory()->create([
            'workflow_stage' => WorkflowStage::GenerationProcessing,
            'completed_at' => null,
        ]);

        $promptRun->markWorkflowCompleted(2);

        expect($promptRun->fresh())
            ->workflow_stage->toBe(WorkflowStage::GenerationCompleted)
            ->completed_at->not->toBeNull();
    });

    test('markWorkflowCompleted does not override explicit completed_at', function () {
        $explicitTime = now()->subDay();
        $promptRun = PromptRun::factory()->create([
            'workflow_stage' => WorkflowStage::GenerationProcessing,
        ]);

        $promptRun->markWorkflowCompleted(2, ['completed_at' => $explicitTime]);

        expect($promptRun->fresh()->completed_at->toDateTimeString())
            ->toBe($explicitTime->toDateTimeString());
    });

    test('markWorkflowFailed sets failure stage and error message', function () {
        $promptRun = PromptRun::factory()->create([
            'workflow_stage' => WorkflowStage::AnalysisProcessing,
            'error_message' => null,
        ]);

        $promptRun->markWorkflowFailed(1, 'Service timeout');

        expect($promptRun->fresh())
            ->workflow_stage->toBe(WorkflowStage::AnalysisFailed)
            ->error_message->toBe('Service timeout');
    });

    test('markWorkflowProcessing sets processing stage and clears error', function () {
        $promptRun = PromptRun::factory()->create([
            'workflow_stage' => WorkflowStage::PreAnalysisCompleted,
            'error_message' => 'Old error',
        ]);

        $promptRun->markWorkflowProcessing(1);

        expect($promptRun->fresh())
            ->workflow_stage->toBe(WorkflowStage::AnalysisProcessing)
            ->error_message->toBeNull();
    });

    test('markWorkflowProcessing accepts additional data', function () {
        $promptRun = PromptRun::factory()->create([
            'workflow_stage' => WorkflowStage::PreAnalysisCompleted,
        ]);

        $promptRun->markWorkflowProcessing(1, [
            'pre_analysis_skipped' => true,
            'pre_analysis_reasoning' => 'Clear task',
        ]);

        expect($promptRun->fresh())
            ->workflow_stage->toBe(WorkflowStage::AnalysisProcessing)
            ->pre_analysis_skipped->toBeTrue()
            ->pre_analysis_reasoning->toBe('Clear task');
    });
});

describe('PromptRun state helpers', function () {
    test('isProcessing returns true for processing stages', function () {
        $promptRun = PromptRun::factory()->create(['workflow_stage' => WorkflowStage::AnalysisProcessing]);

        expect($promptRun->isProcessing)->toBeTrue();
    });

    test('isProcessing returns false for completed stages', function () {
        $promptRun = PromptRun::factory()->create(['workflow_stage' => WorkflowStage::AnalysisCompleted]);

        expect($promptRun->isProcessing)->toBeFalse();
    });

    test('isCompleted returns true only for workflow 2 completed', function () {
        $promptRun1 = PromptRun::factory()->create(['workflow_stage' => WorkflowStage::PreAnalysisCompleted]);
        $promptRun2 = PromptRun::factory()->create(['workflow_stage' => WorkflowStage::AnalysisCompleted]);
        $promptRun3 = PromptRun::factory()->create(['workflow_stage' => WorkflowStage::GenerationCompleted]);

        expect($promptRun1->isCompleted)->toBeFalse()
            ->and($promptRun2->isCompleted)->toBeFalse()
            ->and($promptRun3->isCompleted)->toBeTrue();
    });

    test('isFailed returns true for any failed stage', function () {
        $promptRun1 = PromptRun::factory()->create(['workflow_stage' => WorkflowStage::PreAnalysisFailed]);
        $promptRun2 = PromptRun::factory()->create(['workflow_stage' => WorkflowStage::AnalysisFailed]);
        $promptRun3 = PromptRun::factory()->create(['workflow_stage' => WorkflowStage::GenerationFailed]);

        expect($promptRun1->isFailed)->toBeTrue()
            ->and($promptRun2->isFailed)->toBeTrue()
            ->and($promptRun3->isFailed)->toBeTrue();
    });

    test('getFailedWorkflow returns correct workflow number', function () {
        $promptRun0 = PromptRun::factory()->create(['workflow_stage' => WorkflowStage::PreAnalysisFailed]);
        $promptRun1 = PromptRun::factory()->create(['workflow_stage' => WorkflowStage::AnalysisFailed]);
        $promptRun2 = PromptRun::factory()->create(['workflow_stage' => WorkflowStage::GenerationFailed]);
        $promptRunSuccess = PromptRun::factory()->create(['workflow_stage' => WorkflowStage::GenerationCompleted]);

        expect($promptRun0->getFailedWorkflow())->toBe(0)
            ->and($promptRun1->getFailedWorkflow())->toBe(1)
            ->and($promptRun2->getFailedWorkflow())->toBe(2)
            ->and($promptRunSuccess->getFailedWorkflow())->toBeNull();
    });
});

describe('PromptRun error handling edge cases', function () {
    test('workflow transitions always clear error on success', function () {
        $promptRun = PromptRun::factory()->create([
            'workflow_stage' => WorkflowStage::PreAnalysisFailed,
            'error_message' => 'Critical failure',
        ]);

        // Retry and succeed
        $promptRun->markWorkflowProcessing(0);

        expect($promptRun->fresh()->error_message)->toBeNull();

        $promptRun->markWorkflowCompleted(0);

        expect($promptRun->fresh()->error_message)->toBeNull();
    });

    test('workflow can transition from failed to processing to completed', function () {
        $promptRun = PromptRun::factory()->create(['workflow_stage' => WorkflowStage::AnalysisFailed]);

        $promptRun->markWorkflowProcessing(1);
        expect($promptRun->fresh()->workflow_stage)->toBe(WorkflowStage::AnalysisProcessing);

        $promptRun->markWorkflowCompleted(1);
        expect($promptRun->fresh()->workflow_stage)->toBe(WorkflowStage::AnalysisCompleted);
    });
});
