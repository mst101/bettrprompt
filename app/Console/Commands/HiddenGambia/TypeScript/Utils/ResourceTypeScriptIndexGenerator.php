<?php

namespace App\Console\Commands\HiddenGambia\TypeScript\Utils;

use Illuminate\Support\Facades\File;

class ResourceTypeScriptIndexGenerator
{
    /**
     * Generate an index file that exports all resource types.
     *
     * @param  string  $outputDirectory  The directory where resource TypeScript files are stored
     * @return string The path to the generated index file
     */
    public function generate(string $outputDirectory): string
    {
        // Ensure the output directory exists
        if (! File::isDirectory($outputDirectory)) {
            File::makeDirectory($outputDirectory, 0755, true);
        }

        // Get all TypeScript files in the directory (excluding index.ts)
        $files = collect(File::files($outputDirectory))
            ->filter(function ($file) {
                return $file->getExtension() === 'ts' && $file->getFilename() !== 'index.ts';
            })
            ->map(function ($file) {
                return $file->getFilenameWithoutExtension();
            });

        if ($files->isEmpty()) {
            // Create an empty index file
            $content = "// No resource types found\n";
        } else {
            // Generate simple export statements for each file
            $exports = $files->map(function ($name) {
                return "export * from './{$name}';";
            })->join("\n");

            $content = "// Auto-generated index file for resource types\n\n";
            $content .= $exports;
        }

        $content .= "\n";

        // Write the index file
        $indexPath = "{$outputDirectory}/index.ts";
        File::put($indexPath, $content);

        return $indexPath;
    }
}
