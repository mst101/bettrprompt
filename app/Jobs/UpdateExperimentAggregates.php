<?php

namespace App\Jobs;

use App\Models\AnalyticsEventExperiment;
use App\Models\ExperimentConversion;
use App\Models\ExperimentExposure;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateExperimentAggregates implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private int $experimentId,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info('Updating experiment aggregates', [
                'experiment_id' => $this->experimentId,
            ]);

            // Update conversion stats for each variant
            $variants = DB::table('experiment_variants')
                ->where('experiment_id', $this->experimentId)
                ->pluck('id');

            foreach ($variants as $variantId) {
                $this->updateVariantStats($this->experimentId, $variantId);
            }

            Log::info('Experiment aggregates updated successfully', [
                'experiment_id' => $this->experimentId,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update experiment aggregates', [
                'experiment_id' => $this->experimentId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Update aggregates for a specific variant
     */
    private function updateVariantStats(int $experimentId, int $variantId): void
    {
        // Count exposures
        $exposures = ExperimentExposure::where('experiment_id', $experimentId)
            ->where('variant_id', $variantId)
            ->count();

        // Count unique visitors exposed
        $uniqueVisitorsExposed = ExperimentExposure::where('experiment_id', $experimentId)
            ->where('variant_id', $variantId)
            ->distinct('visitor_id')
            ->count('visitor_id');

        // Count conversions (events attributed to this variant)
        $conversions = AnalyticsEventExperiment::where('experiment_id', $experimentId)
            ->where('variant_id', $variantId)
            ->count();

        // Count unique visitors who converted
        $uniqueVisitorsConverted = DB::table('analytics_event_experiments')
            ->join('analytics_events', 'analytics_event_experiments.event_id', '=', 'analytics_events.event_id')
            ->where('analytics_event_experiments.experiment_id', $experimentId)
            ->where('analytics_event_experiments.variant_id', $variantId)
            ->where('analytics_events.type', 'conversion')
            ->distinct('analytics_events.visitor_id')
            ->count('analytics_events.visitor_id');

        // Sum revenue (if applicable)
        $totalRevenue = DB::table('analytics_event_experiments')
            ->join('analytics_events', 'analytics_event_experiments.event_id', '=', 'analytics_events.event_id')
            ->where('analytics_event_experiments.experiment_id', $experimentId)
            ->where('analytics_event_experiments.variant_id', $variantId)
            ->where('analytics_events.name', 'like', '%subscription_success%')
            ->sum(DB::raw("CAST(analytics_events.properties->>'value' AS DECIMAL)"))
            ?? 0;

        // Calculate derived metrics
        $conversionRate = $exposures > 0 ? $conversions / $exposures : 0;
        $revenuePerVisitor = $uniqueVisitorsExposed > 0 ? $totalRevenue / $uniqueVisitorsExposed : 0;

        // Upsert into experiment_conversions
        ExperimentConversion::updateOrCreate(
            [
                'experiment_id' => $experimentId,
                'variant_id' => $variantId,
            ],
            [
                'exposures' => $exposures,
                'conversions' => $conversions,
                'unique_visitors_exposed' => $uniqueVisitorsExposed,
                'unique_visitors_converted' => $uniqueVisitorsConverted,
                'total_revenue' => $totalRevenue,
                'conversion_rate' => $conversionRate,
                'revenue_per_visitor' => $revenuePerVisitor,
            ],
        );

        Log::info('Variant stats updated', [
            'experiment_id' => $experimentId,
            'variant_id' => $variantId,
            'exposures' => $exposures,
            'conversions' => $conversions,
            'conversion_rate' => round($conversionRate, 6),
        ]);
    }
}
