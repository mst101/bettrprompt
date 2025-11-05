<?php

namespace App\Console\Commands\HiddenGambia\Core;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ClearSettingsCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hg:core:clear-settings-cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear the settings cache';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Clearing settings cache...');

        try {
            // Clear all settings-related cache
            Cache::forget(hgh_cache_key('settings'));
            Cache::forget(hgh_cache_key('settings.all'));

            // Clear any other settings-related cache keys
            $settingsKeys = Cache::get(hgh_cache_key('settings.keys'), []);
            foreach ($settingsKeys as $key) {
                Cache::forget(hgh_cache_key("settings.{$key}"));
            }

            Cache::forget(hgh_cache_key('settings.keys'));

            $this->info('Settings cache cleared successfully.');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Error clearing settings cache: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
