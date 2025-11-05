<?php

namespace App\Console\Commands\HiddenGambia\Core;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ClearLogsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hg:clear-logs {logs?*}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear specified log files (laravel, horizon, reverb). If no logs specified, clears all three';

    /**
     * Available log types and their file paths
     *
     * @var array
     */
    protected $logFiles = [
        'laravel' => 'laravel.log',
        'horizon' => 'horizon.log',
        'reverb' => 'reverb.log',
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $requestedLogs = $this->argument('logs');

        // If no specific logs requested, clear all
        if (empty($requestedLogs)) {
            $requestedLogs = array_keys($this->logFiles);
        }

        $clearedLogs = [];
        $errors = [];

        foreach ($requestedLogs as $logType) {
            if (! isset($this->logFiles[$logType])) {
                $this->error("Unknown log type: {$logType}");
                $this->line('Available log types: '.implode(', ', array_keys($this->logFiles)));

                continue;
            }

            $logPath = storage_path('logs/'.$this->logFiles[$logType]);

            if ($this->clearLog($logPath)) {
                $clearedLogs[] = $logType;
            } else {
                $errors[] = $logType;
            }
        }

        // Display results
        if (! empty($clearedLogs)) {
            $this->info('✓ Successfully cleared logs: '.implode(', ', $clearedLogs));
        }

        if (! empty($errors)) {
            $this->error('✗ Failed to clear logs: '.implode(', ', $errors));
        }

        return empty($errors) ? Command::SUCCESS : Command::FAILURE;
    }

    /**
     * Clear a specific log file
     */
    protected function clearLog(string $logPath): bool
    {
        try {
            if (File::exists($logPath)) {
                // Get file size before clearing for info
                $fileSize = $this->formatBytes(File::size($logPath));

                // Clear the file by writing empty content
                File::put($logPath, '');

                $this->line("  - Cleared {$logPath} (was {$fileSize})");

                return true;
            } else {
                $this->warn("  - Log file not found: {$logPath}");

                return true; // Consider this a success (nothing to clear)
            }
        } catch (\Exception $e) {
            $this->error("  - Error clearing {$logPath}: ".$e->getMessage());

            return false;
        }
    }

    /**
     * Format bytes into human readable format
     */
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, $precision).' '.$units[$pow];
    }
}
