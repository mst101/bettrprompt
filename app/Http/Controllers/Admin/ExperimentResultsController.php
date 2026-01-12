<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Experiment;
use App\Models\ExperimentConversion;
use Illuminate\Http\JsonResponse;

class ExperimentResultsController extends Controller
{
    /**
     * Get detailed results for an experiment
     */
    public function show(Experiment $experiment): JsonResponse
    {
        $this->authorize('view', $experiment);

        // Load variants with conversion stats
        $variants = $experiment->variants()
            ->with(['conversions' => function ($query) {
                $query->where('experiment_id', $experiment->id);
            }])
            ->get()
            ->map(fn ($variant) => [
                'id' => $variant->id,
                'slug' => $variant->slug,
                'name' => $variant->name,
                'is_control' => $variant->is_control,
                'weight' => $variant->weight,
                'stats' => $this->buildVariantStats($variant),
            ]);

        return response()->json([
            'experiment' => [
                'id' => $experiment->id,
                'slug' => $experiment->slug,
                'name' => $experiment->name,
                'status' => $experiment->status,
                'goal_event' => $experiment->goal_event,
                'goal_type' => $experiment->goal_type,
                'started_at' => $experiment->started_at,
                'ended_at' => $experiment->ended_at,
                'minimum_runtime_hours' => $experiment->minimum_runtime_hours,
                'minimum_detectable_effect' => $experiment->minimum_detectable_effect,
                'winner_variant_id' => $experiment->winner_variant_id,
            ],
            'variants' => $variants,
            'summary' => $this->buildSummary($experiment),
            'readiness' => $this->assessReadiness($experiment),
        ]);
    }

    /**
     * Build stats for a variant
     */
    private function buildVariantStats($variant): array
    {
        $conversion = ExperimentConversion::where('experiment_id', $variant->experiment_id)
            ->where('variant_id', $variant->id)
            ->first();

        if (! $conversion) {
            return [
                'exposures' => 0,
                'conversions' => 0,
                'unique_visitors_exposed' => 0,
                'unique_visitors_converted' => 0,
                'conversion_rate' => 0,
                'total_revenue' => 0,
                'revenue_per_visitor' => 0,
            ];
        }

        return [
            'exposures' => $conversion->exposures,
            'conversions' => $conversion->conversions,
            'unique_visitors_exposed' => $conversion->unique_visitors_exposed,
            'unique_visitors_converted' => $conversion->unique_visitors_converted,
            'conversion_rate' => round($conversion->conversion_rate, 6),
            'total_revenue' => round($conversion->total_revenue, 2),
            'revenue_per_visitor' => round($conversion->revenue_per_visitor, 4),
        ];
    }

    /**
     * Build experiment summary stats
     */
    private function buildSummary(Experiment $experiment): array
    {
        $conversions = $experiment->conversions()->get();

        $totalExposures = $conversions->sum('exposures');
        $totalConversions = $conversions->sum('conversions');
        $totalRevenue = $conversions->sum('total_revenue');

        return [
            'total_exposures' => $totalExposures,
            'total_conversions' => $totalConversions,
            'overall_conversion_rate' => $totalExposures > 0
                ? round($totalConversions / $totalExposures, 6)
                : 0,
            'total_revenue' => round($totalRevenue, 2),
        ];
    }

    /**
     * Assess readiness for analysis (minimum sample size, runtime)
     */
    private function assessReadiness(Experiment $experiment): array
    {
        $conversions = $experiment->conversions()->get();
        $totalExposures = $conversions->sum('exposures');

        $minimumSample = $experiment->minimum_sample_size ?? 100;
        $minimumRuntime = $experiment->minimum_runtime_hours ?? 168; // 1 week default

        $now = now();
        $runtimeHours = $experiment->started_at ? $now->diffInHours($experiment->started_at) : 0;
        $minimumRuntimeMet = $runtimeHours >= $minimumRuntime;
        $minimumSampleMet = $totalExposures >= $minimumSample;

        return [
            'minimum_sample_size' => $minimumSample,
            'current_sample_size' => $totalExposures,
            'minimum_sample_met' => $minimumSampleMet,
            'minimum_runtime_hours' => $minimumRuntime,
            'current_runtime_hours' => $runtimeHours,
            'minimum_runtime_met' => $minimumRuntimeMet,
            'ready_for_analysis' => $minimumSampleMet && $minimumRuntimeMet,
        ];
    }
}
