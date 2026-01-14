<?php

declare(strict_types=1);

use App\Models\CognitiveRequirement;
use App\Models\Framework;
use App\Models\Question;
use App\Models\TaskCategory;
use Illuminate\Database\QueryException;

describe('Question Normalization', function () {
    describe('Task Category Foreign Key', function () {
        it('enforces FK constraint on task_category_code', function () {
            expect(function () {
                Question::create([
                    'id' => 'TEST1',
                    'question_text' => 'Test question',
                    'purpose' => 'Test purpose',
                    'priority' => 'high',
                    'task_category_code' => 'NONEXISTENT',
                    'is_active' => true,
                ]);
            })->toThrow(QueryException::class);
        });

        it('allows null task_category_code for universal questions', function () {
            $question = Question::create([
                'id' => 'U_TEST1',
                'question_text' => 'Universal test question',
                'purpose' => 'Test purpose',
                'priority' => 'high',
                'task_category_code' => null,
                'is_active' => true,
            ]);

            expect($question->task_category_code)->toBeNull();
        });

        it('allows valid task_category_code', function () {
            $category = TaskCategory::firstOrCreate(
                ['code' => 'DECISION'],
                ['name' => 'Decision Making', 'description' => 'Decision Making', 'triggers' => [], 'is_active' => true, 'display_order' => 1]
            );

            $question = Question::create([
                'id' => 'D_TEST1',
                'question_text' => 'Test decision question',
                'purpose' => 'Test purpose',
                'priority' => 'high',
                'task_category_code' => 'DECISION',
                'is_active' => true,
            ]);

            expect($question->task_category_code)->toBe('DECISION');
            expect($question->taskCategory->code)->toBe('DECISION');
        });
    });

    describe('Framework Foreign Key', function () {
        it('enforces FK constraint on framework_code', function () {
            expect(function () {
                Question::create([
                    'id' => 'TEST2',
                    'question_text' => 'Test question',
                    'purpose' => 'Test purpose',
                    'priority' => 'high',
                    'framework_code' => 'NONEXISTENT',
                    'is_active' => true,
                ]);
            })->toThrow(QueryException::class);
        });

        it('allows null framework_code for category-specific questions', function () {
            TaskCategory::firstOrCreate(
                ['code' => 'ANALYSIS'],
                ['name' => 'Analysis', 'description' => 'Analysis', 'triggers' => [], 'is_active' => true, 'display_order' => 2]
            );

            $question = Question::create([
                'id' => 'A_TEST1',
                'question_text' => 'Test analysis question',
                'purpose' => 'Test purpose',
                'priority' => 'medium',
                'task_category_code' => 'ANALYSIS',
                'framework_code' => null,
                'is_active' => true,
            ]);

            expect($question->framework_code)->toBeNull();
        });

        it('allows valid framework_code', function () {
            Framework::firstOrCreate(
                ['code' => 'CO_STAR'],
                ['name' => 'CO-STAR', 'category' => 'content', 'description' => 'CO-STAR Framework', 'complexity' => 'medium', 'components' => [], 'is_active' => true, 'display_order' => 1]
            );

            $question = Question::create([
                'id' => 'COS_TEST1',
                'question_text' => 'Test CO-STAR question',
                'purpose' => 'Test purpose',
                'priority' => 'high',
                'framework_code' => 'CO_STAR',
                'is_active' => true,
            ]);

            expect($question->framework_code)->toBe('CO_STAR');
            expect($question->framework->code)->toBe('CO_STAR');
        });
    });

    describe('Cognitive Requirements Relationships', function () {
        it('can attach cognitive requirements to questions', function () {
            $question = Question::create([
                'id' => 'UCOGS001',
                'question_text' => 'Test question with cognitive requirements',
                'purpose' => 'Test purpose',
                'priority' => 'high',
                'is_active' => true,
            ]);

            $structure = CognitiveRequirement::firstOrCreate(
                ['code' => 'STRUCTURE'],
                ['name' => 'Structural Thinking', 'description' => 'Structural Thinking', 'aligned_traits' => [], 'opposed_traits' => [], 'is_active' => true, 'display_order' => 1]
            );

            $detail = CognitiveRequirement::firstOrCreate(
                ['code' => 'DETAIL'],
                ['name' => 'Attention to Detail', 'description' => 'Attention to Detail', 'aligned_traits' => [], 'opposed_traits' => [], 'is_active' => true, 'display_order' => 2]
            );

            $question->cognitiveRequirements()->attach($structure->code, ['requirement_level' => 'primary']);
            $question->cognitiveRequirements()->attach($detail->code, ['requirement_level' => 'primary']);

            expect($question->cognitiveRequirements)->toHaveCount(2);
            expect($question->cognitiveRequirements->pluck('code'))->toContain('STRUCTURE', 'DETAIL');
        });

        it('enforces unique constraint on question-requirement pairs', function () {
            $question = Question::create([
                'id' => 'UUNIQUE001',
                'question_text' => 'Test unique constraint',
                'purpose' => 'Test purpose',
                'priority' => 'high',
                'is_active' => true,
            ]);

            $requirement = CognitiveRequirement::firstOrCreate(
                ['code' => 'SYNTHESIS'],
                ['name' => 'Synthesis', 'description' => 'Synthesis', 'aligned_traits' => [], 'opposed_traits' => [], 'is_active' => true, 'display_order' => 3]
            );

            $question->cognitiveRequirements()->attach($requirement->code);

            expect(function () {
                $question->cognitiveRequirements()->attach($requirement->code);
            })->toThrow(Exception::class);
        });

        it('restricts deletion of cognitive requirements with attached questions', function () {
            $question = Question::create([
                'id' => 'TRESRC0001',
                'question_text' => 'Test delete restriction',
                'purpose' => 'Test purpose',
                'priority' => 'high',
                'is_active' => true,
            ]);

            $requirement = CognitiveRequirement::firstOrCreate(
                ['code' => 'EMPATHY'],
                ['name' => 'Empathy', 'description' => 'Empathy', 'aligned_traits' => [], 'opposed_traits' => [], 'is_active' => true, 'display_order' => 4]
            );

            $question->cognitiveRequirements()->attach($requirement->code);

            expect(function () {
                $requirement->delete();
            })->toThrow(Exception::class);
        });

        it('cascades deletion when question is deleted', function () {
            $question = Question::create([
                'id' => 'TCASC0001',
                'question_text' => 'Test cascade delete',
                'purpose' => 'Test purpose',
                'priority' => 'high',
                'is_active' => true,
            ]);

            $requirement = CognitiveRequirement::firstOrCreate(
                ['code' => 'CREATIVE'],
                ['name' => 'Creative Thinking', 'description' => 'Creative Thinking', 'aligned_traits' => [], 'opposed_traits' => [], 'is_active' => true, 'display_order' => 5]
            );

            $question->cognitiveRequirements()->attach($requirement->code);

            $relationshipCount = \DB::table('question_cognitive_requirements')
                ->where('question_id', $question->id)
                ->count();

            expect($relationshipCount)->toBe(1);

            $question->delete();

            $relationshipCount = \DB::table('question_cognitive_requirements')
                ->where('question_id', $question->id)
                ->count();

            expect($relationshipCount)->toBe(0);
        });
    });

    describe('Question Scopes', function () {
        beforeEach(function () {
            // Create test data
            TaskCategory::firstOrCreate(
                ['code' => 'DECISION'],
                ['name' => 'Decision Making', 'description' => 'Decision Making Framework', 'triggers' => [], 'is_active' => true, 'display_order' => 1]
            );

            Framework::firstOrCreate(
                ['code' => 'CO_STAR'],
                ['name' => 'CO-STAR', 'category' => 'content', 'description' => 'CO-STAR Framework', 'complexity' => 'medium', 'components' => [], 'is_active' => true, 'display_order' => 1]
            );

            Question::create([
                'id' => 'SCOPE_U1',
                'question_text' => 'Universal question',
                'purpose' => 'Test',
                'priority' => 'high',
                'task_category_code' => null,
                'framework_code' => null,
                'is_active' => true,
            ]);

            Question::create([
                'id' => 'SCOPE_D1',
                'question_text' => 'Decision question',
                'purpose' => 'Test',
                'priority' => 'high',
                'task_category_code' => 'DECISION',
                'framework_code' => null,
                'is_active' => true,
            ]);

            Question::create([
                'id' => 'SCOPE_C1',
                'question_text' => 'CO-STAR question',
                'purpose' => 'Test',
                'priority' => 'high',
                'framework_code' => 'CO_STAR',
                'is_active' => true,
            ]);
        });

        it('filters universal questions correctly', function () {
            $questions = Question::universal()->get();

            expect($questions)->toHaveCount(1);
            expect($questions->first()->id)->toBe('SCOPE_U1');
        });

        it('filters by category correctly', function () {
            $questions = Question::byCategory('DECISION')->get();

            expect($questions)->toHaveCount(1);
            expect($questions->first()->id)->toBe('SCOPE_D1');
        });

        it('filters by framework correctly', function () {
            $questions = Question::byFramework('CO_STAR')->get();

            expect($questions)->toHaveCount(1);
            expect($questions->first()->id)->toBe('SCOPE_C1');
        });
    });

    describe('Model Relationships', function () {
        it('loads task category through relationship', function () {
            $category = TaskCategory::firstOrCreate(
                ['code' => 'LEARNING'],
                ['name' => 'Learning', 'description' => 'Learning', 'triggers' => [], 'is_active' => true, 'display_order' => 6]
            );

            $question = Question::create([
                'id' => 'REL_L1',
                'question_text' => 'Learning question',
                'purpose' => 'Test',
                'priority' => 'medium',
                'task_category_code' => 'LEARNING',
                'is_active' => true,
            ]);

            $loaded = Question::with('taskCategory')->find($question->id);

            expect($loaded->taskCategory)->not()->toBeNull();
            expect($loaded->taskCategory->code)->toBe('LEARNING');
        });

        it('loads framework through relationship', function () {
            Framework::firstOrCreate(
                ['code' => 'REACT'],
                ['name' => 'ReAct', 'category' => 'agentic', 'description' => 'ReAct Framework', 'complexity' => 'high', 'components' => [], 'is_active' => true, 'display_order' => 2]
            );

            $question = Question::create([
                'id' => 'REL_R1',
                'question_text' => 'ReAct question',
                'purpose' => 'Test',
                'priority' => 'high',
                'framework_code' => 'REACT',
                'is_active' => true,
            ]);

            $loaded = Question::with('framework')->find($question->id);

            expect($loaded->framework)->not()->toBeNull();
            expect($loaded->framework->code)->toBe('REACT');
        });

        it('category has inverse relationship to questions', function () {
            $category = TaskCategory::firstOrCreate(
                ['code' => 'IDEATION'],
                ['name' => 'Ideation', 'description' => 'Ideation', 'triggers' => [], 'is_active' => true, 'display_order' => 7]
            );

            Question::create([
                'id' => 'REL_I1',
                'question_text' => 'Ideation question 1',
                'purpose' => 'Test',
                'priority' => 'medium',
                'task_category_code' => 'IDEATION',
                'is_active' => true,
            ]);

            Question::create([
                'id' => 'REL_I2',
                'question_text' => 'Ideation question 2',
                'purpose' => 'Test',
                'priority' => 'medium',
                'task_category_code' => 'IDEATION',
                'is_active' => true,
            ]);

            $questions = $category->questions()->get();

            expect($questions)->toHaveCount(2);
        });

        it('framework has inverse relationship to questions', function () {
            Framework::firstOrCreate(
                ['code' => 'STEP_BACK'],
                ['name' => 'Step Back', 'category' => 'reasoning', 'description' => 'Step Back Framework', 'complexity' => 'low', 'components' => [], 'is_active' => true, 'display_order' => 3]
            );

            Question::create([
                'id' => 'REL_S1',
                'question_text' => 'Step Back question',
                'purpose' => 'Test',
                'priority' => 'high',
                'framework_code' => 'STEP_BACK',
                'is_active' => true,
            ]);

            $framework = Framework::find('STEP_BACK');
            $questions = $framework->questions()->get();

            expect($questions)->toHaveCount(1);
        });

        it('cognitive requirement has inverse relationship to questions', function () {
            $question1 = Question::create([
                'id' => 'REL_CR1',
                'question_text' => 'Question 1',
                'purpose' => 'Test',
                'priority' => 'high',
                'is_active' => true,
            ]);

            $question2 = Question::create([
                'id' => 'REL_CR2',
                'question_text' => 'Question 2',
                'purpose' => 'Test',
                'priority' => 'high',
                'is_active' => true,
            ]);

            $requirement = CognitiveRequirement::firstOrCreate(
                ['code' => 'OBJECTIVE'],
                ['name' => 'Objective', 'description' => 'Objective', 'aligned_traits' => [], 'opposed_traits' => [], 'is_active' => true, 'display_order' => 8]
            );

            $question1->cognitiveRequirements()->attach($requirement->code);
            $question2->cognitiveRequirements()->attach($requirement->code);

            $questions = $requirement->questions()->get();

            expect($questions)->toHaveCount(2);
        });
    });
});
