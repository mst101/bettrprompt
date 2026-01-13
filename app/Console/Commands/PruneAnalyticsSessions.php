<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PruneAnalyticsSessions extends Command
{
    protected $signature = 'analytics:prune-sessions
                            {--dry-run}
                            {--before= : Delete sessions before this date (defaults to 25 months ago)}
                            {--batch=5000}';

    protected $description = 'Delete completed analytics sessions older than 25 months';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $batchSize = (int) $this->option('batch');

        $beforeDate = $this->option('before')
            ? Carbon::parse($this->option('before'))
            : Carbon::now()->subMonths(25);

        $this->info("Pruning sessions ended before: {$beforeDate->toDateString()}");

        // Only prune completed sessions (ended_at is not null)
        $completedQuery = DB::table('analytics_sessions')
            ->whereNotNull('ended_at')
            ->where('ended_at', '<', $beforeDate);

        $completedCount = $completedQuery->count();
        $this->info("Found {$completedCount} completed sessions to prune.");

        // Also identify abandoned sessions (never ended, started >48 hours ago)
        $abandonedCutoff = Carbon::now()->subHours(48);
        $abandonedQuery = DB::table('analytics_sessions')
            ->whereNull('ended_at')
            ->where('started_at', '<', $abandonedCutoff);

        $abandonedCount = $abandonedQuery->count();
        $this->info("Found {$abandonedCount} abandoned sessions (never ended, started >48h ago).");

        if ($dryRun) {
            $this->warn('DRY RUN: No deletion performed.');

            return 0;
        }

        // Prune completed sessions
        if ($completedCount > 0) {
            $bar = $this->output->createProgressBar($completedCount);
            $deleted = 0;

            while (true) {
                $deletedBatch = (clone $completedQuery)->limit($batchSize)->delete();
                if ($deletedBatch === 0) {
                    break;
                }

                $deleted += $deletedBatch;
                $bar->advance($deletedBatch);
            }

            $bar->finish();
            $this->newLine();
            $this->info("Deleted {$deleted} completed sessions.");
        }

        // Prune abandoned sessions (separate operation)
        if ($abandonedCount > 0) {
            $this->info("\nPruning abandoned sessions...");
            $abandonedDeleted = 0;

            while (true) {
                $deletedBatch = (clone $abandonedQuery)->limit($batchSize)->delete();
                if ($deletedBatch === 0) {
                    break;
                }
                $abandonedDeleted += $deletedBatch;
            }

            $this->info("Deleted {$abandonedDeleted} abandoned sessions.");
        }

        return 0;
    }
}
