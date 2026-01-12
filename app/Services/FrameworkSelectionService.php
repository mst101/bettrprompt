<?php

namespace App\Services;

use App\Models\FrameworkSelection;
use App\Models\PromptRun;
use Illuminate\Support\Facades\Log;

class FrameworkSelectionService
{
    /**
     * Record a framework selection event
     *
     * Called when a user selects a framework after receiving recommendations
     */
    public function recordSelection(
        PromptRun $promptRun,
        string $visitorId,
        ?int $userId,
        string $recommendedFramework,
        string $chosenFramework,
        array $recommendationScores = [],
        ?string $taskCategory = null,
        ?string $personalityType = null,
    ): FrameworkSelection {
        $accepted = $recommendedFramework === $chosenFramework;

        try {
            $selection = FrameworkSelection::create([
                'prompt_run_id' => $promptRun->id,
                'visitor_id' => $visitorId,
                'user_id' => $userId,
                'recommended_framework' => $recommendedFramework,
                'chosen_framework' => $chosenFramework,
                'accepted_recommendation' => $accepted,
                'task_category' => $taskCategory,
                'personality_type' => $personalityType,
                'recommendation_scores' => $recommendationScores,
                'selected_at' => now(),
            ]);

            Log::info('Framework selection recorded', [
                'prompt_run_id' => $promptRun->id,
                'recommended' => $recommendedFramework,
                'chosen' => $chosenFramework,
                'accepted' => $accepted,
            ]);

            return $selection;
        } catch (\Exception $e) {
            Log::error('Failed to record framework selection', [
                'prompt_run_id' => $promptRun->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Update framework selection with outcome metrics
     *
     * Called when the prompt is rated or interacted with
     */
    public function updateWithOutcome(
        FrameworkSelection $selection,
        ?int $promptRating = null,
        ?bool $promptCopied = null,
        ?bool $promptEdited = null,
        ?float $editPercentage = null,
    ): FrameworkSelection {
        try {
            $selection->update([
                'prompt_rating' => $promptRating ?? $selection->prompt_rating,
                'prompt_copied' => $promptCopied ?? $selection->prompt_copied,
                'prompt_edited' => $promptEdited ?? $selection->prompt_edited,
                'edit_percentage' => $editPercentage ?? $selection->edit_percentage,
            ]);

            Log::info('Framework selection outcome updated', [
                'selection_id' => $selection->id,
                'rating' => $promptRating,
                'copied' => $promptCopied,
            ]);

            return $selection->refresh();
        } catch (\Exception $e) {
            Log::error('Failed to update framework selection outcome', [
                'selection_id' => $selection->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Calculate acceptance rate for a framework
     */
    public function getAcceptanceRate(string $framework): float
    {
        $total = FrameworkSelection::where('recommended_framework', $framework)->count();

        if ($total === 0) {
            return 0;
        }

        $accepted = FrameworkSelection::where('recommended_framework', $framework)
            ->where('accepted_recommendation', true)
            ->count();

        return ($accepted / $total) * 100;
    }

    /**
     * Get average rating for a framework when chosen
     */
    public function getAverageRating(string $framework): ?float
    {
        return FrameworkSelection::where('chosen_framework', $framework)
            ->whereNotNull('prompt_rating')
            ->avg('prompt_rating');
    }

    /**
     * Get copy rate for a framework
     */
    public function getCopyRate(string $framework): float
    {
        $total = FrameworkSelection::where('chosen_framework', $framework)->count();

        if ($total === 0) {
            return 0;
        }

        $copied = FrameworkSelection::where('chosen_framework', $framework)
            ->where('prompt_copied', true)
            ->count();

        return ($copied / $total) * 100;
    }

    /**
     * Get framework performance summary
     */
    public function getFrameworkPerformance(string $framework): array
    {
        return [
            'framework' => $framework,
            'total_recommended' => FrameworkSelection::where('recommended_framework', $framework)->count(),
            'total_chosen' => FrameworkSelection::where('chosen_framework', $framework)->count(),
            'acceptance_rate' => $this->getAcceptanceRate($framework),
            'average_rating' => $this->getAverageRating($framework),
            'copy_rate' => $this->getCopyRate($framework),
        ];
    }

    /**
     * Get top performing frameworks
     */
    public function getTopFrameworks(int $limit = 5): array
    {
        $frameworks = FrameworkSelection::distinct('chosen_framework')
            ->pluck('chosen_framework')
            ->toArray();

        $performance = array_map(
            fn ($framework) => $this->getFrameworkPerformance($framework),
            $frameworks,
        );

        // Sort by acceptance rate descending
        usort($performance, fn ($a, $b) => $b['acceptance_rate'] <=> $a['acceptance_rate']);

        return array_slice($performance, 0, $limit);
    }
}
