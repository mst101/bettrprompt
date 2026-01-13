<?php

namespace App\Services;

use App\Models\Funnel;
use App\Models\FunnelProgress;

class FunnelProcessingService
{
    /**
     * Process an analytics event for funnel tracking
     *
     * @param  array  $eventData  Event data that may contain context (e.g., tier for subscription)
     */
    public function processEvent(string $visitorId, string $eventName, array $eventData = []): void
    {
        // Get all active funnels
        $funnels = Funnel::where('is_active', true)->with('stages')->get();

        foreach ($funnels as $funnel) {
            $this->processFunnelEvent($funnel, $visitorId, $eventName, $eventData);
        }
    }

    /**
     * Process event for a specific funnel
     */
    private function processFunnelEvent(Funnel $funnel, string $visitorId, string $eventName, array $eventData): void
    {
        // Get or create progress record
        $progress = FunnelProgress::firstOrCreate(
            [
                'funnel_id' => $funnel->id,
                'visitor_id' => $visitorId,
            ],
            [
                'stage' => 1,
                'stage_timestamps' => [],
            ]
        );

        // If already converted, skip
        if ($progress->is_converted) {
            return;
        }

        // Get current stage definition
        $currentStageDefinition = $funnel->stages()->where('order', $progress->stage)->first();

        if (! $currentStageDefinition) {
            return;
        }

        // Check if event matches current stage
        if ($currentStageDefinition->event_name !== $eventName) {
            return;
        }

        // Check if event conditions are met
        if (! $this->eventConditionsMet($currentStageDefinition, $eventData)) {
            return;
        }

        // Mark stage as completed with timestamp
        $timestamps = $progress->stage_timestamps ?? [];
        $timestamps[$progress->stage] = now()->toIso8601String();

        // Move to next stage or mark as converted
        $isLastStage = $progress->stage === $funnel->stages()->count();

        if ($isLastStage) {
            $progress->update([
                'stage_timestamps' => $timestamps,
                'conversion_date' => now(),
                'is_converted' => true,
            ]);
        } else {
            $progress->update([
                'stage' => $progress->stage + 1,
                'stage_timestamps' => $timestamps,
            ]);
        }
    }

    /**
     * Check if event conditions are met
     */
    private function eventConditionsMet(object $stage, array $eventData): bool
    {
        if (! $stage->event_conditions) {
            return true;
        }

        // Check tier_filter condition for subscription events
        if (isset($stage->event_conditions['tier_filter'])) {
            $allowedTiers = $stage->event_conditions['tier_filter'];
            // Try multiple possible property names
            $tier = $eventData['tier'] ?? $eventData['subscription_tier'] ?? null;

            if (! $tier || ! in_array($tier, $allowedTiers, true)) {
                return false;
            }
        }

        return true;
    }
}
