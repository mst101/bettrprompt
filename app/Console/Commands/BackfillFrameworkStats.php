<?php

namespace App\Console\Commands;

use App\Jobs\BuildFrameworkDailyStats;
use Carbon\Carbon;
use Illuminate\Console\Command;

class BackfillFrameworkStats extends Command
{
    protected $signature = 'analytics:backfill-framework {--from=} {--to=}';

    protected $description = 'Backfill framework daily statistics for a date range';

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

        $this->info("Backfilling framework stats from {$from->toDateString()} to {$to->toDateString()}");

        $dayCount = $from->diffInDays($to) + 1;
        $bar = $this->output->createProgressBar($dayCount);
        $bar->start();

        for ($date = $from->copy(); $date <= $to; $date->addDay()) {
            BuildFrameworkDailyStats::dispatchSync($date);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Backfill complete');

        return self::SUCCESS;
    }
}
