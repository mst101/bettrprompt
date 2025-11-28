<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PromptFrameworkService
{
    private string $n8nBaseUrl;

    public function __construct()
    {
        // Always use the internal service URL to avoid HTTPS/host routing issues
        $this->n8nBaseUrl = config('services.n8n.url', env('N8N_INTERNAL_URL', 'http://n8n:5678'));
    }

    /**
     * Step 1: Analyse task and get framework + questions
     */
    public function analyseTask(
        string $taskDescription,
        ?string $personalityType,
        ?array $traitPercentages
    ): array {
        $payload = [
            'task_description' => $taskDescription,
            'personality_type' => $personalityType,
            'trait_percentages' => $traitPercentages,
        ];

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
