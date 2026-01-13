<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RestoreArchivedVisitor extends Command
{
    protected $signature = 'visitors:restore {visitor_id : UUID of archived visitor}';

    protected $description = 'Restore an archived visitor back to the main table';

    public function handle()
    {
        $visitorId = $this->argument('visitor_id');

        $archived = DB::table('visitors_archive')->where('id', $visitorId)->first();

        if (! $archived) {
            $this->error("Visitor {$visitorId} not found in archive.");

            return 1;
        }

        $this->info('Found archived visitor:');
        $this->line("  ID: {$archived->id}");
        $this->line("  Archived at: {$archived->archived_at}");
        $this->line("  Archive tier: {$archived->archive_tier}");
        $this->line("  Reason: {$archived->archive_reason}");

        if (! $this->confirm('Restore this visitor to the main table?')) {
            $this->info('Restore cancelled.');

            return 0;
        }

        try {
            DB::transaction(function () use ($archived) {
                // Remove archive metadata columns
                $data = (array) $archived;
                unset($data['archived_at'], $data['archive_tier'], $data['archive_reason']);

                // Restore to main table
                DB::table('visitors')->insert($data);
            });

            $this->info("✅ Restored visitor {$visitorId} to main table.");
            $this->warn('Note: Archive entry still exists. Delete manually if needed:');
            $this->line("  DELETE FROM visitors_archive WHERE id = '{$visitorId}';");

            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to restore visitor: {$e->getMessage()}");

            return 1;
        }
    }
}
