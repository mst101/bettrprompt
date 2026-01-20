<?php

namespace App\Console\Commands;

use App\Jobs\BuildDailyAnalyticsAggregates;
use Carbon\Carbon;
use Illuminate\Console\Command;

class BuildDailyAggregates extends Command
{
    protected $signature = 'analytics:build-daily-aggregates {--date= : The date to aggregate (YYYY-MM-DD), defaults to yesterday}';

    protected $description = 'Build all daily analytics aggregates (orchestrator for analytics, framework, question, workflow stats)';

    public function handle(): int
    {
        $dateString = $this->option('date');
        $date = $dateString
            ? Carbon::createFromFormat('Y-m-d', $dateString)->startOfDay()
            : now()->subDay()->startOfDay();

        $this->info("Building all daily aggregates for {$date->toDateString()}...");

        BuildDailyAnalyticsAggregates::dispatchSync($date);

        $this->info('All daily aggregates built successfully.');

        return self::SUCCESS;
    }
}
