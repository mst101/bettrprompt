<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PruneExperimentHistory extends Command
{
    protected $signature = 'experiments:prune-history
                            {--dry-run}
                            {--grace-days=365 : Days after experiment end to retain raw data}
                            {--batch=5000}';

    protected $description = 'Delete raw experiment data for ended experiments past retention period';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $graceDays = (int) $this->option('grace-days');
        $batchSize = (int) $this->option('batch');

        $this->info("Finding experiments ended >{$graceDays} days ago...");

        // Find experiments eligible for pruning (ended_at + grace_days < now)
        $cutoffDate = Carbon::now()->subDays($graceDays);
        $eligibleExperiments = DB::table('experiments')
            ->select('id', 'name', 'ended_at')
            ->whereIn('status', ['completed', 'archived'])
            ->whereNotNull('ended_at')
            ->where('ended_at', '<', $cutoffDate)
            ->get();

        if ($eligibleExperiments->isEmpty()) {
            $this->info('No experiments eligible for pruning.');

            return 0;
        }

        $this->info("Found {$eligibleExperiments->count()} experiments eligible for pruning:");
        $this->table(
            ['ID', 'Name', 'Ended At'],
            $eligibleExperiments->map(fn ($e) => [
                $e->id,
                $e->name,
                $e->ended_at,
            ])
        );

        if ($dryRun) {
            $assignmentCount = DB::table('experiment_assignments')
                ->whereIn('experiment_id', $eligibleExperiments->pluck('id'))
                ->count();
            $exposureCount = DB::table('experiment_exposures')
                ->whereIn('experiment_id', $eligibleExperiments->pluck('id'))
                ->count();
            $eventExperimentCount = DB::table('analytics_event_experiments')
                ->whereIn('experiment_id', $eligibleExperiments->pluck('id'))
                ->count();

            $this->info("\nWould delete:");
            $this->info("- {$assignmentCount} assignments");
            $this->info("- {$exposureCount} exposures");
            $this->info("- {$eventExperimentCount} event-experiment links");
            $this->warn('DRY RUN: No deletion performed.');

            return 0;
        }

        // Delete in batches
        $experimentIds = $eligibleExperiments->pluck('id')->toArray();

        $this->info("\nDeleting assignments...");
        $this->deleteBatched('experiment_assignments', 'experiment_id', $experimentIds, $batchSize);

        $this->info('Deleting exposures...');
        $this->deleteBatched('experiment_exposures', 'experiment_id', $experimentIds, $batchSize);

        $this->info('Deleting event-experiment links...');
        $this->deleteBatched('analytics_event_experiments', 'experiment_id', $experimentIds, $batchSize);

        $this->info('Experiment history pruning complete.');

        return 0;
    }

    protected function deleteBatched(string $table, string $column, array $ids, int $batchSize): void
    {
        $total = 0;
        while (true) {
            $deleted = DB::table($table)
                ->whereIn($column, $ids)
                ->limit($batchSize)
                ->delete();

            if ($deleted === 0) {
                break;
            }
            $total += $deleted;
        }
        $this->info("Deleted {$total} rows from {$table}.");
    }
}
