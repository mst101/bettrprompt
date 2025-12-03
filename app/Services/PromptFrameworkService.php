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
     * Pre-Analysis: Quick clarity check before main analysis
     * Returns whether clarification is needed and questions if applicable
     * Always returns gracefully - never throws exceptions
     * Note: Does NOT use personality data - only task description
     */
    public function preAnalyseTask(string $taskDescription): array
    {
        $payload = [
            'task_description' => $taskDescription,
        ];

        try {
            $response = Http::timeout(10)
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
        ?string $forcedFrameworkCode = null
    ): array {
        $payload = [
            'task_description' => $taskDescription,
            'personality_type' => $personalityType,
            'trait_percentages' => $traitPercentages,
        ];

        // Add pre-analysis context if available (structured JSON)
        if ($preAnalysisContext !== null) {
            $payload['pre_analysis_context'] = $preAnalysisContext;
        }

        // Add forced framework if specified
        if ($forcedFrameworkCode !== null) {
            $payload['forced_framework_code'] = $forcedFrameworkCode;
        }

        try {
            $response = Http::timeout(60)
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
        array $questionAnswers
    ): array {
        $payload = [
            'analysis_data' => [
                'task_classification' => $taskClassification,
                'cognitive_requirements' => $cognitiveRequirements,
                'selected_framework' => $selectedFramework,
                'personality_tier' => $personalityTier,
                'task_trait_alignment' => $taskTraitAlignment,
            ],
            'original_task_description' => $originalTaskDescription,
            'personality_type' => $personalityType,
            'trait_percentages' => $traitPercentages,
            'question_answers' => $questionAnswers,
        ];

        try {
            $response = Http::timeout(90)
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
        } catch (\Exception $e) {
            Log::error('Workflow 2 exception', ['message' => $e->getMessage()]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
