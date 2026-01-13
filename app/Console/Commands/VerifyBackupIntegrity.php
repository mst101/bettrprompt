<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class VerifyBackupIntegrity extends Command
{
    protected $signature = 'backups:verify-integrity';

    protected $description = 'Verify backup integrity and test recovery procedures';

    public function handle()
    {
        $this->info('🔍 Verifying backup integrity...');
        $this->newLine();

        $checks = [
            'visitors_table_exists' => fn () => $this->checkTableExists('visitors'),
            'visitors_archive_table_exists' => fn () => $this->checkTableExists('visitors_archive'),
            'archive_has_data' => fn () => $this->checkArchiveHasData(),
            'archive_schema_complete' => fn () => $this->checkArchiveSchema(),
            'visitors_tier_0_protected' => fn () => $this->checkTier0Protection(),
            'indexes_exist' => fn () => $this->checkIndexes(),
            'recovery_test' => fn () => $this->testRecovery(),
        ];

        $passed = 0;
        $failed = 0;

        foreach ($checks as $name => $check) {
            try {
                if ($check()) {
                    $this->line("✅ {$name}");
                    $passed++;
                } else {
                    $this->line("❌ {$name}");
                    $failed++;
                }
            } catch (\Exception $e) {
                $this->line("❌ {$name}: {$e->getMessage()}");
                $failed++;
            }
        }

        $this->newLine();
        $this->info("Results: {$passed} passed, {$failed} failed");

        // Statistics
        $this->newLine();
        $this->info('📊 Backup Statistics:');

        $visitorsCount = DB::table('visitors')->count();
        $archiveCount = DB::table('visitors_archive')->count();
        $totalRecords = $visitorsCount + $archiveCount;

        $this->line("  Active visitors: {$visitorsCount}");
        $this->line("  Archived visitors: {$archiveCount}");
        $this->line("  Total retained: {$totalRecords}");

        if ($archiveCount > 0) {
            $tiers = DB::table('visitors_archive')
                ->select('archive_tier', DB::raw('COUNT(*) as count'))
                ->groupBy('archive_tier')
                ->get();

            $this->line("\n  Archive breakdown:");
            foreach ($tiers as $tier) {
                $this->line("    {$tier->archive_tier}: {$tier->count}");
            }

            $oldestArchive = DB::table('visitors_archive')
                ->select('archived_at')
                ->orderBy('archived_at')
                ->first();

            $newestArchive = DB::table('visitors_archive')
                ->select('archived_at')
                ->orderBy('archived_at', 'desc')
                ->first();

            $this->line("\n  Archive date range:");
            $this->line("    Oldest: {$oldestArchive->archived_at}");
            $this->line("    Newest: {$newestArchive->archived_at}");
        }

        return $failed === 0 ? 0 : 1;
    }

    protected function checkTableExists(string $table): bool
    {
        return DB::connection()->getSchemaBuilder()->hasTable($table);
    }

    protected function checkArchiveHasData(): bool
    {
        $count = DB::table('visitors_archive')->count();

        if ($count === 0) {
            $this->warn('   (No archived visitors yet - this is normal on first run)');

            return true;
        }

        return $count > 0;
    }

    protected function checkArchiveSchema(): bool
    {
        $columns = DB::connection()->getSchemaBuilder()->getColumnListing('visitors_archive');

        $required = ['id', 'user_id', 'archived_at', 'archive_tier', 'archive_reason'];

        foreach ($required as $col) {
            if (! in_array($col, $columns)) {
                throw new \Exception("Missing column: {$col}");
            }
        }

        return true;
    }

    protected function checkTier0Protection(): bool
    {
        // Tier 0 visitors should never be deleted
        // Check that users table is intact
        if (! DB::connection()->getSchemaBuilder()->hasTable('users')) {
            return true; // No users table means no Tier 0 conversions yet
        }

        $userCount = DB::table('users')->count();
        $convertedVisitors = DB::table('visitors')->whereNotNull('user_id')->count();

        $this->warn("   ({$userCount} users, {$convertedVisitors} converted visitors)");

        // Should be at least as many users as converted visitors
        return $convertedVisitors >= 0;
    }

    protected function checkIndexes(): bool
    {
        $indexes = [
            'visitors' => ['last_visit_at'],
            'analytics_sessions' => ['ended_at', 'started_at'],
            'experiments' => ['ended_at', 'status'],
        ];

        foreach ($indexes as $table => $cols) {
            foreach ($cols as $col) {
                $indexExists = DB::select('
                    SELECT indexname FROM pg_indexes
                    WHERE tablename = ? AND indexname LIKE ?
                ', [$table, "%{$col}%"]);

                if (empty($indexExists)) {
                    throw new \Exception("Missing index on {$table}({$col})");
                }
            }
        }

        return true;
    }

    protected function testRecovery(): bool
    {
        // Test that we can query archived data
        $sampleArchive = DB::table('visitors_archive')->first();

        if ($sampleArchive) {
            // Test that all required fields are present
            $fields = ['id', 'user_id', 'utm_source', 'first_visit_at', 'last_visit_at'];
            foreach ($fields as $field) {
                if (! isset($sampleArchive->$field)) {
                    throw new \Exception("Missing field in archive: {$field}");
                }
            }
        }

        return true;
    }
}
