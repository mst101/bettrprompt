<?php

namespace Tests\Feature;

use App\Events\FrameworkSelected;
use App\Events\PromptOptimizationCompleted;
use App\Models\PromptRun;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class N8nWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected string $validSecret;

    protected function setUp(): void
    {
        parent::setUp();

        // Set up a valid webhook secret
        $this->validSecret = 'test-webhook-secret-123';
        config(['services.n8n.webhook_secret' => $this->validSecret]);
    }

    /** Helper method to make authenticated webhook requests */
    protected function webhookPost(array $data, ?string $secret = null)
    {
        $secret = $secret ?? $this->validSecret;

        if ($secret === false) {
            // No secret header
            return $this->postJson('/api/n8n/webhook', $data);
        }

        return $this
            ->withHeaders(['X-N8N-SECRET' => $secret])
            ->postJson('/api/n8n/webhook', $data);
    }

    public function test_webhook_requires_valid_secret(): void
    {
        $user = User::factory()->create();
        $promptRun = PromptRun::factory()->create(['user_id' => $user->id]);

        $response = $this->webhookPost([
            'prompt_run_id' => $promptRun->id,
            'workflow_stage' => 'framework_selected',
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);
    }

    public function test_webhook_rejects_missing_secret(): void
    {
        $user = User::factory()->create();
        $promptRun = PromptRun::factory()->create(['user_id' => $user->id]);

        $response = $this->webhookPost([
            'prompt_run_id' => $promptRun->id,
            'workflow_stage' => 'framework_selected',
        ], false); // No secret

        $response->assertStatus(403);
        $response->assertJson(['error' => 'Unauthorised']);
    }

    public function test_webhook_rejects_invalid_secret(): void
    {
        $user = User::factory()->create();
        $promptRun = PromptRun::factory()->create(['user_id' => $user->id]);

        $response = $this->webhookPost([
            'prompt_run_id' => $promptRun->id,
            'workflow_stage' => 'framework_selected',
        ], 'invalid-secret');

        $response->assertStatus(403);
        $response->assertJson(['error' => 'Unauthorised']);
    }

    public function test_webhook_validates_prompt_run_id_required(): void
    {
        $response = $this->webhookPost([
            'workflow_stage' => 'framework_selected',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'error' => 'Invalid payload',
        ]);
        $response->assertJsonStructure(['details' => ['prompt_run_id']]);
    }

    public function test_webhook_validates_prompt_run_id_exists(): void
    {
        $response = $this->webhookPost([
            'prompt_run_id' => 999999,
            'workflow_stage' => 'framework_selected',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'error' => 'Invalid payload',
        ]);
        $response->assertJsonStructure(['details' => ['prompt_run_id']]);
    }

    public function test_webhook_validates_workflow_stage_values(): void
    {
        $user = User::factory()->create();
        $promptRun = PromptRun::factory()->create(['user_id' => $user->id]);

        $response = $this->webhookPost([
            'prompt_run_id' => $promptRun->id,
            'workflow_stage' => 'invalid_stage',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'error' => 'Invalid payload',
        ]);
        $response->assertJsonStructure(['details' => ['workflow_stage']]);
    }

    public function test_webhook_validates_status_values(): void
    {
        $user = User::factory()->create();
        $promptRun = PromptRun::factory()->create(['user_id' => $user->id]);

        $response = $this->webhookPost([
            'prompt_run_id' => $promptRun->id,
            'status' => 'invalid_status',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'error' => 'Invalid payload',
        ]);
        $response->assertJsonStructure(['details' => ['status']]);
    }

    public function test_webhook_validates_framework_questions_array(): void
    {
        $user = User::factory()->create();
        $promptRun = PromptRun::factory()->create(['user_id' => $user->id]);

        $response = $this->webhookPost([
            'prompt_run_id' => $promptRun->id,
            'framework_questions' => 'not an array',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'error' => 'Invalid payload',
        ]);
        $response->assertJsonStructure(['details' => ['framework_questions']]);
    }

    public function test_webhook_updates_prompt_run_successfully(): void
    {
        $user = User::factory()->create();
        $promptRun = PromptRun::factory()->create([
            'user_id' => $user->id,
            'workflow_stage' => 'submitted',
            'status' => 'processing',
        ]);

        $response = $this->webhookPost([
            'prompt_run_id' => $promptRun->id,
            'workflow_stage' => 'framework_selected',
            'status' => 'processing',
            'selected_framework' => 'SMART Goals',
            'framework_reasoning' => 'This framework suits your task',
            'framework_questions' => [
                'What is your specific goal?',
                'How will you measure success?',
            ],
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $promptRun->refresh();
        $this->assertEquals('framework_selected', $promptRun->workflow_stage);
        $this->assertEquals('processing', $promptRun->status);
        $this->assertEquals('SMART Goals', $promptRun->selected_framework);
        $this->assertEquals('This framework suits your task', $promptRun->framework_reasoning);
        $this->assertCount(2, $promptRun->framework_questions);
    }

    public function test_webhook_broadcasts_framework_selected_event(): void
    {
        Event::fake([FrameworkSelected::class]);

        $user = User::factory()->create();
        $promptRun = PromptRun::factory()->create([
            'user_id' => $user->id,
            'workflow_stage' => 'submitted',
        ]);

        $response = $this->webhookPost([
            'prompt_run_id' => $promptRun->id,
            'workflow_stage' => 'framework_selected',
            'selected_framework' => 'SMART Goals',
        ]);

        $response->assertOk();

        Event::assertDispatched(FrameworkSelected::class, function ($event) use ($promptRun) {
            return $event->promptRun->id === $promptRun->id;
        });
    }

    public function test_webhook_broadcasts_completion_event(): void
    {
        Event::fake([PromptOptimizationCompleted::class]);

        $user = User::factory()->create();
        $promptRun = PromptRun::factory()->create([
            'user_id' => $user->id,
            'workflow_stage' => 'generating_prompt',
        ]);

        $response = $this->webhookPost([
            'prompt_run_id' => $promptRun->id,
            'workflow_stage' => 'completed',
            'status' => 'completed',
            'optimized_prompt' => 'Your optimised prompt here',
        ]);

        $response->assertOk();

        Event::assertDispatched(PromptOptimizationCompleted::class, function ($event) use ($promptRun) {
            return $event->promptRun->id === $promptRun->id;
        });
    }

    public function test_webhook_sets_completed_at_timestamp(): void
    {
        $user = User::factory()->create();
        $promptRun = PromptRun::factory()->create([
            'user_id' => $user->id,
            'workflow_stage' => 'generating_prompt',
            'completed_at' => null,
        ]);

        $response = $this->webhookPost([
            'prompt_run_id' => $promptRun->id,
            'workflow_stage' => 'completed',
            'status' => 'completed',
            'optimized_prompt' => 'Final prompt',
        ]);

        $response->assertOk();

        $promptRun->refresh();
        $this->assertNotNull($promptRun->completed_at);
        $this->assertEquals('completed', $promptRun->workflow_stage);
    }

    public function test_webhook_handles_missing_prompt_run(): void
    {
        // Create a prompt run then delete it
        $user = User::factory()->create();
        $promptRun = PromptRun::factory()->create(['user_id' => $user->id]);
        $promptRunId = $promptRun->id;
        $promptRun->delete();

        // Validation kicks in before manual checks, so deleted IDs return 422, not 404
        $response = $this->webhookPost([
            'prompt_run_id' => $promptRunId,
            'workflow_stage' => 'framework_selected',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'error' => 'Invalid payload',
        ]);
        $response->assertJsonStructure(['details' => ['prompt_run_id']]);
    }

    public function test_webhook_stores_error_message(): void
    {
        $user = User::factory()->create();
        $promptRun = PromptRun::factory()->create([
            'user_id' => $user->id,
            'workflow_stage' => 'submitted',
        ]);

        $response = $this->webhookPost([
            'prompt_run_id' => $promptRun->id,
            'workflow_stage' => 'failed',
            'status' => 'failed',
            'error_message' => 'OpenAI API rate limit exceeded',
        ]);

        $response->assertOk();

        $promptRun->refresh();
        $this->assertEquals('failed', $promptRun->workflow_stage);
        $this->assertEquals('failed', $promptRun->status);
        $this->assertEquals('OpenAI API rate limit exceeded', $promptRun->error_message);
    }

    public function test_webhook_stores_n8n_response_payload(): void
    {
        $user = User::factory()->create();
        $promptRun = PromptRun::factory()->create(['user_id' => $user->id]);

        $payload = [
            'execution_id' => 'exec-123',
            'timestamp' => '2025-01-08T12:00:00Z',
            'details' => [
                'httpCode' => 500,
                'errorType' => 'API_ERROR',
            ],
        ];

        $response = $this->webhookPost([
            'prompt_run_id' => $promptRun->id,
            'workflow_stage' => 'failed',
            'status' => 'failed',
            'n8n_response_payload' => $payload,
        ]);

        $response->assertOk();

        $promptRun->refresh();
        $this->assertEquals($payload, $promptRun->n8n_response_payload);
    }

    public function test_webhook_respects_rate_limiting(): void
    {
        $user = User::factory()->create();
        $promptRun = PromptRun::factory()->create(['user_id' => $user->id]);

        // Make 61 requests (limit is 60 per minute)
        for ($i = 0; $i < 61; $i++) {
            $response = $this->webhookPost([
                'prompt_run_id' => $promptRun->id,
                'workflow_stage' => 'framework_selected',
            ]);

            if ($i < 60) {
                $response->assertStatus(200);
            } else {
                // 61st request should be rate limited
                $response->assertStatus(429);
            }
        }
    }

    public function test_webhook_does_not_broadcast_on_non_milestone_stages(): void
    {
        Event::fake([FrameworkSelected::class, PromptOptimizationCompleted::class]);

        $user = User::factory()->create();
        $promptRun = PromptRun::factory()->create([
            'user_id' => $user->id,
            'workflow_stage' => 'framework_selected',
        ]);

        // Update to answering_questions (not a milestone)
        $response = $this->webhookPost([
            'prompt_run_id' => $promptRun->id,
            'workflow_stage' => 'answering_questions',
        ]);

        $response->assertOk();

        // Should not broadcast any events
        Event::assertNotDispatched(FrameworkSelected::class);
        Event::assertNotDispatched(PromptOptimizationCompleted::class);
    }
}
