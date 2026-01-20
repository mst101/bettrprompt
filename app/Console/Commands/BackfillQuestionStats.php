<?php

namespace App\Console\Commands;

use App\Jobs\BuildQuestionDailyStats;
use Carbon\Carbon;
use Illuminate\Console\Command;

class BackfillQuestionStats extends Command
{
    protected $signature = 'analytics:backfill-question {--from=} {--to=}';

    protected $description = 'Backfill question daily statistics for a date range';

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

        $this->info("Backfilling question stats from {$from->toDateString()} to {$to->toDateString()}");

        $dayCount = $from->diffInDays($to) + 1;
        $bar = $this->output->createProgressBar($dayCount);
        $bar->start();

        for ($date = $from->copy(); $date <= $to; $date->addDay()) {
            BuildQuestionDailyStats::dispatchSync($date);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Backfill complete');

        return self::SUCCESS;
    }
}
