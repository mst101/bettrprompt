<?php

namespace App\Console\Commands;

use App\Jobs\AggregateFunnelDailyStats;
use Carbon\Carbon;
use Illuminate\Console\Command;

class AggregateFunnelStats extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'analytics:aggregate-funnel-stats {--date= : The date to aggregate (YYYY-MM-DD), defaults to yesterday}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Aggregate funnel progression data into daily statistics';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dateString = $this->option('date');
        $aggregationDate = $dateString
            ? Carbon::createFromFormat('Y-m-d', $dateString)->startOfDay()
            : now()->subDay()->startOfDay();

        $this->info("Aggregating funnel stats for {$aggregationDate->toDateString()}...");

        AggregateFunnelDailyStats::dispatch($aggregationDate);

        $this->info('Funnel aggregation job dispatched successfully.');

        return self::SUCCESS;
    }
}
