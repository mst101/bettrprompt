<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PromptFrameworkService
{
    private string $n8nBaseUrl;

    public function __construct()
    {
        // Always use the internal service URL to avoid HTTPS/host routing issues
        $this->n8nBaseUrl = config('services.n8n.url', env('N8N_INTERNAL_URL', 'http://n8n:5678'));
    }

    /**
     * Recursively remove null values from an array
     * This ensures we don't send unnecessary null fields to n8n workflows
     */
    private function removeNullValues(?array $data): ?array
    {
        if ($data === null) {
            return null;
        }

        $filtered = [];

        foreach ($data as $key => $value) {
            if ($value === null) {
                continue; // Skip null values
            }

            if (is_array($value)) {
                $nestedFiltered = $this->removeNullValues($value);
                // Only add if the nested array is not empty after filtering
                if (! empty($nestedFiltered)) {
                    $filtered[$key] = $nestedFiltered;
                }
            } else {
                $filtered[$key] = $value;
            }
        }

        return empty($filtered) ? null : $filtered;
    }

    /**
     * Check if we should use test mock responses instead of calling real n8n workflows
     * Returns true when running in testing environment (E2E tests or server-side tests)
     *
     * This is important for E2E tests because we want to:
     * 1. Avoid making real HTTP calls to n8n during tests
     * 2. Test the UI flow without depending on external services
     * 3. Ensure tests run consistently and quickly
     *
     * When testing: PromptFrameworkService will return mock responses instead of
     * calling the n8n workflows via HTTP, simulating successful workflow completions.
     */
    protected function isTestingEnvironment(): bool
    {
        return env('APP_ENV') === 'testing' || env('TESTING') === 'true' || app()->environment('testing');
    }

    /**
     * Get mock n8n response for the pre-analysis workflow
     *
     * The pre-analysis workflow performs a quick clarity check before main analysis.
     * This mock simulates a successful response indicating no clarification is needed.
     */
    protected function getMockTestResponseForPreAnalysis(): array
    {
        return [
            'needs_clarification' => false,
            'questions' => [],
            'reasoning' => 'Mock response - no clarification needed',
            'pre_analysis_context' => [],
            'api_usage' => 0,
        ];
    }

    /**
     * Get mock n8n response for the analysis workflow (Workflow 1)
     *
     * Workflow 1 analyzes the task and selects an appropriate framework.
     * This mock simulates a successful analysis that selected SMART Goals framework.
     */
    protected function getMockTestResponseForAnalysis(): array
    {
        return [
            'success' => true,
            'data' => [
                'task_classification' => ['category' => 'planning', 'confidence' => 0.95],
                'framework_name' => 'SMART Goals',
                'framework_code' => 'SMART',
                'framework_description' => 'Specific, Measurable, Achievable, Relevant, Time-bound',
                'questions' => [
                    ['question' => 'What is your specific goal?'],
                    ['question' => 'How will you measure success?'],
                ],
                'reasoning' => 'Mock framework selection',
            ],
        ];
    }

    /**
     * Get mock n8n response for the generation workflow (Workflow 2)
     *
     * Workflow 2 generates an optimised prompt based on the analysis results.
     * This mock simulates a successful generation with a sample prompt.
     */
    protected function getMockTestResponseForGeneration(): array
    {
        return [
            'success' => true,
            'data' => [
                'optimised_prompt' => 'Mock optimised prompt based on your input and selected framework.',
                'framework_used' => ['code' => 'SMART', 'name' => 'SMART Goals'],
                'personality_adjustments_summary' => [],
                'model_recommendations' => ['gpt-4'],
                'iteration_suggestions' => [],
            ],
        ];
    }

    /**
     * Step 0: Pre-Analysis - quick clarity check before main analysis
     * Returns whether clarification is needed and questions if applicable
     * Always returns gracefully - never throws exceptions
     * Note: Does NOT use personality data - only task description
     */
    public function preAnalyseTask(string $taskDescription, ?array $userContext = null): array
    {
        // Return mock response in testing environment (prevents real n8n calls during tests)
        if ($this->isTestingEnvironment()) {
            Log::info('Using mock pre-analysis response (testing environment)', [
                'task' => Str::limit($taskDescription, 100),
            ]);

            return $this->getMockTestResponseForPreAnalysis();
        }

        $payload = [
            'task_description' => $taskDescription,
        ];

        // Add user context if available (filter out null values)
        $filteredUserContext = $this->removeNullValues($userContext);
        if ($filteredUserContext !== null) {
            $payload['user_context'] = $filteredUserContext;
        }
        Log::info('Pre-analysis payload', (array) $payload);

        dd($payload);

        try {
            // Increased from 10 to 60 seconds to handle edge cases with long task descriptions
            // Pre-analysis should be fast, but better safe than sorry
            $response = Http::timeout(60)
                ->connectTimeout(10)
                ->post("{$this->n8nBaseUrl}/webhook/api/n8n/webhook/pre-analysis", $payload);

            if ($response->successful()) {
                $data = $response->json();

                // Log the raw response for debugging
                Log::info('Pre-analysis response received', [
                    'data' => $data,
                    'task' => Str::limit($taskDescription, 100),
                ]);

                // Validate response structure
                if (! isset($data['success']) || ! $data['success']) {
                    throw new \Exception('Invalid response from pre-analysis workflow');
                }

                if (! isset($data['data']['needs_clarification'])) {
                    throw new \Exception('Missing needs_clarification field');
                }

                return [
                    'needs_clarification' => $data['data']['needs_clarification'],
                    'questions' => $data['data']['questions'] ?? null,
                    'reasoning' => $data['data']['reasoning'] ?? 'Proceeding with analysis.',
                    'pre_analysis_context' => $data['data']['pre_analysis_context'] ?? null,
                    'api_usage' => $data['api_usage'] ?? null,
                ];
            }

            // Non-successful response - skip gracefully
            Log::warning('Pre-analysis failed, skipping', [
                'status' => $response->status(),
                'body' => $response->body(),
                'task' => Str::limit($taskDescription, 100),
            ]);

            return [
                'needs_clarification' => false,
                'reasoning' => 'Proceeding directly to analysis.',
                'pre_analysis_context' => null,
                'api_usage' => null,
            ];
        } catch (\Exception $e) {
            // Any error - skip gracefully
            Log::warning('Pre-analysis failed, skipping', [
                'error' => $e->getMessage(),
                'task' => Str::limit($taskDescription, 100),
            ]);

            return [
                'needs_clarification' => false,
                'reasoning' => 'Proceeding directly to analysis.',
                'pre_analysis_context' => null,
                'api_usage' => null,
            ];
        }
    }

    /**
     * Step 1: Analyse task and get framework + questions
     */
    public function analyseTask(
        string $taskDescription,
        ?string $personalityType,
        ?array $traitPercentages,
        ?array $preAnalysisContext = null,
        ?string $forcedFrameworkCode = null,
        ?array $userContext = null
    ): array {
        // Return mock response in testing environment (prevents real n8n calls during tests)
        if ($this->isTestingEnvironment()) {
            Log::info('Using mock analysis response (testing environment)', [
                'task' => Str::limit($taskDescription, 100),
            ]);

            return $this->getMockTestResponseForAnalysis();
        }

        $payload = [
            'task_description' => $taskDescription,
        ];

        // Add pre-analysis context if available (structured JSON)
        if ($preAnalysisContext !== null) {
            $payload['pre_analysis_context'] = $preAnalysisContext;
        }

        // Add forced framework if specified
        if ($forcedFrameworkCode !== null) {
            $payload['forced_framework_code'] = $forcedFrameworkCode;
        }

        // Add user context with nested personality data (filter out null values)
        if ($userContext !== null) {
            // Nest personality data under user_context.personality
            $contextWithPersonality = $userContext;
            $contextWithPersonality['personality'] = [
                'personality_type' => $personalityType,
                'trait_percentages' => $traitPercentages,
            ];
            $payload['user_context'] = $this->removeNullValues($contextWithPersonality);
        } else {
            // If no user context, create one with just personality data
            $payload['user_context'] = [
                'personality' => [
                    'personality_type' => $personalityType,
                    'trait_percentages' => $traitPercentages,
                ],
            ];
        }

        try {
            // Increased from 60 to 120 seconds to allow for complex analysis workflows
            $response = Http::timeout(120)
                ->post("{$this->n8nBaseUrl}/webhook/api/n8n/webhook/analysis", $payload);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Workflow 1 failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [
                'success' => false,
                'error' => 'Analysis workflow failed',
            ];
        } catch (\Exception $e) {
            Log::error('Workflow 1 exception', ['message' => $e->getMessage()]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Step 2: Generate the optimised prompt
     */
    public function generatePrompt(
        array $taskClassification,
        array $cognitiveRequirements,
        array $selectedFramework,
        string $personalityTier,
        array $taskTraitAlignment,
        array $personalityAdjustmentsPreview,
        string $originalTaskDescription,
        ?string $personalityType,
        ?array $traitPercentages,
        array $questionAnswers,
        ?array $userContext = null,
        ?array $preAnalysisContext = null
    ): array {
        // Return mock response in testing environment (prevents real n8n calls during tests)
        if ($this->isTestingEnvironment()) {
            Log::info('Using mock generation response (testing environment)', [
                'task' => Str::limit($originalTaskDescription, 100),
            ]);

            return $this->getMockTestResponseForGeneration();
        }

        $payload = [
            'analysis_data' => [
                'task_classification' => $taskClassification,
                'cognitive_requirements' => $cognitiveRequirements,
                'selected_framework' => $selectedFramework,
                'personality_tier' => $personalityTier,
                'task_trait_alignment' => $taskTraitAlignment,
            ],
            'original_task_description' => $originalTaskDescription,
            'question_answers' => $questionAnswers,
        ];

        // Add pre-analysis context if available
        if ($preAnalysisContext !== null) {
            $payload['pre_analysis_context'] = $preAnalysisContext;
        }

        // Add user context with nested personality data (filter out null values)
        if ($userContext !== null) {
            // Nest personality data under user_context.personality
            $contextWithPersonality = $userContext;
            $contextWithPersonality['personality'] = [
                'personality_type' => $personalityType,
                'trait_percentages' => $traitPercentages,
            ];
            $payload['user_context'] = $this->removeNullValues($contextWithPersonality);
        } else {
            // If no user context, create one with just personality data
            $payload['user_context'] = [
                'personality' => [
                    'personality_type' => $personalityType,
                    'trait_percentages' => $traitPercentages,
                ],
            ];
        }

        try {
            // Increased from 90 to 180 seconds to allow for longer-running workflows
            // Some complex prompts can take 100+ seconds to generate
            $response = Http::timeout(180)
                ->post("{$this->n8nBaseUrl}/webhook/api/n8n/webhook/generate", $payload);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Workflow 2 failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [
                'success' => false,
                'error' => 'Generation workflow failed',
            ];
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Workflow 2 connection error', [
                'message' => $e->getMessage(),
                'url' => "{$this->n8nBaseUrl}/webhook/api/n8n/webhook/generate",
            ]);

            return [
                'success' => false,
                'error' => 'Failed to connect to n8n: '.$e->getMessage(),
            ];
        } catch (\Illuminate\Http\Client\RequestException $e) {
            Log::error('Workflow 2 request error', [
                'message' => $e->getMessage(),
                'status' => $e->response?->status(),
            ]);

            return [
                'success' => false,
                'error' => 'n8n request failed: '.$e->getMessage(),
            ];
        } catch (\Exception $e) {
            Log::error('Workflow 2 exception', [
                'message' => $e->getMessage(),
                'class' => get_class($e),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
