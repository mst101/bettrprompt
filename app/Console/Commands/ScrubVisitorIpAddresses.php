<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ScrubVisitorIpAddresses extends Command
{
    protected $signature = 'visitors:scrub-ip-addresses
                            {--dry-run}
                            {--age-days=30 : Age in days before scrubbing}
                            {--batch=5000}';

    protected $description = 'Truncate IP addresses for visitors older than 30 days';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $ageDays = (int) $this->option('age-days');
        $batchSize = (int) $this->option('batch');

        $cutoffDate = Carbon::now()->subDays($ageDays);
        $this->info("Scrubbing IPs for visitors created before: {$cutoffDate->toDateString()}");

        $query = DB::table('visitors')
            ->whereNotNull('ip_address')
            ->where('first_visit_at', '<', $cutoffDate)
            ->where('ip_address', 'NOT LIKE', '%.0'); // Skip already truncated

        $count = $query->count();
        $this->info("Found {$count} visitors with full IP addresses.");

        if ($dryRun) {
            $this->warn('DRY RUN: No updates performed.');

            return 0;
        }

        if ($count === 0) {
            return 0;
        }

        $bar = $this->output->createProgressBar($count);
        $updated = 0;

        // PostgreSQL-specific IP truncation
        while (true) {
            $affected = DB::update('
                UPDATE visitors
                SET ip_address = host(network(inet(ip_address) & inet(?)))
                WHERE id IN (
                    SELECT id FROM visitors
                    WHERE ip_address IS NOT NULL
                      AND first_visit_at < ?
                      AND ip_address NOT LIKE ?
                    LIMIT ?
                )
            ', ['255.255.255.0', $cutoffDate, '%.0', $batchSize]);

            if ($affected === 0) {
                break;
            }

            $updated += $affected;
            $bar->advance($affected);
        }

        $bar->finish();
        $this->newLine();
        $this->info("Truncated {$updated} IP addresses.");

        return 0;
    }
}
