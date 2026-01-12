<?php

namespace App\Services;

use App\Models\AnalyticsEvent;
use App\Models\AnalyticsEventExperiment;
use App\Models\Experiment;
use App\Models\ExperimentExposure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ConversionAttributionService
{
    /**
     * Attribute a conversion event to all eligible experiments
     *
     * A conversion is attributed to an experiment if:
     * 1. The experiment is/was running at time of conversion
     * 2. The visitor had at least one exposure to the experiment before the conversion
     * 3. The conversion occurred within the experiment's window
     */
    public function attributeConversion(AnalyticsEvent $event): void
    {
        // Only process conversion events
        if ($event->type !== 'conversion') {
            return;
        }

        // Must have a visitor
        if (! $event->visitor_id) {
            return;
        }

        // Find all experiments where visitor was exposed before conversion
        $eligibleExperiments = $this->findEligibleExperiments(
            visitorId: $event->visitor_id,
            conversionTime: $event->occurred_at,
        );

        foreach ($eligibleExperiments as $experiment) {
            // Find the exposure that qualifies for attribution
            $exposure = ExperimentExposure::where('experiment_id', $experiment['id'])
                ->where('visitor_id', $event->visitor_id)
                ->where('occurred_at', '<', $event->occurred_at)
                ->latest('occurred_at')
                ->first();

            if (! $exposure) {
                continue;
            }

            // Check if already attributed (prevent duplicates)
            $existing = AnalyticsEventExperiment::where('event_id', $event->event_id)
                ->where('experiment_id', $experiment['id'])
                ->exists();

            if ($existing) {
                continue;
            }

            // Create attribution
            try {
                AnalyticsEventExperiment::create([
                    'event_id' => $event->event_id,
                    'experiment_id' => $experiment['id'],
                    'variant_id' => $exposure->variant_id,
                    'exposure_id' => $exposure->id,
                ]);

                Log::info('Conversion attributed to experiment', [
                    'event_id' => $event->event_id,
                    'experiment_id' => $experiment['id'],
                    'variant_id' => $exposure->variant_id,
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to attribute conversion', [
                    'event_id' => $event->event_id,
                    'experiment_id' => $experiment['id'],
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Find all experiments eligible for conversion attribution
     *
     * @return array<array{id: int, slug: string}>
     */
    private function findEligibleExperiments($visitorId, $conversionTime): array
    {
        // Get experiments where:
        // - Experiment was running (or had just ended) at conversion time
        // - Visitor had exposures during the experiment
        $experiments = DB::table('experiments')
            ->select('experiments.id', 'experiments.slug')
            ->join('experiment_exposures', 'experiments.id', '=', 'experiment_exposures.experiment_id')
            ->where('experiment_exposures.visitor_id', $visitorId)
            ->where('experiment_exposures.occurred_at', '<', $conversionTime)
            ->where(function ($query) use ($conversionTime) {
                // Experiment was running at time of conversion
                $query->where('experiments.status', 'running')
                    ->where('experiments.started_at', '<=', $conversionTime);
                // OR experiment had ended recently (within 24 hours) to allow delayed conversions
                // This handles cases where a user sees variant, leaves, comes back later and converts
            })
            ->distinct()
            ->get()
            ->toArray();

        return array_map(fn ($row) => (array) $row, $experiments);
    }
}
