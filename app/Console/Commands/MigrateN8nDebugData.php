<?php

namespace App\Console\Commands;

use App\Services\WorkflowVariantService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MigrateN8nDebugData extends Command
{
    protected $signature = 'n8n:migrate-debug-data {--workflow= : Specific workflow number} {--force : Skip confirmation}';

    protected $description = 'Migrate legacy n8n debug data to variant structure';

    public function handle(WorkflowVariantService $variantService)
    {
        $this->info('Migrating n8n debug data to variant structure...');

        $workflowNumbers = [];
        if ($this->option('workflow')) {
            $workflowNumbers = [(int) $this->option('workflow')];
        } else {
            // Auto-detect which workflows have legacy data
            $workflowNumbers = $this->detectWorkflowsWithLegacyData();
        }

        if (empty($workflowNumbers)) {
            $this->info('No legacy debug data found. Migration not needed.');

            return 0;
        }

        $this->info('Found legacy data for workflows: '.implode(', ', $workflowNumbers));

        if (! $this->option('force') && ! $this->confirm('Proceed with migration?')) {
            $this->info('Migration cancelled.');

            return 0;
        }

        foreach ($workflowNumbers as $workflowNumber) {
            $this->migrateWorkflow($workflowNumber, $variantService);
        }

        $this->info('Migration completed successfully!');

        return 0;
    }

    /**
     * Detect which workflows have legacy debug data
     */
    private function detectWorkflowsWithLegacyData(): array
    {
        $workflowNumbers = [0, 1, 2];
        $hasData = [];

        foreach ($workflowNumbers as $num) {
            $legacyPath = storage_path('app/n8n_debug/javascript/');
            if (is_dir($legacyPath)) {
                // Check if there are any files for this workflow
                $files = glob($legacyPath."**/workflow_{$num}_*.js");
                if (! empty($files)) {
                    $hasData[] = $num;
                }
            }
        }

        return $hasData;
    }

    /**
     * Migrate a single workflow's debug data
     */
    private function migrateWorkflow(int $workflowNumber, WorkflowVariantService $variantService): void
    {
        $this->line("Migrating workflow {$workflowNumber}...");

        $config = config('n8n_variants');
        if (! isset($config[$workflowNumber])) {
            $this->warn("  No variant configuration found for workflow {$workflowNumber}");

            return;
        }

        $defaultVariant = $config[$workflowNumber]['default'];
        $baseLegacyPath = storage_path('app/n8n_debug/');

        // Migrate JavaScript files
        $this->migrateDirectory($workflowNumber, $defaultVariant, $baseLegacyPath, 'javascript', $variantService);

        // Migrate prompt files
        $this->migrateDirectory($workflowNumber, $defaultVariant, $baseLegacyPath, 'prompt', $variantService);

        // Migrate output files
        $this->migrateDirectory($workflowNumber, $defaultVariant, $baseLegacyPath, 'output', $variantService);

        $this->line("  ✓ Workflow {$workflowNumber} migrated to '{$defaultVariant}' variant");
    }

    /**
     * Migrate files in a specific directory
     */
    private function migrateDirectory(
        int $workflowNumber,
        string $variant,
        string $basePath,
        string $type,
        WorkflowVariantService $variantService
    ): void {
        foreach (['old', 'new'] as $version) {
            $sourcePath = $basePath."{$type}/{$version}/";
            if (! is_dir($sourcePath)) {
                continue;
            }

            // Find all files matching this workflow
            $pattern = $sourcePath."workflow_{$workflowNumber}_*";
            $files = glob($pattern);

            foreach ($files as $sourceFile) {
                if (! is_file($sourceFile)) {
                    continue;
                }

                $fileName = basename($sourceFile);
                $destPath = $variantService->getVariantStoragePath($workflowNumber, $variant, "{$type}/{$version}");

                // Create destination directory if it doesn't exist
                if (! is_dir($destPath)) {
                    mkdir($destPath, 0755, true);
                }

                $destFile = $destPath.$fileName;

                // Copy the file (don't move to preserve original)
                if (copy($sourceFile, $destFile)) {
                    chmod($destFile, 0644);
                    $this->line("    ✓ Copied {$fileName}");
                } else {
                    $this->error("    ✗ Failed to copy {$fileName}");
                }
            }
        }
    }
}
