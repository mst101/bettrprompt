<?php

namespace Tests\Feature;

use App\Models\PromptRun;
use App\Models\User;
use App\Services\N8nClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PromptOptimizerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'personality_type' => 'INTJ',
            'trait_percentages' => [
                'introversion' => 75,
                'intuition' => 80,
                'thinking' => 70,
                'judging' => 65,
            ],
        ]);
    }

    public function test_index_displays_form_for_authenticated_users(): void
    {
        $this->actingAs($this->user);

        $response = $this->get(route('prompt-optimizer.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('PromptOptimizer/Index')
            ->has('auth.user')
        );
    }

    public function test_index_redirects_guests_to_login(): void
    {
        $response = $this->get(route('prompt-optimizer.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_store_validates_task_description_required(): void
    {
        $this->actingAs($this->user);

        $response = $this->post(route('prompt-optimizer.store'), []);

        $response->assertSessionHasErrors(['task_description']);
    }

    public function test_store_validates_task_description_min_length(): void
    {
        $this->actingAs($this->user);

        $response = $this->post(route('prompt-optimizer.store'), [
            'task_description' => 'short', // Too short (min 10)
        ]);

        $response->assertSessionHasErrors(['task_description']);
    }

    public function test_store_creates_prompt_run_successfully(): void
    {
        $this->actingAs($this->user);

        // Mock N8nClient to return success
        $this->mock(N8nClient::class, function ($mock) {
            $mock->shouldReceive('triggerWebhook')
                ->once()
                ->andReturn([
                    'success' => true,
                    'data' => [
                        'selected_framework' => 'SMART Goals',
                        'framework_reasoning' => 'Suitable for goal-oriented tasks',
                        'framework_questions' => [
                            'What is your specific objective?',
                        ],
                    ],
                ]);
        });

        $response = $this->post(route('prompt-optimizer.store'), [
            'task_description' => 'Create a detailed project plan for launching a new product',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('prompt_runs', [
            'user_id' => $this->user->id,
            'task_description' => 'Create a detailed project plan for launching a new product',
            'personality_type' => 'INTJ',
            'status' => 'processing',
        ]);
    }

    public function test_store_includes_user_personality_traits(): void
    {
        $this->actingAs($this->user);

        $this->mock(N8nClient::class, function ($mock) {
            $mock->shouldReceive('triggerWebhook')
                ->once()
                ->with('/webhook/framework-selector', \Mockery::on(function ($data) {
                    return $data['personality_type'] === 'INTJ' &&
                        isset($data['trait_percentages']) &&
                        $data['trait_percentages']['introversion'] === 75;
                }))
                ->andReturn([
                    'success' => true,
                    'data' => [
                        'selected_framework' => 'SMART Goals',
                        'framework_reasoning' => 'Test reasoning',
                        'framework_questions' => [],
                    ],
                ]);
        });

        $response = $this->post(route('prompt-optimizer.store'), [
            'task_description' => 'Test task that is long enough to pass validation',
        ]);

        $response->assertRedirect();
    }

    public function test_show_displays_prompt_run_details(): void
    {
        $this->actingAs($this->user);

        $promptRun = PromptRun::factory()->create([
            'user_id' => $this->user->id,
            'task_description' => 'My test task',
            'personality_type' => 'INTJ',
            'selected_framework' => 'SMART Goals',
            'workflow_stage' => 'framework_selected',
        ]);

        $response = $this->get(route('prompt-optimizer.show', $promptRun));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('PromptOptimizer/Show')
            ->where('promptRun.id', $promptRun->id)
            ->where('promptRun.taskDescription', 'My test task')
            ->where('promptRun.selectedFramework', 'SMART Goals')
        );
    }

    public function test_show_displays_current_question(): void
    {
        $this->actingAs($this->user);

        $promptRun = PromptRun::factory()->create([
            'user_id' => $this->user->id,
            'workflow_stage' => 'answering_questions',
            'framework_questions' => [
                'What is your goal?',
                'How will you measure success?',
            ],
            'clarifying_answers' => [],
        ]);

        $response = $this->get(route('prompt-optimizer.show', $promptRun));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('currentQuestion', 'What is your goal?')
            ->where('progress.answered', 0)
            ->where('progress.total', 2)
        );
    }

    public function test_show_returns_null_when_all_questions_answered(): void
    {
        $this->actingAs($this->user);

        $promptRun = PromptRun::factory()->create([
            'user_id' => $this->user->id,
            'workflow_stage' => 'generating_prompt',
            'framework_questions' => [
                'Question 1',
                'Question 2',
            ],
            'clarifying_answers' => [
                'Answer 1',
                'Answer 2',
            ],
        ]);

        $response = $this->get(route('prompt-optimizer.show', $promptRun));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('currentQuestion', null)
            ->where('progress.answered', 2)
            ->where('progress.total', 2)
        );
    }

    public function test_history_displays_only_user_prompt_runs(): void
    {
        $this->actingAs($this->user);

        // Create prompt runs for this user
        PromptRun::factory()->count(2)->create(['user_id' => $this->user->id]);

        // Create prompt runs for another user
        $otherUser = User::factory()->create();
        PromptRun::factory()->count(3)->create(['user_id' => $otherUser->id]);

        $response = $this->get(route('prompt-optimizer.history'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('PromptOptimizer/History')
            ->has('promptRuns.data', 2) // Should only see own runs
        );
    }

    public function test_history_orders_by_created_at_desc(): void
    {
        $this->actingAs($this->user);

        // Create runs with specific timestamps
        $oldRun = PromptRun::factory()->create([
            'user_id' => $this->user->id,
            'created_at' => now()->subDays(2),
        ]);
        $newRun = PromptRun::factory()->create([
            'user_id' => $this->user->id,
            'created_at' => now(),
        ]);

        $response = $this->get(route('prompt-optimizer.history'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('promptRuns.data.0.id', $newRun->id)
            ->where('promptRuns.data.1.id', $oldRun->id)
        );
    }

    public function test_answer_question_saves_answer_successfully(): void
    {
        $this->actingAs($this->user);

        $promptRun = PromptRun::factory()->create([
            'user_id' => $this->user->id,
            'workflow_stage' => 'answering_questions',
            'framework_questions' => ['Question 1', 'Question 2'],
            'clarifying_answers' => [],
        ]);

        $response = $this->post(route('prompt-optimizer.answer', $promptRun), [
            'answer' => 'This is my detailed answer to the first question',
        ]);

        $response->assertRedirect(route('prompt-optimizer.show', $promptRun));

        $promptRun->refresh();
        $this->assertCount(1, $promptRun->clarifying_answers);
        $this->assertEquals('This is my detailed answer to the first question', $promptRun->clarifying_answers[0]);
    }

    public function test_answer_question_validates_required_answer(): void
    {
        $this->actingAs($this->user);

        $promptRun = PromptRun::factory()->create([
            'user_id' => $this->user->id,
            'workflow_stage' => 'answering_questions',
            'framework_questions' => ['Question 1'],
            'clarifying_answers' => [],
        ]);

        $response = $this->post(route('prompt-optimizer.answer', $promptRun), [
            'answer' => '',
        ]);

        $response->assertSessionHasErrors(['answer']);
    }

    public function test_answer_question_validates_answer_length(): void
    {
        $this->actingAs($this->user);

        $promptRun = PromptRun::factory()->create([
            'user_id' => $this->user->id,
            'workflow_stage' => 'answering_questions',
            'framework_questions' => ['Question 1'],
            'clarifying_answers' => [],
        ]);

        $response = $this->post(route('prompt-optimizer.answer', $promptRun), [
            'answer' => str_repeat('a', 2001), // Too long (max 2000)
        ]);

        $response->assertSessionHasErrors(['answer']);
    }

    public function test_completing_all_questions_triggers_generation(): void
    {
        $this->actingAs($this->user);

        // Mock N8nClient to return successful optimization
        $this->mock(N8nClient::class, function ($mock) {
            $mock->shouldReceive('triggerWebhook')
                ->once()
                ->with('/webhook/final-prompt-optimizer', \Mockery::any())
                ->andReturn([
                    'success' => true,
                    'data' => [
                        'optimized_prompt' => 'Your optimised prompt here',
                    ],
                ]);
        });

        $promptRun = PromptRun::factory()->create([
            'user_id' => $this->user->id,
            'workflow_stage' => 'answering_questions',
            'framework_questions' => ['Question 1', 'Question 2'],
            'clarifying_answers' => ['Answer 1'], // One already answered
        ]);

        // Answer the last question
        $response = $this->post(route('prompt-optimizer.answer', $promptRun), [
            'answer' => 'Answer 2',
        ]);

        $response->assertRedirect(route('prompt-optimizer.show', $promptRun));

        $promptRun->refresh();
        $this->assertEquals('completed', $promptRun->workflow_stage);
        $this->assertEquals('completed', $promptRun->status);
    }

    public function test_skip_question_records_null_answer(): void
    {
        $this->actingAs($this->user);

        $promptRun = PromptRun::factory()->create([
            'user_id' => $this->user->id,
            'workflow_stage' => 'answering_questions',
            'framework_questions' => ['Question 1', 'Question 2'],
            'clarifying_answers' => [],
        ]);

        $response = $this->post(route('prompt-optimizer.skip', $promptRun));

        $response->assertRedirect(route('prompt-optimizer.show', $promptRun));

        $promptRun->refresh();
        $this->assertCount(1, $promptRun->clarifying_answers);
        $this->assertNull($promptRun->clarifying_answers[0]);
    }

    public function test_guests_cannot_access_prompt_optimizer(): void
    {
        $response = $this->post(route('prompt-optimizer.store'), [
            'task_description' => 'Test task',
        ]);

        $response->assertRedirect(route('login'));
    }

    public function test_user_cannot_view_other_users_prompt_runs(): void
    {
        $this->actingAs($this->user);

        $otherUser = User::factory()->create();
        $otherRun = PromptRun::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->get(route('prompt-optimizer.show', $otherRun));

        $response->assertForbidden();
    }

    public function test_user_cannot_answer_other_users_questions(): void
    {
        $this->actingAs($this->user);

        $otherUser = User::factory()->create();
        $otherRun = PromptRun::factory()->create([
            'user_id' => $otherUser->id,
            'workflow_stage' => 'answering_questions',
            'framework_questions' => ['Question 1'],
            'clarifying_answers' => [],
        ]);

        $response = $this->post(route('prompt-optimizer.answer', $otherRun), [
            'answer' => 'My answer',
        ]);

        $response->assertForbidden();
    }

    public function test_completed_prompt_run_displays_optimized_prompt(): void
    {
        $this->actingAs($this->user);

        $promptRun = PromptRun::factory()->create([
            'user_id' => $this->user->id,
            'workflow_stage' => 'completed',
            'status' => 'completed',
            'optimized_prompt' => 'This is your personalised, optimised prompt based on your INTJ personality.',
        ]);

        $response = $this->get(route('prompt-optimizer.show', $promptRun));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('promptRun.optimizedPrompt', 'This is your personalised, optimised prompt based on your INTJ personality.')
            ->where('promptRun.workflowStage', 'completed')
        );
    }
}
