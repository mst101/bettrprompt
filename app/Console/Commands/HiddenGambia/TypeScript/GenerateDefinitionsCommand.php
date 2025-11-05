<?php

namespace App\Console\Commands\HiddenGambia\TypeScript;

use App\Console\Commands\HiddenGambia\TypeScript\Utils\ExternalTypeDefinitions;
use App\Console\Commands\HiddenGambia\TypeScript\Utils\ModelTypeScriptIndexGenerator;
use App\Console\Commands\HiddenGambia\TypeScript\Utils\ModelTypeScriptInterfaceExtractor;
use App\Console\Commands\HiddenGambia\TypeScript\Utils\ResourceTypeScriptIndexGenerator;
use App\Console\Commands\HiddenGambia\TypeScript\Utils\ResourceTypeScriptInterfaceExtractor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\Finder;

class GenerateDefinitionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hg:make:typescript
                            {models?* : The model classes to generate TypeScript for}
                            {--resource= : Generate TypeScript definition for a specific resource}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate TypeScript definitions for Laravel resources and models';

    /**
     * The resource extractor.
     *
     * @var ResourceTypeScriptInterfaceExtractor
     */
    protected $resourceExtractor;

    /**
     * The model extractor.
     *
     * @var ModelTypeScriptInterfaceExtractor
     */
    protected $modelExtractor;

    /**
     * Configure the command.
     *
     * @return void
     */
    protected function configure()
    {
        $this->setHelp('This command generates TypeScript definitions for Laravel resources and models.

It can generate definitions for all resources, all models, or both.
You can also specify specific models or a specific resource to generate definitions for.

Examples:
  <info>php artisan hg:make:typescript</info>                  Generate definitions for both resources and models
  <info>php artisan hg:make:typescript User Post</info>        Generate definitions for specific models
  <info>php artisan hg:make:typescript --resource=UserResource</info>  Generate definitions for a specific resource
');
    }

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(
        ResourceTypeScriptInterfaceExtractor $resourceExtractor,
        ModelTypeScriptInterfaceExtractor $modelExtractor
    ) {
        parent::__construct();
        $this->resourceExtractor = $resourceExtractor;
        $this->modelExtractor = $modelExtractor;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $models = $this->argument('models');
        $specificResource = $this->option('resource');

        // Generate definitions for a specific resource
        if ($specificResource) {
            $this->generateResourceDefinition($specificResource);

            return 0;
        }

        // Generate definitions for specific models
        if (! empty($models)) {
            $this->info('Generating TypeScript definitions for specified models...');

            foreach ($models as $model) {
                $this->generateModelDefinition($model);
            }

            return 0;
        }

        // If no specific resource or models are specified, generate both
        $this->info('Generating TypeScript definitions for all resources and models...');
        $this->generateResourceDefinitions();
        $this->generateModelDefinitions();

        return 0;
    }

    /**
     * Generate TypeScript definitions for all resources.
     *
     * @return void
     */
    protected function generateResourceDefinitions()
    {
        $this->info('Generating TypeScript definitions for resources...');

        // Get all resource files
        $resourceFiles = $this->getResourceFiles();
        $outputDir = resource_path('js/types/resources');

        // Create the output directory if it doesn't exist
        if (! File::exists($outputDir)) {
            File::makeDirectory($outputDir, 0755, true);
        }

        $generatedCount = 0;

        // Generate TypeScript definitions for each resource
        foreach ($resourceFiles as $resourceFile) {
            // Get the resource name from the file path
            $resourceName = pathinfo($resourceFile, PATHINFO_FILENAME);

            // First, try to extract the TypeScript interface directly from the file
            $fileContent = file_get_contents($resourceFile);

            // Debug: Check if the docblock contains a TypeScript interface
            if (strpos($fileContent, 'TypeScript interface') !== false) {
                $this->info('Found TypeScript interface in docblock');

                // Extract the docblock for debugging
                if (preg_match('/\/\*\*(.*?)\*\//s', $fileContent, $docblockMatches)) {
                    $docblock = $docblockMatches[1];
                    $this->info('Found docblock with length: '.strlen($docblock));

                    // Extract the TypeScript interface section
                    if (preg_match('/TypeScript interface:(.*?)```/s', $docblock, $interfaceHeaderMatches)) {
                        $this->info('Found TypeScript interface header');

                        // Extract the full interface including the code block
                        if (preg_match('/```typescript(.*?)```/s', $docblock, $codeBlockMatches)) {
                            $this->info('Found TypeScript code block with length: '.strlen($codeBlockMatches[1]));

                            // Extract the interface name and content
                            if (preg_match('/interface\s+(\w+)\s*\{(.*?)\}/s', $codeBlockMatches[1], $interfaceMatches)) {
                                $this->info('Successfully extracted interface: '.$interfaceMatches[1]);

                                $interfaceName = $interfaceMatches[1];
                                $interfaceContent = $interfaceMatches[2];

                                // Generate the TypeScript file
                                $content = "/**\n";
                                $content .= " * TypeScript definition for the {$resourceName}\n";
                                $content .= " * Auto-generated by hg:make:typescript\n";
                                $content .= " */\n\n";

                                // Extract imports from the interface content
                                $typesToImport = [];
                                // Match TypeScript types that should be imported (Resources and other interface references)
                                // Only match types that are used as actual types, not string literals in union types
                                if (preg_match_all('/(?::\s*|<\s*|readonly\s+)([A-Z]\w*Resource)(?:\[\]|\s*\|\s*null|\s*;|\s*\})/', $interfaceContent, $typeMatches)) {
                                    foreach ($typeMatches[1] as $typeName) {
                                        // Skip the current resource (self-reference check)
                                        if ($typeName === $resourceName || $typeName === str_replace('Resource', '', $resourceName)) {
                                            continue;
                                        }

                                        if (! in_array($typeName, $typesToImport)) {
                                            $typesToImport[] = $typeName;
                                        }
                                    }
                                }

                                // Add imports
                                if (! empty($typesToImport)) {
                                    $typesToImport = array_unique($typesToImport);
                                    sort($typesToImport);
                                    $content .= 'import type { '.implode(', ', $typesToImport)." } from '@/types';\n\n";
                                }

                                // Use the resource name for the interface name
                                $content .= "export interface {$resourceName} {\n";

                                // Clean up the interface content by removing asterisks and extra spaces
                                $lines = explode("\n", $interfaceContent);
                                $cleanedLines = [];
                                foreach ($lines as $line) {
                                    // Remove leading asterisks and spaces
                                    $cleanedLine = preg_replace('/^\s*\*\s*/', '', $line);
                                    // Ensure proper indentation (4 spaces)
                                    if (! empty($cleanedLine) && $cleanedLine[0] !== '/') {
                                        $cleanedLines[] = '    '.$cleanedLine;
                                    }
                                }
                                $content .= implode("\n", $cleanedLines)."\n";
                                $content .= "}\n";

                                // Write the TypeScript file
                                $outputFile = "{$outputDir}/{$resourceName}.ts";
                                File::put($outputFile, $content);
                                $this->line('Generated: '.str_replace(base_path(), '', $outputFile));
                                $generatedCount++;

                                continue;
                            } else {
                                $this->error('Could not extract interface name and content');
                            }
                        } else {
                            $this->error('Could not extract TypeScript code block');
                        }
                    } else {
                        $this->error('Could not extract TypeScript interface header');
                    }
                } else {
                    $this->error('Could not extract docblock');
                }
            } else {
                $this->error('No TypeScript interface found in docblock');
            }

            // If direct extraction failed, try to extract properties from the toArray method
            if (preg_match('/public\s+function\s+toArray\s*\([^)]*\)\s*:?\s*array\s*\{(.*?)return\s*\[(.*?)\]\s*;\s*\}/s', $fileContent, $matches)) {
                $toArrayContent = $matches[2];

                // Extract properties from the toArray method
                $properties = [];
                $propertyNames = []; // Track property names to avoid duplicates
                if (preg_match_all('/[\'"](\w+)[\'"]\s*=>\s*([^,]+),?/s', $toArrayContent, $propMatches, PREG_SET_ORDER)) {
                    foreach ($propMatches as $match) {
                        $propName = $match[1];
                        $propValue = trim($match[2]);

                        // Skip relationship properties that use whenLoaded
                        if (strpos($propValue, 'whenLoaded') !== false) {
                            continue;
                        }

                        // Convert to camelCase for TypeScript
                        $camelPropName = lcfirst(str_replace('_', '', ucwords($propName, '_')));

                        // Skip if we've already processed this property
                        if (in_array($camelPropName, $propertyNames)) {
                            continue;
                        }
                        $propertyNames[] = $camelPropName;

                        // Determine property type
                        $propType = 'any';
                        if ($propName === 'id') {
                            $propType = 'number';
                        } elseif (in_array($propName, ['name', 'title', 'description', 'slug', 'email'])) {
                            $propType = 'string';
                        } elseif (strpos($propValue, '$this->id') !== false) {
                            $propType = 'number';
                        } elseif (strpos($propValue, '$this->name') !== false ||
                                 strpos($propValue, '$this->title') !== false ||
                                 strpos($propValue, '$this->description') !== false) {
                            $propType = 'string';
                        } elseif (strpos($propValue, '?:') !== false || strpos($propValue, '? :') !== false) {
                            $propType = 'string | null';
                        } elseif (strpos($propValue, 'Carbon') !== false ||
                                 strpos($propValue, 'created_at') !== false ||
                                 strpos($propValue, 'updated_at') !== false ||
                                 preg_match('/_at$/', $propName)) {
                            $propType = 'string'; // ISO date string
                        }

                        $properties[] = "    readonly {$camelPropName}: {$propType};"; // 4-space indentation
                    }
                }

                // Extract relationships
                $relationships = [];
                $relatedTypes = [];
                if (preg_match_all('/[\'"](\w+)[\'"]\s*=>\s*\$this->whenLoaded\([\'"](\w+)[\'"],\s*function\s*\(\)\s*\{(.*?)\}\)/s', $fileContent, $relMatches, PREG_SET_ORDER)) {
                    foreach ($relMatches as $match) {
                        $relName = $match[1];
                        $relMethod = $match[2];
                        $relContent = $match[3];

                        // Convert to camelCase for TypeScript
                        $relName = lcfirst(str_replace('_', '', ucwords($relName, '_')));

                        // Determine relationship type
                        $relType = 'any';
                        $isArray = false;

                        if (preg_match('/new\s+(\w+)Resource/', $relContent, $typeMatch)) {
                            $relType = $typeMatch[1].'Resource';
                            $relatedTypes[] = $relType;
                        }

                        if (strpos($relContent, 'collect') !== false ||
                           strpos($relContent, 'map') !== false ||
                           strpos($relContent, '->all()') !== false) {
                            $isArray = true;
                            $relType .= '[]';
                        }

                        $relationships[] = "    readonly {$relName}?: {$relType} | null;"; // 4-space indentation
                    }
                }

                // Generate the TypeScript file
                $content = "/**\n";
                $content .= " * TypeScript definition for the {$resourceName}\n";
                $content .= " * Auto-generated by hg:make:typescript\n";
                $content .= " */\n\n";

                // Add imports
                $typesToImport = $relatedTypes;
                if (! empty($typesToImport)) {
                    $typesToImport = array_unique($typesToImport);
                    sort($typesToImport);
                    $content .= 'import type { '.implode(', ', $typesToImport)." } from '@/types';\n\n";
                }

                // Generate interface without extending ModelResource
                $content .= "export interface {$resourceName} {\n";
                if (! empty($properties)) {
                    $content .= implode("\n", $properties)."\n";
                }

                if (! empty($relationships)) {
                    if (! empty($properties)) {
                        $content .= "\n    // Relationships\n"; // 4-space indentation
                    }
                    $content .= implode("\n", $relationships)."\n";
                }

                $content .= "}\n";

                // Write the TypeScript file
                $outputFile = "{$outputDir}/{$resourceName}.ts";
                File::put($outputFile, $content);
                $this->line('Generated: '.str_replace(base_path(), '', $outputFile));
                $generatedCount++;

                continue;
            }

            // If all extraction methods failed, use the extractor
            $extractor = new ResourceTypeScriptExtractor($resourceFile);
            $content = $extractor->extract($resourceName);

            // Write the TypeScript file
            $outputFile = "{$outputDir}/{$resourceName}.ts";
            File::put($outputFile, $content);
            $this->line('Generated: '.str_replace(base_path(), '', $outputFile));
        }

        // Generate the index file
        $indexGenerator = new ResourceTypeScriptIndexGenerator;
        $indexGenerator->generate($outputDir);

        $this->info("Generated TypeScript definitions for {$generatedCount} resources.");
    }

    /**
     * Generate TypeScript definitions for all models.
     *
     * @return void
     */
    protected function generateModelDefinitions()
    {
        $this->info('Generating TypeScript definitions for models...');

        // Get all model classes
        $modelClasses = $this->getModelClasses();

        // Create the output directory if it doesn't exist
        $outputDir = resource_path('js/types/models');
        if (! File::exists($outputDir)) {
            File::makeDirectory($outputDir, 0755, true);
        }

        $count = 0;
        foreach ($modelClasses as $modelClass) {
            $this->generateModelDefinition($modelClass);
            $count++;
        }

        // Generate external type definitions
        $this->generateExternalTypeDefinitions($outputDir);

        // Generate the index file
        $indexGenerator = new ModelTypeScriptIndexGenerator;
        $indexGenerator->generate($outputDir);

        $this->info("Generated TypeScript definitions for {$count} models.");
    }

    /**
     * Generate TypeScript definition for a specific model.
     *
     * @return void
     */
    protected function generateModelDefinition(string $modelClass)
    {
        // If the model doesn't have the full namespace, add it
        if (strpos($modelClass, '\\') === false) {
            $modelClass = "App\\Models\\{$modelClass}";
        }

        try {
            // Check if the class exists
            if (! class_exists($modelClass)) {
                $this->error("Model class {$modelClass} does not exist.");

                return;
            }

            $modelName = class_basename($modelClass);

            // Extract TypeScript interface from the model
            $typeScriptContent = $this->modelExtractor->extract($modelClass);

            // Write the TypeScript interface to a file
            $outputDir = resource_path('js/types/models');
            if (! File::exists($outputDir)) {
                File::makeDirectory($outputDir, 0755, true);
            }

            $outputFile = "{$outputDir}/{$modelName}.ts";
            File::put($outputFile, $typeScriptContent);

            $this->line('Generated: '.str_replace(base_path(), '', $outputFile));
        } catch (\Exception $e) {
            $this->error('Error generating TypeScript for '.class_basename($modelClass).': '.$e->getMessage());
        }
    }

    /**
     * Generate TypeScript definitions for external types.
     *
     * @param  string  $outputDir  The directory to output the TypeScript files
     * @return void
     */
    protected function generateExternalTypeDefinitions(string $outputDir)
    {
        $this->info('Generating TypeScript definitions for external types...');

        $externalTypes = ExternalTypeDefinitions::getAll();

        foreach ($externalTypes as $type) {
            $outputFile = "{$outputDir}/{$type['name']}.ts";
            File::put($outputFile, $type['definition']);
            $this->line('Generated: '.str_replace(base_path(), '', $outputFile));
        }
    }

    /**
     * Get all resource files in the application.
     */
    protected function getResourceFiles(): array
    {
        $resourcesPath = app_path('Http/Resources');
        $excludedResources = [];

        $files = [];

        // Get all PHP files in the resources directory
        foreach (File::allFiles($resourcesPath) as $file) {
            if ($file->getExtension() === 'php' && str_ends_with($file->getFilename(), 'Resource.php') && ! in_array(pathinfo($file->getFilename(), PATHINFO_FILENAME), $excludedResources)) {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    /**
     * Get all model classes.
     */
    protected function getModelClasses(): array
    {
        $modelsPath = app_path('Models');

        if (! File::exists($modelsPath)) {
            return [];
        }

        $finder = new Finder;
        $finder->files()->in($modelsPath)->name('*.php');

        $models = [];
        foreach ($finder as $file) {
            $className = $file->getBasename('.php');
            $models[] = "App\\Models\\{$className}";
        }

        return $models;
    }

    /**
     * Generate TypeScript definition for a specific resource.
     *
     * @return void
     */
    protected function generateResourceDefinition(string $resourceName)
    {
        $this->info("Generating TypeScript definition for {$resourceName}...");

        // Ensure the resource name ends with 'Resource'
        if (! str_ends_with($resourceName, 'Resource')) {
            $resourceName .= 'Resource';
        }

        // Find the resource file
        $resourceFile = app_path("Http/Resources/{$resourceName}.php");

        if (! File::exists($resourceFile)) {
            $this->error("Resource file not found: {$resourceFile}");

            return;
        }

        $outputDir = resource_path('js/types/resources');

        // Create the output directory if it doesn't exist
        if (! File::exists($outputDir)) {
            File::makeDirectory($outputDir, 0755, true);
        }

        // Get the resource file content
        $fileContent = file_get_contents($resourceFile);

        // Debug: Check if the docblock contains a TypeScript interface
        if (strpos($fileContent, 'TypeScript interface') !== false) {
            $this->info('Found TypeScript interface in docblock');

            // Extract the docblock for debugging
            if (preg_match('/\/\*\*(.*?)\*\//s', $fileContent, $docblockMatches)) {
                $docblock = $docblockMatches[1];
                $this->info('Found docblock with length: '.strlen($docblock));

                // Extract the TypeScript interface section
                if (preg_match('/TypeScript interface:(.*?)```/s', $docblock, $interfaceHeaderMatches)) {
                    $this->info('Found TypeScript interface header');

                    // Extract the full interface including the code block
                    if (preg_match('/```typescript(.*?)```/s', $docblock, $codeBlockMatches)) {
                        $this->info('Found TypeScript code block with length: '.strlen($codeBlockMatches[1]));

                        // Extract the interface name and content
                        if (preg_match('/interface\s+(\w+)\s*\{(.*?)\}/s', $codeBlockMatches[1], $interfaceMatches)) {
                            $this->info('Successfully extracted interface: '.$interfaceMatches[1]);

                            $interfaceName = $interfaceMatches[1];
                            $interfaceContent = $interfaceMatches[2];

                            // Generate the TypeScript file
                            $content = "/**\n";
                            $content .= " * TypeScript definition for the {$resourceName}\n";
                            $content .= " * Auto-generated by hg:make:typescript\n";
                            $content .= " */\n\n";

                            // Extract imports from the interface content
                            $typesToImport = [];
                            // Match TypeScript types that should be imported (Resources and other interface references)
                            // Only match types that are used as actual types, not string literals in union types
                            if (preg_match_all('/(?::\s*|<\s*|readonly\s+)([A-Z]\w*Resource)(?:\[\]|\s*\|\s*null|\s*;|\s*\})/', $interfaceContent, $typeMatches)) {
                                foreach ($typeMatches[1] as $typeName) {
                                    // Skip the current resource (self-reference check)
                                    if ($typeName === $resourceName || $typeName === str_replace('Resource', '', $resourceName)) {
                                        continue;
                                    }

                                    if (! in_array($typeName, $typesToImport)) {
                                        $typesToImport[] = $typeName;
                                    }
                                }
                            }

                            // Add imports
                            if (! empty($typesToImport)) {
                                $typesToImport = array_unique($typesToImport);
                                sort($typesToImport);
                                $content .= 'import type { '.implode(', ', $typesToImport)." } from '@/types';\n\n";
                            }

                            // Use the resource name for the interface name
                            $content .= "export interface {$resourceName} {\n";

                            // Clean up the interface content by removing asterisks and extra spaces
                            $lines = explode("\n", $interfaceContent);
                            $cleanedLines = [];
                            foreach ($lines as $line) {
                                // Remove leading asterisks and spaces
                                $cleanedLine = preg_replace('/^\s*\*\s*/', '', $line);
                                // Ensure proper indentation (4 spaces)
                                if (! empty($cleanedLine) && $cleanedLine[0] !== '/') {
                                    $cleanedLines[] = '    '.$cleanedLine;
                                }
                            }
                            $content .= implode("\n", $cleanedLines)."\n";
                            $content .= "}\n";

                            // Write the TypeScript file
                            $outputFile = "{$outputDir}/{$resourceName}.ts";
                            File::put($outputFile, $content);
                            $this->line('Generated: '.str_replace(base_path(), '', $outputFile));

                            return;
                        } else {
                            $this->error('Could not extract interface name and content');
                        }
                    } else {
                        $this->error('Could not extract TypeScript code block');
                    }
                } else {
                    $this->error('Could not extract TypeScript interface header');
                }
            } else {
                $this->error('Could not extract docblock');
            }
        } else {
            $this->error('No TypeScript interface found in docblock');
        }

        // If direct extraction failed, use the extractor
        $extractor = new ResourceTypeScriptExtractor($resourceFile);
        $content = $extractor->extract($resourceName);

        // Write the TypeScript file
        $outputFile = "{$outputDir}/{$resourceName}.ts";
        File::put($outputFile, $content);
        $this->line('Generated: '.str_replace(base_path(), '', $outputFile));
    }
}

/**
 * Extract TypeScript interface from a resource file.
 */
class ResourceTypeScriptExtractor
{
    /**
     * The resource file path.
     */
    protected string $resourceFile;

    /**
     * Create a new extractor instance.
     */
    public function __construct(string $resourceFile)
    {
        $this->resourceFile = $resourceFile;
    }

    /**
     * Extract TypeScript interface from the resource file.
     */
    public function extract(string $resourceName): string
    {
        // Parse the resource file
        $fileContent = file_get_contents($this->resourceFile);

        // Generate the TypeScript content
        $content = "/**\n";
        $content .= " * TypeScript definition for the {$resourceName}\n";
        $content .= " * Auto-generated by hg:make:typescript\n";
        $content .= " */\n\n";

        // Add imports
        $content .= "import type { ModelResource } from '@/types';\n\n";

        // Generate the interface
        $content .= "export interface {$resourceName} {\n";
        $content .= "    // This interface was auto-generated but could not extract properties from the resource class.\n";
        $content .= "    // Please add properties manually or update the resource class with a proper TypeScript interface.\n";
        $content .= "}\n";

        return $content;
    }
}
