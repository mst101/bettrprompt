<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Log;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Process Mailgun events hourly
        $schedule->command('mailgun:process-events')->hourly();

        // Update GeoIP database every Monday at 2:00 AM
        $schedule->command('geoip:update')
            ->weekly()
            ->mondays()
            ->at('02:00')
            ->withoutOverlapping()
            ->onSuccess(function () {
                Log::info('GeoIP database updated successfully via scheduler');
            })
            ->onFailure(function () {
                Log::error('GeoIP database update failed');
            });

        // Aggregate funnel statistics daily at 1:00 AM UTC
        $schedule->command('analytics:aggregate-funnel-stats')
            ->daily()
            ->at('01:00')
            ->withoutOverlapping()
            ->onSuccess(function () {
                Log::info('Funnel statistics aggregated successfully');
            })
            ->onFailure(function () {
                Log::error('Failed to aggregate funnel statistics');
            });

        // Aggregate all analytics daily stats at 1:30 AM UTC
        // Runs after funnel stats to ensure proper ordering
        $schedule->command('analytics:build-daily-aggregates')
            ->daily()
            ->at('01:30')
            ->withoutOverlapping()
            ->onSuccess(function () {
                Log::info('Daily analytics aggregates built successfully');
            })
            ->onFailure(function () {
                Log::error('Failed to build daily analytics aggregates');
            });

        // Reset monthly prompt counts for free tier users on 1st of month at 00:00 UTC
        $schedule->command('prompts:reset-monthly-counts')
            ->monthlyOn(1, '00:00')
            ->withoutOverlapping()
            ->onSuccess(function () {
                Log::info('Monthly prompt counts reset successfully');
            })
            ->onFailure(function () {
                Log::error('Failed to reset monthly prompt counts');
            });

        // Data Retention: Visitor cleanup (Tier 2 and Tier 1) on 1st of month at 02:30 UTC
        $schedule->command('visitors:cleanup')
            ->monthlyOn(1, '02:30')
            ->withoutOverlapping()
            ->runInBackground()
            ->onSuccess(function () {
                Log::info('Visitor cleanup completed successfully');
            })
            ->onFailure(function () {
                Log::error('Visitor cleanup failed');
            });

        // Data Retention: Analytics events pruning (>6 months) on 15th of month at 03:00 UTC
        $schedule->command('analytics:prune-events')
            ->monthlyOn(15, '03:00')
            ->withoutOverlapping()
            ->runInBackground()
            ->onSuccess(function () {
                Log::info('Analytics events pruning completed successfully');
            })
            ->onFailure(function () {
                Log::error('Analytics events pruning failed');
            });

        // Data Retention: Analytics sessions pruning (>25 months) on 1st of month at 03:30 UTC
        $schedule->command('analytics:prune-sessions')
            ->monthlyOn(1, '03:30')
            ->withoutOverlapping()
            ->runInBackground()
            ->onSuccess(function () {
                Log::info('Analytics sessions pruning completed successfully');
            })
            ->onFailure(function () {
                Log::error('Analytics sessions pruning failed');
            });

        // Data Retention: Experiment history pruning (>365 days post-end) on 1st of month at 04:00 UTC
        $schedule->command('experiments:prune-history --grace-days=365')
            ->monthlyOn(1, '04:00')
            ->withoutOverlapping()
            ->runInBackground()
            ->onSuccess(function () {
                Log::info('Experiment history pruning completed successfully');
            })
            ->onFailure(function () {
                Log::error('Experiment history pruning failed');
            });

        // Data Retention: Funnel progress pruning on 15th of month at 04:00 UTC
        $schedule->command('funnels:prune-progress')
            ->monthlyOn(15, '04:00')
            ->withoutOverlapping()
            ->runInBackground()
            ->onSuccess(function () {
                Log::info('Funnel progress pruning completed successfully');
            })
            ->onFailure(function () {
                Log::error('Funnel progress pruning failed');
            });

        // Privacy Enhancement: IP address scrubbing (>30 days) every Friday at 05:00 UTC
        $schedule->command('visitors:scrub-ip-addresses')
            ->weekly()
            ->fridays()
            ->at('05:00')
            ->withoutOverlapping()
            ->onSuccess(function () {
                Log::info('IP address scrubbing completed successfully');
            })
            ->onFailure(function () {
                Log::error('IP address scrubbing failed');
            });
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
    }
}
