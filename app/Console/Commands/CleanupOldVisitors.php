<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupOldVisitors extends Command
{
    protected $signature = 'visitors:cleanup
                            {--dry-run : Show what would be deleted without deleting}
                            {--batch=5000 : Number of records to process per batch}
                            {--tier=all : Which tier to process: tier_1, tier_2, or all}';

    protected $description = 'Archive and delete stale visitor records based on retention tiers';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $batchSize = (int) $this->option('batch');
        $tier = $this->option('tier');

        $this->info('Starting visitor cleanup...');
        $this->info('Dry run: '.($dryRun ? 'YES' : 'NO'));

        // Always process Tier 2 first (most restrictive), then Tier 1
        if ($tier === 'all' || $tier === 'tier_2') {
            $this->processTier2($dryRun, $batchSize);
        }

        if ($tier === 'all' || $tier === 'tier_1') {
            $this->processTier1($dryRun, $batchSize);
        }

        $this->info('Cleanup completed.');

        return 0;
    }

    protected function processTier2(bool $dryRun, int $batchSize): void
    {
        $this->info("\n--- Processing Tier 2 (Low-Signal Visitors) ---");

        $cutoffDate = Carbon::now()->subMonths(6);
        $this->info("Cutoff date: {$cutoffDate->toDateString()}");

        // Build query for Tier 2 eligible visitors
        $query = DB::table('visitors')
            ->select('visitors.id')
            ->where('visitors.last_visit_at', '<', $cutoffDate)
            // Exclude Tier 0
            ->whereNull('visitors.user_id')
            ->whereNull('visitors.converted_at')
            ->whereNull('visitors.referred_by_user_id')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('prompt_runs')
                    ->whereColumn('prompt_runs.visitor_id', 'visitors.id');
            })
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('analytics_sessions')
                    ->whereColumn('analytics_sessions.visitor_id', 'visitors.id')
                    ->where('analytics_sessions.converted', true);
            })
            // Exclude Tier 1 (no attribution data)
            ->whereNull('visitors.utm_source')
            ->whereNull('visitors.utm_medium')
            ->whereNull('visitors.utm_campaign')
            ->whereNull('visitors.utm_term')
            ->whereNull('visitors.utm_content')
            ->whereNull('visitors.referrer')
            ->whereNull('visitors.landing_page')
            ->whereNull('visitors.personality_type')
            ->whereNull('visitors.location_detected_at')
            ->whereNull('visitors.currency_code')
            ->whereNull('visitors.language_code')
            // Has 0-1 sessions
            ->where(function ($q) {
                $q->whereNotExists(function ($subQ) {
                    $subQ->select(DB::raw(1))
                        ->from('analytics_sessions')
                        ->whereColumn('analytics_sessions.visitor_id', 'visitors.id')
                        ->havingRaw('COUNT(*) >= 2');
                });
            });

        $count = $query->count();
        $this->info("Found {$count} Tier 2 visitors eligible for archival.");

        if ($dryRun) {
            $this->warn('DRY RUN: No changes made.');

            return;
        }

        if ($count === 0) {
            return;
        }

        $bar = $this->output->createProgressBar($count);
        $processed = 0;

        while (true) {
            $visitorIds = (clone $query)
                ->limit($batchSize)
                ->pluck('id')
                ->toArray();

            if (empty($visitorIds)) {
                break;
            }

            DB::transaction(function () use ($visitorIds) {
                // Insert into archive
                DB::statement('
                    INSERT INTO visitors_archive (
                        id, user_id, utm_source, utm_medium, utm_campaign, utm_term, utm_content,
                        referrer, landing_page, user_agent, ip_address,
                        first_visit_at, last_visit_at, converted_at, referred_by_user_id,
                        personality_type, trait_percentages, ui_complexity, ui_step_number,
                        country_code, country_name, currency_code, language_code, location_detected_at,
                        created_at, updated_at,
                        archived_at, archive_tier, archive_reason
                    )
                    SELECT
                        id, user_id, utm_source, utm_medium, utm_campaign, utm_term, utm_content,
                        referrer, landing_page, user_agent, ip_address,
                        first_visit_at, last_visit_at, converted_at, referred_by_user_id,
                        personality_type, trait_percentages, ui_complexity, ui_step_number,
                        country_code, country_name, currency_code, language_code, location_detected_at,
                        created_at, updated_at,
                        NOW(), ?, ?
                    FROM visitors
                    WHERE id = ANY(?)
                ', ['tier_2', 'Low-signal visitor: 1 or fewer sessions, no attribution data, inactive >6 months', '{'.implode(',', $visitorIds).'}']);

                // Delete from main table
                DB::table('visitors')->whereIn('id', $visitorIds)->delete();
            });

            $processed += count($visitorIds);
            $bar->advance(count($visitorIds));
        }

        $bar->finish();
        $this->newLine();
        $this->info("Archived and deleted {$processed} Tier 2 visitors.");
    }

    protected function processTier1(bool $dryRun, int $batchSize): void
    {
        $this->info("\n--- Processing Tier 1 (Marketing Attribution Visitors) ---");

        $cutoffDate = Carbon::now()->subMonths(25);
        $this->info("Cutoff date: {$cutoffDate->toDateString()}");

        // Build query for Tier 1 eligible visitors
        $query = DB::table('visitors')
            ->select('visitors.id')
            ->where('visitors.last_visit_at', '<', $cutoffDate)
            // Exclude Tier 0
            ->whereNull('visitors.user_id')
            ->whereNull('visitors.converted_at')
            ->whereNull('visitors.referred_by_user_id')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('prompt_runs')
                    ->whereColumn('prompt_runs.visitor_id', 'visitors.id');
            })
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('analytics_sessions')
                    ->whereColumn('analytics_sessions.visitor_id', 'visitors.id')
                    ->where('analytics_sessions.converted', true);
            })
            // Must have Tier 1 characteristics (at least one)
            ->where(function ($q) {
                $q->whereNotNull('visitors.utm_source')
                    ->orWhereNotNull('visitors.utm_medium')
                    ->orWhereNotNull('visitors.utm_campaign')
                    ->orWhereNotNull('visitors.utm_term')
                    ->orWhereNotNull('visitors.utm_content')
                    ->orWhereNotNull('visitors.referrer')
                    ->orWhereNotNull('visitors.landing_page')
                    ->orWhereNotNull('visitors.personality_type')
                    ->orWhereNotNull('visitors.location_detected_at')
                    ->orWhereNotNull('visitors.currency_code')
                    ->orWhereNotNull('visitors.language_code')
                    ->orWhereExists(function ($subQ) {
                        $subQ->select(DB::raw(1))
                            ->from('analytics_sessions')
                            ->whereColumn('analytics_sessions.visitor_id', 'visitors.id')
                            ->havingRaw('COUNT(*) >= 2');
                    });
            });

        $count = $query->count();
        $this->info("Found {$count} Tier 1 visitors eligible for archival.");

        if ($dryRun) {
            $this->warn('DRY RUN: No changes made.');

            return;
        }

        if ($count === 0) {
            return;
        }

        $bar = $this->output->createProgressBar($count);
        $processed = 0;

        while (true) {
            $visitorIds = (clone $query)
                ->limit($batchSize)
                ->pluck('id')
                ->toArray();

            if (empty($visitorIds)) {
                break;
            }

            DB::transaction(function () use ($visitorIds) {
                // Insert into archive
                DB::statement('
                    INSERT INTO visitors_archive (
                        id, user_id, utm_source, utm_medium, utm_campaign, utm_term, utm_content,
                        referrer, landing_page, user_agent, ip_address,
                        first_visit_at, last_visit_at, converted_at, referred_by_user_id,
                        personality_type, trait_percentages, ui_complexity, ui_step_number,
                        country_code, country_name, currency_code, language_code, location_detected_at,
                        created_at, updated_at,
                        archived_at, archive_tier, archive_reason
                    )
                    SELECT
                        id, user_id, utm_source, utm_medium, utm_campaign, utm_term, utm_content,
                        referrer, landing_page, user_agent, ip_address,
                        first_visit_at, last_visit_at, converted_at, referred_by_user_id,
                        personality_type, trait_percentages, ui_complexity, ui_step_number,
                        country_code, country_name, currency_code, language_code, location_detected_at,
                        created_at, updated_at,
                        NOW(), ?, ?
                    FROM visitors
                    WHERE id = ANY(?)
                ', ['tier_1', 'Marketing attribution visitor: has UTM/referrer data, inactive >25 months', '{'.implode(',', $visitorIds).'}']);

                // Delete from main table
                DB::table('visitors')->whereIn('id', $visitorIds)->delete();
            });

            $processed += count($visitorIds);
            $bar->advance(count($visitorIds));
        }

        $bar->finish();
        $this->newLine();
        $this->info("Archived and deleted {$processed} Tier 1 visitors.");
    }
}
