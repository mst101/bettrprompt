<?php

namespace App\Console\Commands;

use App\Jobs\BuildAnalyticsDailyStats;
use Carbon\Carbon;
use Illuminate\Console\Command;

class BackfillDailyAnalytics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'analytics:backfill-daily {--from=} {--to=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfill daily analytics aggregations for a date range';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Parse and validate dates
        try {
            $from = $this->option('from') ? Carbon::parse($this->option('from')) : now()->subDay()->startOfDay();
            $to = $this->option('to') ? Carbon::parse($this->option('to')) : now()->subDay()->startOfDay();
        } catch (\Exception $e) {
            $this->error("Invalid date format: {$e->getMessage()}");

            return self::FAILURE;
        }

        // Validate date range
        if ($from->isAfter($to)) {
            $this->error('--from date must be before or equal to --to date');

            return self::FAILURE;
        }

        $this->info("Backfilling daily analytics from {$from->toDateString()} to {$to->toDateString()}");

        $dayCount = $from->diffInDays($to) + 1;
        $bar = $this->output->createProgressBar($dayCount);
        $bar->start();

        // Process each day in the range
        for ($date = $from->copy(); $date <= $to; $date->addDay()) {
            BuildAnalyticsDailyStats::dispatchSync($date);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Backfill complete');

        return self::SUCCESS;
    }
}
