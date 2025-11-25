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
     * Workflow 1: Analyse task and generate clarifying questions
     */
    public function analyseTask(
        string $taskDescription,
        ?string $personalityType = null,
        ?array $traitPercentages = null
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
     * Workflow 2: Generate optimised prompt
     */
    public function generatePrompt(
        array $taskClassification,
        array $selectedFramework,
        array $alternativeFrameworks,
        string $personalityTier,
        array $personalityAdjustmentsPreview,
        string $originalTaskDescription,
        ?string $personalityType,
        ?array $traitPercentages,
        array $questionAnswers
    ): array {
        $payload = [
            'task_classification' => $taskClassification,
            'selected_framework' => $selectedFramework,
            'alternative_frameworks' => $alternativeFrameworks,
            'personality_tier' => $personalityTier,
            'personality_adjustments_preview' => $personalityAdjustmentsPreview,
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
