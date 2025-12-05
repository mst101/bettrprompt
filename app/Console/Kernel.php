<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        // Core Commands
        \App\Console\Commands\HiddenGambia\Core\ClearSettingsCacheCommand::class,

        // Marketing Commands
        \App\Console\Commands\HiddenGambia\Marketing\SendCampaignCommand::class,
        \App\Console\Commands\HiddenGambia\Marketing\ProcessScheduledCampaignsCommand::class,

        // TypeScript Commands
        \App\Console\Commands\HiddenGambia\TypeScript\GenerateDefinitionsCommand::class,

        // Resource Commands
        \App\Console\Commands\HiddenGambia\Resources\MakeCommand::class,

        // App Commands
        \App\Console\Commands\HiddenGambia\App\MakeAppCommand::class,

        // Magellan Commands
        \App\Console\Commands\Magellan\TestMagellanCommand::class,
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Process scheduled campaigns every 10 minutes
        $schedule->command('hg:marketing:process-scheduled-campaigns')->everyTenMinutes();

        // Process Mailgun events hourly
        $schedule->command('mailgun:process-events')->hourly();

        // Update GeoIP database every Monday at 2:00 AM
        $schedule->command('geoip:update')
            ->weekly()
            ->mondays()
            ->at('02:00')
            ->withoutOverlapping()
            ->onSuccess(function () {
                \Illuminate\Support\Facades\Log::info('GeoIP database updated successfully via scheduler');
            })
            ->onFailure(function () {
                \Illuminate\Support\Facades\Log::error('GeoIP database update failed');
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
