<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PromptFrameworkService
{
    private string $n8nBaseUrl;

    public function __construct()
    {
        // Prefer internal service URL when available (e.g., Sail n8n container)
        $this->n8nBaseUrl = config('services.n8n.url')
            ?? config('services.n8n.base_url');
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
            $response = Http::post("{$this->n8nBaseUrl}/webhook/prompt-builder-workflow-1", $payload);

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
        array $alternativeFrameworks,
        string $personalityTier,
        array $taskTraitAlignment,
        array $personalityAdjustmentsPreview,
        string $originalTaskDescription,
        ?string $personalityType,
        ?array $traitPercentages,
        array $questionAnswers
    ): array {
        $payload = [
            'task_classification' => $taskClassification,
            'cognitive_requirements' => $cognitiveRequirements,
            'selected_framework' => $selectedFramework,
            'alternative_frameworks' => $alternativeFrameworks,
            'personality_tier' => $personalityTier,
            'task_trait_alignment' => $taskTraitAlignment,
            'personality_adjustments_preview' => $personalityAdjustmentsPreview,
            'original_task_description' => $originalTaskDescription,
            'personality_type' => $personalityType,
            'trait_percentages' => $traitPercentages,
            'question_answers' => $questionAnswers,
        ];

        try {
            $response = Http::post("{$this->n8nBaseUrl}/webhook/prompt-builder-workflow-2", $payload);

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
