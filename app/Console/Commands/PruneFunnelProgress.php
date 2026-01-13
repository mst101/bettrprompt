<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PruneFunnelProgress extends Command
{
    protected $signature = 'funnels:prune-progress
                            {--dry-run}
                            {--buffer-days=180 : Days beyond max attribution window to retain}
                            {--batch=5000}';

    protected $description = 'Delete non-converted funnel progress older than attribution window';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $bufferDays = (int) $this->option('buffer-days');
        $batchSize = (int) $this->option('batch');

        // Find max attribution window across all funnels
        $maxWindow = DB::table('funnels')->max('attribution_window_days') ?? 90;
        $this->info("Max funnel attribution window: {$maxWindow} days");

        $totalRetention = $maxWindow + $bufferDays;
        $cutoffDate = Carbon::now()->subDays($totalRetention);
        $this->info("Pruning non-converted progress updated before: {$cutoffDate->toDateString()}");

        $query = DB::table('funnel_progress')
            ->where('is_converted', false)
            ->where('updated_at', '<', $cutoffDate);

        $count = $query->count();
        $this->info("Found {$count} stale funnel progress rows.");

        if ($dryRun) {
            $this->warn('DRY RUN: No deletion performed.');

            // Show breakdown by funnel
            $breakdown = DB::table('funnel_progress')
                ->join('funnels', 'funnel_progress.funnel_id', '=', 'funnels.id')
                ->select('funnels.name', DB::raw('COUNT(*) as count'))
                ->where('funnel_progress.is_converted', false)
                ->where('funnel_progress.updated_at', '<', $cutoffDate)
                ->groupBy('funnels.name')
                ->get();

            $this->table(['Funnel', 'Stale Count'], $breakdown->map(fn ($r) => [$r->name, $r->count]));

            return 0;
        }

        if ($count === 0) {
            return 0;
        }

        $bar = $this->output->createProgressBar($count);
        $deleted = 0;

        while (true) {
            $deletedBatch = (clone $query)->limit($batchSize)->delete();
            if ($deletedBatch === 0) {
                break;
            }

            $deleted += $deletedBatch;
            $bar->advance($deletedBatch);
        }

        $bar->finish();
        $this->newLine();
        $this->info("Deleted {$deleted} stale funnel progress rows.");

        return 0;
    }
}
