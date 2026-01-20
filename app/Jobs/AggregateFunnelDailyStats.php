<?php

namespace App\Jobs;

use App\Models\Funnel;
use App\Models\FunnelDailyStats;
use App\Models\FunnelProgress;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class AggregateFunnelDailyStats implements ShouldQueue
{
    use Queueable;

    private Carbon $aggregationDate;

    /**
     * Create a new job instance.
     * If no date provided, aggregates for yesterday
     */
    public function __construct(?Carbon $aggregationDate = null)
    {
        $this->aggregationDate = $aggregationDate ?? now()->subDay()->startOfDay();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $funnels = Funnel::where('is_active', true)->with('stages')->get();

            foreach ($funnels as $funnel) {
                $this->aggregateFunnelStats($funnel);
            }

            Log::info('Funnel daily stats aggregated successfully', [
                'date' => $this->aggregationDate->toDateString(),
                'funnel_count' => $funnels->count(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to aggregate funnel daily stats', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Aggregate stats for a specific funnel
     */
    private function aggregateFunnelStats(Funnel $funnel): void
    {
        $stages = $funnel->stages()->orderBy('order')->get();
        $dateStart = $this->aggregationDate->startOfDay();
        $dateEnd = $this->aggregationDate->endOfDay();

        // Check if there's any funnel progress data for this date
        $hasData = FunnelProgress::where('funnel_id', $funnel->id)
            ->whereBetween('created_at', [$dateStart, $dateEnd])
            ->exists();

        if (! $hasData) {
            Log::info('No funnel progress data for date', [
                'date' => $this->aggregationDate->toDateString(),
                'funnel_id' => $funnel->id,
            ]);

            return;
        }

        foreach ($stages as $stage) {
            $starts = $this->countStageStarts($funnel->id, $stage->order, $dateStart, $dateEnd);
            $conversions = $this->countStageConversions($funnel->id, $stage->order, $dateStart, $dateEnd);
            $conversionRate = $starts > 0 ? ($conversions / $starts) * 100 : 0;

            // Upsert the daily stat
            FunnelDailyStats::updateOrCreate(
                [
                    'funnel_id' => $funnel->id,
                    'date' => $this->aggregationDate->toDateString(),
                    'stage' => $stage->order,
                ],
                [
                    'starts' => $starts,
                    'conversions' => $conversions,
                    'conversion_rate' => $conversionRate,
                ]
            );
        }
    }

    /**
     * Count how many visitors reached a specific stage on a given date
     */
    private function countStageStarts(int $funnelId, int $stage, Carbon $dateStart, Carbon $dateEnd): int
    {
        return FunnelProgress::where('funnel_id', $funnelId)
            ->where('stage', '>=', $stage)
            ->whereRaw(
                "CAST(stage_timestamps->?->>'$' AS TIMESTAMP) BETWEEN ? AND ?",
                [$stage, $dateStart, $dateEnd]
            )
            ->count();
    }

    /**
     * Count how many visitors progressed from current stage to next stage on a given date
     */
    private function countStageConversions(int $funnelId, int $stage, Carbon $dateStart, Carbon $dateEnd): int
    {
        $nextStage = $stage + 1;

        return FunnelProgress::where('funnel_id', $funnelId)
            ->where('stage', '>=', $nextStage)
            ->whereRaw(
                "CAST(stage_timestamps->?->>'$' AS TIMESTAMP) BETWEEN ? AND ?",
                [$nextStage, $dateStart, $dateEnd]
            )
            ->count();
    }
}
