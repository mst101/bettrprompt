<?php

namespace App\Console\Commands;

use App\Jobs\BuildWorkflowDailyStats;
use Carbon\Carbon;
use Illuminate\Console\Command;

class BackfillWorkflowStats extends Command
{
    protected $signature = 'analytics:backfill-workflow {--from=} {--to=}';

    protected $description = 'Backfill workflow daily statistics for a date range';

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

        $this->info("Backfilling workflow stats from {$from->toDateString()} to {$to->toDateString()}");

        $dayCount = $from->diffInDays($to) + 1;
        $bar = $this->output->createProgressBar($dayCount);
        $bar->start();

        for ($date = $from->copy(); $date <= $to; $date->addDay()) {
            BuildWorkflowDailyStats::dispatchSync($date);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Backfill complete');

        return self::SUCCESS;
    }
}
