<?php

namespace App\Console;

use App\Console\Commands\HiddenGambia\App\MakeAppCommand;
use App\Console\Commands\HiddenGambia\Core\ClearSettingsCacheCommand;
use App\Console\Commands\HiddenGambia\Resources\MakeCommand;
use App\Console\Commands\HiddenGambia\TypeScript\GenerateDefinitionsCommand;
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
    protected $commands = [
        // Core Commands
        ClearSettingsCacheCommand::class,

        // TypeScript Commands
        GenerateDefinitionsCommand::class,

        // Resource Commands
        MakeCommand::class,

        // App Commands
        MakeAppCommand::class,

    ];

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
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
    }
}
