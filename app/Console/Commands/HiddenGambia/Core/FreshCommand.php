<?php

namespace App\Console\Commands\HiddenGambia\Core;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Redis;

class FreshCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hg:fresh 
                            {stores?* : Redis stores to flush (e.g., analytics, cache, default). If none specified, all stores are flushed}
                            {--force : Force the operation to run in production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset database with fresh migration and seeders, and flush specified Redis stores';

    /**
     * Available Redis connections from config/database.php
     */
    protected array $availableStores = [
        'default',
        'cache',
        'analytics',
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Check if running in production without force flag
        if ($this->laravel->environment('production') && ! $this->option('force')) {
            $this->error('This command will delete all data! Use --force to run in production.');

            return 1;
        }

        $this->info('🔄 Starting database and Redis reset...');
        $this->newLine();

        // Determine which Redis stores to flush
        $storesToFlush = $this->getStoresToFlush();

        // Build confirmation message
        $confirmMessage = 'This will delete ALL data in the database';
        if (! empty($storesToFlush)) {
            $confirmMessage .= ' and Redis store(s): '.implode(', ', $storesToFlush);
        }
        $confirmMessage .= '. Continue?';

        // Confirm the action (skip if no interaction)
        if (! $this->option('no-interaction') && ! $this->confirm($confirmMessage)) {
            $this->info('Operation cancelled.');

            return 0;
        }

        // Run database migration with seeders
        $this->info('📊 Running database migration with seeders...');

        try {
            Artisan::call('migrate:fresh', [
                '--seed' => true,
                '--force' => true, // Force in production
            ], $this->output);

            $this->info('✅ Database migration completed successfully');
        } catch (\Exception $e) {
            $this->error('❌ Database migration failed: '.$e->getMessage());

            return 1;
        }

        $this->newLine();

        // Clear logs
        $this->info('🧹 Clearing logs...');
        try {
            Artisan::call('hg:clear-logs', [], $this->output);
            $this->info('✅ Logs cleared successfully');
        } catch (\Exception $e) {
            $this->error('❌ Failed to clear logs: '.$e->getMessage());
            // Continue even if log clearing fails
        }

        $this->newLine();

        // Flush Redis stores
        if (! empty($storesToFlush)) {
            $this->info('🗑️  Flushing Redis stores...');

            foreach ($storesToFlush as $store) {
                try {
                    Redis::connection($store)->flushdb();
                    $this->info("✅ Redis store '{$store}' flushed successfully");
                } catch (\Exception $e) {
                    $this->error("❌ Failed to flush Redis store '{$store}': ".$e->getMessage());
                    // Continue with other stores even if one fails
                }
            }

            $this->newLine();

            // Show Redis statistics
            $this->showRedisStats();
        }

        $this->newLine();
        $this->info('🎉 Database and Redis reset completed!');

        return 0;
    }

    /**
     * Determine which Redis stores to flush based on arguments.
     */
    protected function getStoresToFlush(): array
    {
        $requestedStores = $this->argument('stores');

        // If no arguments provided, flush all stores
        if (empty($requestedStores)) {
            return $this->availableStores;
        }

        // Validate and filter requested stores
        $validStores = [];
        $invalidStores = [];

        foreach ($requestedStores as $store) {
            if (in_array($store, $this->availableStores)) {
                // Avoid duplicates
                if (! in_array($store, $validStores)) {
                    $validStores[] = $store;
                }
            } else {
                $invalidStores[] = $store;
            }
        }

        // Warn about invalid stores
        if (! empty($invalidStores)) {
            $this->warn('⚠️  Ignoring invalid Redis store(s): '.implode(', ', $invalidStores));
            $this->info('Available stores are: '.implode(', ', $this->availableStores));
            $this->newLine();
        }

        return $validStores;
    }

    /**
     * Display Redis statistics for all connections.
     */
    protected function showRedisStats(): void
    {
        $this->info('📈 Redis Statistics:');

        $stats = [];
        foreach ($this->availableStores as $store) {
            try {
                $size = Redis::connection($store)->dbsize();
                $stats[] = [ucfirst($store), $size];
            } catch (\Exception $e) {
                $stats[] = [ucfirst($store), 'Error: '.$e->getMessage()];
            }
        }

        $this->table(['Connection', 'Keys'], $stats);
    }
}
