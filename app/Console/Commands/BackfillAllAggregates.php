<?php

namespace App\Console\Commands;

use App\Jobs\AggregateFunnelDailyStats;
use App\Jobs\BuildDailyAnalyticsAggregates;
use Carbon\Carbon;
use Illuminate\Console\Command;

class BackfillAllAggregates extends Command
{
    protected $signature = 'analytics:backfill-all {--from=} {--to=}';

    protected $description = 'Backfill all daily aggregates (analytics, framework, question, workflow, funnel) for a date range';

    public function handle(): int
    {
        try {
            $from = $this->option('from') ? Carbon::parse($this->option('from')) : now()->subDay()->startOfDay();
            $to = $this->option('to') ? Carbon::parse($this->option('to')) : now()->subDay()->startOfDay();
        } catch (\Exception $e) {
            $this->error("Invalid date format: {$e->getMessage()}");

            return self::FAILURE;
        }

        if ($from->isAfter($to)) {
            $this->error('--from date must be before or equal to --to date');

            return self::FAILURE;
        }

        $this->info("Backfilling ALL aggregates from {$from->toDateString()} to {$to->toDateString()}");

        $dayCount = $from->diffInDays($to) + 1;
        $bar = $this->output->createProgressBar($dayCount * 5); // 5 aggregation jobs
        $bar->start();

        for ($date = $from->copy(); $date <= $to; $date->addDay()) {
            // Use orchestrator for analytics/framework/question/workflow (4 jobs)
            BuildDailyAnalyticsAggregates::dispatchSync($date);
            $bar->advance(4);

            // Funnel stats runs separately (1 job)
            AggregateFunnelDailyStats::dispatchSync($date);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('All aggregates backfilled successfully');

        return self::SUCCESS;
    }
}
