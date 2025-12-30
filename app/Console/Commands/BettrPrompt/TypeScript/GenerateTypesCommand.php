<?php

namespace App\Console\Commands\BettrPrompt\TypeScript;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerateTypesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bp:types:generate
                            {resource? : Specific resource to generate TypeScript for (e.g., UserResource)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate TypeScript definitions from Laravel Resource docblocks';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $specificResource = $this->argument('resource');

        if ($specificResource) {
            return $this->generateSingleResource($specificResource);
        }

        return $this->generateAllResources();
    }

    /**
     * Generate TypeScript for all resources.
     */
    protected function generateAllResources(): int
    {
        $this->info('Generating TypeScript definitions for all resources...');

        $resourceFiles = $this->getResourceFiles();
        $outputDir = resource_path('js/Types/resources');

        // Create output directory if needed
        if (! File::exists($outputDir)) {
            File::makeDirectory($outputDir, 0755, true);
        }

        $generated = 0;
        $skipped = 0;

        foreach ($resourceFiles as $resourceFile) {
            $resourceName = pathinfo($resourceFile, PATHINFO_FILENAME);

            if ($this->generateTypeScriptFile($resourceFile, $resourceName, $outputDir)) {
                $generated++;
            } else {
                $skipped++;
            }
        }

        $this->info("✅ Generated {$generated} TypeScript definitions");

        if ($skipped > 0) {
            $this->warn("⚠️  Skipped {$skipped} resources (no TypeScript interface found in docblock)");
        }

        // Generate index file
        $this->generateIndexFile($outputDir);

        return 0;
    }

    /**
     * Generate TypeScript for a single resource.
     */
    protected function generateSingleResource(string $resourceName): int
    {
        // Ensure resource name ends with 'Resource'
        if (! str_ends_with($resourceName, 'Resource')) {
            $resourceName .= 'Resource';
        }

        $resourceFile = app_path("Http/Resources/{$resourceName}.php");

        if (! File::exists($resourceFile)) {
            $this->error("Resource file not found: {$resourceFile}");

            return 1;
        }

        $outputDir = resource_path('js/Types/resources');

        if (! File::exists($outputDir)) {
            File::makeDirectory($outputDir, 0755, true);
        }

        if ($this->generateTypeScriptFile($resourceFile, $resourceName, $outputDir)) {
            $this->info("✅ Generated TypeScript definition for {$resourceName}");

            return 0;
        }

        $this->error("Failed to generate TypeScript for {$resourceName}");

        return 1;
    }

    /**
     * Generate a TypeScript file from a resource docblock.
     */
    protected function generateTypeScriptFile(string $resourceFile, string $resourceName, string $outputDir): bool
    {
        $fileContent = file_get_contents($resourceFile);

        // Extract TypeScript interface from docblock (handle asterisks in comments)
        if (! preg_match('/```typescript\s*(.*?)```/s', $fileContent, $codeBlockMatches)) {
            $this->line("  - Skipped {$resourceName} (no TypeScript interface in docblock)");

            return false;
        }

        // Remove leading asterisks and spaces from each line
        $interfaceCode = preg_replace('/^\s*\*\s?/m', '', $codeBlockMatches[1]);
        $interfaceCode = trim($interfaceCode);

        // Extract import dependencies
        $imports = $this->extractImports($interfaceCode, $resourceName);

        // Build the TypeScript file content
        $content = "/**\n";
        $content .= " * TypeScript definition for {$resourceName}\n";
        $content .= " * Auto-generated from Resource docblock by bp:types:generate\n";
        $content .= " */\n\n";

        if (! empty($imports)) {
            $content .= 'import type { '.implode(', ', $imports)." } from '@/Types';\n\n";
        }

        // Replace interface name with resource name
        $interfaceCode = preg_replace('/interface\s+\w+/', "export interface {$resourceName}", $interfaceCode);

        $content .= $interfaceCode."\n";

        // Write the file
        $outputFile = "{$outputDir}/{$resourceName}.ts";
        File::put($outputFile, $content);

        $this->line("  - Generated {$resourceName}.ts");

        return true;
    }

    /**
     * Extract import dependencies from interface code.
     */
    protected function extractImports(string $interfaceCode, string $currentResource): array
    {
        $imports = [];

        // Match types that look like resources (e.g., UserResource, PromptRunResource)
        if (preg_match_all('/:\s*([A-Z]\w*Resource)(?:\[\]|\s*\||;)/', $interfaceCode, $matches)) {
            foreach ($matches[1] as $type) {
                // Skip self-references
                if ($type !== $currentResource) {
                    $imports[] = $type;
                }
            }
        }

        return array_unique($imports);
    }

    /**
     * Generate index.ts file that exports all resources.
     */
    protected function generateIndexFile(string $outputDir): void
    {
        $resourceFiles = File::files($outputDir);
        $exports = [];

        foreach ($resourceFiles as $file) {
            $filename = $file->getFilenameWithoutExtension();

            // Skip index file itself
            if ($filename === 'index') {
                continue;
            }

            $exports[] = "export * from './{$filename}';";
        }

        sort($exports);

        $content = "/**\n";
        $content .= " * Resource TypeScript definitions index\n";
        $content .= " * Auto-generated by bp:types:generate\n";
        $content .= " */\n\n";
        $content .= implode("\n", $exports)."\n";

        File::put("{$outputDir}/index.ts", $content);

        $this->line('  - Generated index.ts');
    }

    /**
     * Get all resource files.
     */
    protected function getResourceFiles(): array
    {
        $resourcesPath = app_path('Http/Resources');

        if (! File::exists($resourcesPath)) {
            return [];
        }

        $files = [];

        foreach (File::allFiles($resourcesPath) as $file) {
            if ($file->getExtension() === 'php' && str_ends_with($file->getFilename(), 'Resource.php')) {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }
}
