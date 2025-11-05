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
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
    }
}
