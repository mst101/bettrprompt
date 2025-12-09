<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\DatabaseTransactions::class)
    ->in('Feature');

pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\DatabaseTransactions::class)
    ->in('Unit');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Test Helpers & Builders
|--------------------------------------------------------------------------
|
| Make test builders and helpers available throughout the test suite
|
*/

use Tests\Builders\PromptRunBuilder;

function promptRunBuilder(): PromptRunBuilder
{
    return PromptRunBuilder::new();
}

/*
|--------------------------------------------------------------------------
| Webhook Testing Helpers
|--------------------------------------------------------------------------
|
| Helper functions for n8n webhook testing
|
*/

/** Helper function to make authenticated webhook requests */
function webhookPost(array $data, ?string $secret = null): \Illuminate\Testing\TestResponse
{
    $validSecret = test()->validSecret;
    $secret = $secret ?? $validSecret;

    if ($secret === false) {
        // No secret header
        return test()->postJson('/api/n8n/webhook', $data);
    }

    return test()
        ->withHeaders(['X-N8N-SECRET' => $secret])
        ->postJson('/api/n8n/webhook', $data);
}

/**
 * Create a SMART Goals framework array for webhook testing
 */
function createSmartFramework(string $rationale = 'This framework suits your task'): array
{
    return [
        'name' => 'SMART Goals',
        'code' => 'SMART',
        'components' => [
            'Specific',
            'Measurable',
            'Achievable',
            'Relevant',
            'Time-bound',
        ],
        'rationale' => $rationale,
    ];
}

/**
 * Create framework questions array for webhook testing
 */
function createFrameworkQuestions(int $count = 2): array
{
    $questions = [
        'What is your specific goal?',
        'How will you measure success?',
        'What resources are available?',
        'What timeline do you have?',
    ];

    return array_slice($questions, 0, $count);
}

/**
 * Create a standard webhook payload for 1_completed stage
 */
function createFrameworkSelectedPayload(
    \App\Models\PromptRun $promptRun,
    ?array $framework = null,
    ?array $questions = null
): array {
    return [
        'prompt_run_id' => $promptRun->id,
        'workflow_stage' => '1_completed',
        'selected_framework' => $framework ?? createSmartFramework(),
        'framework_questions' => $questions ?? createFrameworkQuestions(),
    ];
}

/**
 * Create a standard webhook payload for 2_completed stage
 */
function createCompletedPayload(\App\Models\PromptRun $promptRun, ?string $optimizedPrompt = null): array
{
    return [
        'prompt_run_id' => $promptRun->id,
        'workflow_stage' => '2_completed',
        'optimized_prompt' => $optimizedPrompt ?? 'Here is your optimised prompt based on your personality type and preferences.',
    ];
}
