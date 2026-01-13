<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PruneAnalyticsEvents extends Command
{
    protected $signature = 'analytics:prune-events
                            {--dry-run : Show counts without deleting}
                            {--before= : Delete events before this date (YYYY-MM-DD, defaults to 6 months ago)}
                            {--batch=10000 : Batch size for deletion}';

    protected $description = 'Delete analytics events older than retention period';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $batchSize = (int) $this->option('batch');

        // Default: 6 months ago
        $beforeDate = $this->option('before')
            ? Carbon::parse($this->option('before'))
            : Carbon::now()->subMonths(6);

        $this->info("Pruning analytics_events before: {$beforeDate->toDateString()}");

        $query = DB::table('analytics_events')
            ->where('occurred_at', '<', $beforeDate);

        $count = $query->count();
        $this->info("Found {$count} events to prune.");

        if ($dryRun) {
            $this->warn('DRY RUN: No deletion performed.');

            // Show breakdown by event type
            $breakdown = DB::table('analytics_events')
                ->select('type', DB::raw('COUNT(*) as count'))
                ->where('occurred_at', '<', $beforeDate)
                ->groupBy('type')
                ->get();

            $this->table(['Type', 'Count'], $breakdown->map(fn ($row) => [$row->type, $row->count]));

            return 0;
        }

        if ($count === 0) {
            $this->info('No events to prune.');

            return 0;
        }

        $bar = $this->output->createProgressBar($count);
        $deleted = 0;

        // Delete in batches (CASCADE will handle analytics_event_experiments)
        while (true) {
            $deletedBatch = DB::table('analytics_events')
                ->where('occurred_at', '<', $beforeDate)
                ->limit($batchSize)
                ->delete();

            if ($deletedBatch === 0) {
                break;
            }

            $deleted += $deletedBatch;
            $bar->advance($deletedBatch);
        }

        $bar->finish();
        $this->newLine();
        $this->info("Deleted {$deleted} events.");

        // Log for observability
        Log::info('Analytics events pruned', [
            'count' => $deleted,
            'before_date' => $beforeDate->toDateString(),
        ]);

        return 0;
    }
}
