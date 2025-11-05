<?php

namespace App\Console\Commands\HiddenGambia\Resources;

use App\Console\Commands\Shared\MigrationAnalyser;
use App\Console\Commands\Shared\ModelAnalyser;
use App\Console\Commands\Shared\Utilities;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hg:make:resources
                           {models?* : The model classes to generate resources for}
                           {--force : Force overwrite existing resource files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate Hidden Gambia resource files for models based on migrations and relationships';

    /**
     * The model analyser instance.
     */
    protected ModelAnalyser $modelAnalyser;

    /**
     * The migration analyser instance.
     */
    protected MigrationAnalyser $migrationAnalyser;

    /**
     * Create a new command instance.
     */
    public function __construct(ModelAnalyser $modelAnalyser, MigrationAnalyser $migrationAnalyser)
    {
        parent::__construct();
        $this->modelAnalyser = $modelAnalyser;
        $this->migrationAnalyser = $migrationAnalyser;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $models = $this->argument('models');
        $all = empty($models); // Only set $all to true if no models were specified

        if (empty($models) && ! $all) {
            $this->error('Please specify at least one model.');

            return 1;
        }

        if ($all) {
            $models = Utilities::getAllModelClasses();
            if (empty($models)) {
                $this->error('No models found in the app/Models directory.');

                return 1;
            }
        } else {
            // Ensure all model classes are fully qualified
            $models = array_map(function ($model) {
                if (! Str::startsWith($model, ['App\\', '\\'])) {
                    return 'App\\Models\\'.$model;
                }

                return $model;
            }, $models);
        }

        $this->info('Generating resources for '.count($models).' model(s)...');
        $bar = $this->output->createProgressBar(count($models));
        $bar->start();

        $generatedCount = 0;
        $skippedCount = 0;
        $errorCount = 0;

        foreach ($models as $modelClass) {
            try {
                $result = $this->generateResourceForModel($modelClass, $this->option('force')); // Respect existing files unless --force
                if ($result === true) {
                    $generatedCount++;
                } elseif ($result === false) {
                    $skippedCount++;
                } else {
                    $errorCount++;
                }
            } catch (\Exception $e) {
                $this->newLine();
                $this->error("Error generating resource for {$modelClass}: ".$e->getMessage());
                $errorCount++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info('Resource generation completed:');
        $this->info("- Generated: {$generatedCount}");
        $this->info("- Skipped: {$skippedCount}");
        $this->info("- Errors: {$errorCount}");

        return 0;
    }

    /**
     * Generate a resource file for a model.
     */
    protected function generateResourceForModel(string $modelClass, bool $force = false): bool|string
    {
        // Check if the model class exists
        if (! class_exists($modelClass)) {
            $this->newLine();
            $this->warn("Model class {$modelClass} does not exist. Skipping.");

            return 'Model class does not exist';
        }

        // Get the resource class name
        $resourceClass = Utilities::getResourceClassName($modelClass);
        $resourcePath = Utilities::getResourcePath($resourceClass);

        // Check if the resource file already exists
        if (File::exists($resourcePath) && ! $force) {
            $this->newLine();
            $this->warn("Resource file for {$modelClass} already exists. Use --force to overwrite.");

            return false;
        }

        // Analyse the model
        $modelInfo = $this->modelAnalyser->analyse($modelClass);

        // Get the table name from the model
        $modelInstance = new $modelClass;
        $tableName = $modelInstance->getTable();

        // Get column information from migrations
        $columns = $this->migrationAnalyser->getTableColumns($tableName);

        // Create a column map for easy lookup
        $columnMap = [];
        if (is_array($columns)) {
            foreach ($columns as $column) {
                if (isset($column['name'])) {
                    $columnMap[$column['name']] = $column;
                }
            }
        }

        // Debug output
        $this->info('Column information for '.$tableName.' table:');
        foreach ($columnMap as $name => $column) {
            if (isset($column['nullable']) && $column['nullable']) {
                $this->info("Column {$name} is nullable");
            }
        }

        // Create a resource template
        $template = new ResourceTemplate($resourceClass, $modelClass);

        // Extract custom sections from existing file if it exists
        $template->extractCustomSections($resourcePath);

        // Set hidden fields
        $template->setHiddenFields($modelInfo['hidden']);

        // Add primary key first
        $isNullablePrimaryKey = false;
        if (isset($columnMap[$modelInfo['primaryKey']]) && isset($columnMap[$modelInfo['primaryKey']]['nullable'])) {
            $isNullablePrimaryKey = (bool) $columnMap[$modelInfo['primaryKey']]['nullable'];
            if ($isNullablePrimaryKey) {
                $this->info("Setting primary key {$modelInfo['primaryKey']} as nullable in TypeScript interface");
            }
        }

        $primaryKeyColumnInfo = isset($columnMap[$modelInfo['primaryKey']]) ? $columnMap[$modelInfo['primaryKey']] : null;
        $template->addAttribute(
            $modelInfo['primaryKey'],
            null,
            false,
            $isNullablePrimaryKey,
            $primaryKeyColumnInfo
        );

        // Add attributes from model's fillable properties
        foreach ($modelInfo['fillable'] as $attribute) {
            // Skip hidden fields
            if (! in_array($attribute, $modelInfo['hidden'])) {
                $isDate = in_array($attribute, $modelInfo['dates']);

                // Check if the attribute is nullable
                $isNullable = false;
                if (isset($columnMap[$attribute]) && isset($columnMap[$attribute]['nullable'])) {
                    $isNullable = (bool) $columnMap[$attribute]['nullable'];
                    // Debug output for nullable fields
                    if ($isNullable) {
                        $this->info("Setting {$attribute} as nullable in TypeScript interface");
                    }
                }

                // Get the cast type for this attribute
                $castType = isset($modelInfo['casts'][$attribute]) ? $modelInfo['casts'][$attribute] : null;

                // Get column information from migration analysis
                $columnInfo = isset($columnMap[$attribute]) ? $columnMap[$attribute] : null;

                $template->addAttribute($attribute, $castType, $isDate, $isNullable, $columnInfo);
            }
        }

        // Add dates that aren't already in fillable
        foreach ($modelInfo['dates'] as $date) {
            if (! in_array($date, $modelInfo['fillable']) &&
                $date !== $modelInfo['primaryKey'] &&
                ! in_array($date, $modelInfo['hidden'])) {
                $isNullable = isset($columnMap[$date]['nullable']) ? $columnMap[$date]['nullable'] : false;
                $dateColumnInfo = isset($columnMap[$date]) ? $columnMap[$date] : null;
                $template->addAttribute($date, 'timestamp', true, $isNullable, $dateColumnInfo);
            }
        }

        // Add appends from model
        foreach ($modelInfo['appends'] as $append) {
            if (! in_array($append, $modelInfo['hidden'])) {
                $template->addAttribute($append, null, false, false, null);
            }
        }

        // Add custom accessors
        foreach ($modelInfo['accessors'] as $accessor) {
            if (! in_array($accessor['name'], $modelInfo['hidden'])) {
                $template->addAccessor($accessor);
            }
        }

        // Add relationships from model
        foreach ($modelInfo['relationships'] as $name => $relation) {
            $template->addRelationship($name, $relation);
        }

        // Generate the resource file content
        $content = $template->generate();

        // Create the directory if it doesn't exist
        $directory = dirname($resourcePath);
        if (! File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        // Write the file
        File::put($resourcePath, $content);

        $this->info("Generated resource file: {$resourcePath}");

        return true;
    }

    /**
     * Get the console command help text.
     */
    public function getHelp(): string
    {
        return <<<'HELP'
The <info>hg:make:resources</info> command generates resource files for models
based on their migrations and relationships.

<comment>Usage:</comment>
  hg:make:resources [<models>...]

<comment>Arguments:</comment>
  models                Model classes to generate resources for (e.g., User, Post)

<comment>Examples:</comment>
  <info>php artisan hg:make:resources User Post</info>      Generate resources for User and Post models
  <info>php artisan hg:make:resources</info>               Generate resources for all models
HELP;
    }
}
