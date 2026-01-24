<?php

namespace App\Services;

use App\Data\GenerationPayload;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * N8n Workflow Client
 *
 * Orchestrates all three n8n workflows:
 * - Workflow 0: Pre-analysis (quick clarity check)
 * - Workflow 1: Analysis (framework selection)
 * - Workflow 2: Generation (prompt generation)
 */
class N8nWorkflowClient
{
    public private(set) string $n8nBaseUrl;

    public function __construct()
    {
        // Always use the internal service URL to avoid HTTPS/host routing issues
        // Config value is set in config/services.php for proper config caching support
        $this->n8nBaseUrl = config('services.n8n.url');
    }

    // ======== WORKFLOW 0: PRE-ANALYSIS ========

    /**
     * Execute pre-analysis workflow - quick clarity check before main analysis
     *
     * Returns whether clarification is needed and questions if applicable.
     * Always returns gracefully - never throws exceptions.
     * Note: Does NOT use personality data - only task description
     */
    public function executePreAnalysis(string $taskDescription, ?array $userContext = null): array
    {
        // Return mock response in testing environment (prevents real n8n calls during tests)
        if ($this->isTestingEnvironment()) {
            Log::info('Using mock response (testing environment)', [
                'workflow' => 'pre-analysis',
                'task' => Str::limit($taskDescription, 100),
            ]);

            return $this->getMockPreAnalysisResponse();
        }

        $payload = [
            'task_description' => $taskDescription,
        ];

        // Add user context if available (filter out null values)
        $filteredUserContext = $this->removeNullValues($userContext);
        if ($filteredUserContext !== null) {
            $payload['user_context'] = $filteredUserContext;
        }

        try {
            // Increased from 10 to 60 seconds to handle edge cases with long task descriptions
            // Pre-analysis should be fast, but better safe than sorry
            $response = Http::timeout(60)
                ->connectTimeout(10)
                ->post("{$this->n8nBaseUrl}/webhook/api/n8n/webhook/pre-analysis", $payload);

            if ($response->successful()) {
                $data = $response->json();

                // Log the raw response for debugging
                Log::info('Pre-analysis workflow response received', [
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
            Log::warning('Pre-analysis workflow failed, skipping', [
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
            Log::warning('Pre-analysis workflow failed, skipping', [
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

    // ======== WORKFLOW 1: ANALYSIS ========

    /**
     * Execute analysis workflow - analyse task and select appropriate framework
     */
    public function executeAnalysis(
        string $taskDescription,
        ?string $personalityType,
        ?array $traitPercentages,
        ?array $preAnalysisContext = null,
        ?string $forcedFrameworkCode = null,
        ?array $userContext = null
    ): array {
        // Return mock response in testing environment (prevents real n8n calls during tests)
        if ($this->isTestingEnvironment()) {
            Log::info('Using mock response (testing environment)', [
                'workflow' => 'analysis',
                'task' => Str::limit($taskDescription, 100),
            ]);

            return $this->getMockAnalysisResponse();
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
                'type' => $personalityType,
                'trait_percentages' => $traitPercentages,
            ];
            $payload['user_context'] = $this->removeNullValues($contextWithPersonality);
        } else {
            // If no user context, create one with just personality data
            $payload['user_context'] = [
                'personality' => [
                    'type' => $personalityType,
                    'trait_percentages' => $traitPercentages,
                ],
            ];
        }

        try {
            // Increased from 60 to 120 seconds to allow for complex analysis workflows
            $response = Http::timeout(120)
                ->post("{$this->n8nBaseUrl}/webhook/api/n8n/webhook/analysis", $payload);

            if ($response->successful()) {
                $data = $response->json();
                if (is_array($data)) {
                    return $data;
                }

                Log::warning('Analysis workflow returned non-array response', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return ['success' => true];
            }

            Log::error('Analysis workflow failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [
                'success' => false,
                'error' => 'Analysis workflow failed',
            ];
        } catch (\Exception $e) {
            Log::error('Analysis workflow exception', ['message' => $e->getMessage()]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    // ======== WORKFLOW 2: GENERATION ========

    /**
     * Execute generation workflow - generate the optimised prompt
     */
    public function executeGeneration(GenerationPayload $payload): array
    {
        // Return mock response in testing environment (prevents real n8n calls during tests)
        if ($this->isTestingEnvironment()) {
            Log::info('Using mock response (testing environment)', [
                'workflow' => 'generation',
                'task' => Str::limit($payload->originalTaskDescription, 100),
            ]);

            return $this->getMockGenerationResponse();
        }

        $n8nPayload = [
            'analysis_data' => [
                'task_classification' => $payload->taskClassification,
                'cognitive_requirements' => $payload->cognitiveRequirements,
                'selected_framework' => $payload->selectedFramework,
                'personality_tier' => $payload->personalityTier,
                'task_trait_alignment' => $payload->taskTraitAlignment,
            ],
            'original_task_description' => $payload->originalTaskDescription,
            'question_answers' => $payload->questionAnswers,
        ];

        // Add pre-analysis context if available
        if ($payload->preAnalysisContext !== null) {
            $n8nPayload['pre_analysis_context'] = $payload->preAnalysisContext;
        }

        // Add user context with nested personality data (filter out null values)
        if ($payload->userContext !== null) {
            // Nest personality data under user_context.personality
            $contextWithPersonality = $payload->userContext;
            $contextWithPersonality['personality'] = [
                'type' => $payload->personalityType,
                'trait_percentages' => $payload->traitPercentages,
            ];
            $n8nPayload['user_context'] = $this->removeNullValues($contextWithPersonality);
        } else {
            // If no user context, create one with just personality data
            $n8nPayload['user_context'] = [
                'personality' => [
                    'type' => $payload->personalityType,
                    'trait_percentages' => $payload->traitPercentages,
                ],
            ];
        }

        try {
            // Increased from 90 to 180 seconds to allow for longer-running workflows
            // Some complex prompts can take 100+ seconds to generate
            $response = Http::timeout(180)
                ->post("{$this->n8nBaseUrl}/webhook/api/n8n/webhook/generate", $n8nPayload);

            if ($response->successful()) {
                $data = $response->json();
                if (is_array($data)) {
                    return $data;
                }

                Log::warning('Generation workflow returned non-array response', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return ['success' => true];
            }

            Log::error('Generation workflow failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [
                'success' => false,
                'error' => 'Generation workflow failed',
            ];
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Generation workflow connection error', [
                'message' => $e->getMessage(),
                'url' => "{$this->n8nBaseUrl}/webhook/api/n8n/webhook/generate",
            ]);

            return [
                'success' => false,
                'error' => 'Failed to connect to n8n: '.$e->getMessage(),
            ];
        } catch (\Illuminate\Http\Client\RequestException $e) {
            Log::error('Generation workflow request error', [
                'message' => $e->getMessage(),
                'status' => $e->response?->status(),
            ]);

            return [
                'success' => false,
                'error' => 'n8n request failed: '.$e->getMessage(),
            ];
        } catch (\Exception $e) {
            Log::error('Generation workflow exception', [
                'message' => $e->getMessage(),
                'class' => get_class($e),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    // ======== INTERNAL HELPERS ========

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

    // ======== TESTING SUPPORT ========

    /**
     * Check if we should use test mock responses instead of calling real n8n workflows
     * Returns true when running in testing environment (E2E tests or server-side tests)
     *
     * This is important for E2E tests because we want to:
     * 1. Avoid making real HTTP calls to n8n during tests
     * 2. Test the UI flow without depending on external services
     * 3. Ensure tests run consistently and quickly
     *
     * When testing: N8nWorkflowClient will return mock responses instead of
     * calling the n8n workflows via HTTP, simulating successful workflow completions.
     */
    protected function isTestingEnvironment(): bool
    {
        return app()->environment('testing');
    }

    /**
     * Get mock n8n response for the pre-analysis workflow
     *
     * The pre-analysis workflow performs a quick clarity check before main analysis.
     * This mock simulates a successful response indicating no clarification is needed.
     */
    protected function getMockPreAnalysisResponse(): array
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
    protected function getMockAnalysisResponse(): array
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
    protected function getMockGenerationResponse(): array
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
}
