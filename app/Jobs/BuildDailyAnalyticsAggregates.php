<?php

namespace App\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class BuildDailyAnalyticsAggregates implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @param  Carbon|null  $date  Date to aggregate for (defaults to yesterday)
     */
    public function __construct(
        private ?Carbon $date = null,
    ) {
        $this->date = $this->date ?? now()->subDay()->startOfDay();
    }

    /**
     * Execute the job.
     *
     * Dispatches all daily aggregation jobs for a given date
     */
    public function handle(): void
    {
        try {
            Log::info('Starting daily analytics aggregation', [
                'date' => $this->date->toDateString(),
            ]);

            // Dispatch all aggregation jobs
            BuildFrameworkDailyStats::dispatch($this->date);
            BuildQuestionDailyStats::dispatch($this->date);
            BuildWorkflowDailyStats::dispatch($this->date);

            Log::info('Daily analytics aggregation jobs dispatched', [
                'date' => $this->date->toDateString(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to dispatch daily analytics aggregation jobs', [
                'date' => $this->date->toDateString(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
