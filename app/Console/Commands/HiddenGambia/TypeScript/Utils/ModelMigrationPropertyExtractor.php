<?php

namespace App\Console\Commands\HiddenGambia\TypeScript\Utils;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ModelMigrationPropertyExtractor
{
    /**
     * Extract model properties from migration files.
     *
     * @param  string  $modelName  The name of the model
     * @return array Array of properties with their types
     */
    public function extract(string $modelName): array
    {
        $tableName = $this->guessTableName($modelName);
        $migrationFiles = $this->findMigrationFiles($tableName);

        if (empty($migrationFiles)) {
            return $this->getDefaultProperties();
        }

        $properties = [];

        foreach ($migrationFiles as $file) {
            $content = File::get($file);
            $this->extractPropertiesFromMigration($content, $properties);
        }

        // Add common properties if they don't exist
        $defaultProperties = $this->getDefaultProperties();
        foreach ($defaultProperties as $name => $type) {
            if (! isset($properties[$name])) {
                $properties[$name] = $type;
            }
        }

        return $properties;
    }

    /**
     * Get default properties that are common to most models.
     */
    protected function getDefaultProperties(): array
    {
        return [
            'id' => 'number',
            'createdAt' => 'string',
            'updatedAt' => 'string',
        ];
    }

    /**
     * Guess the table name from the model name.
     */
    protected function guessTableName(string $modelName): string
    {
        return Str::snake(Str::plural($modelName));
    }

    /**
     * Find migration files for a specific table.
     */
    protected function findMigrationFiles(string $tableName): array
    {
        $migrationsPath = database_path('migrations');
        $files = File::glob("{$migrationsPath}/*.php");

        return array_filter($files, function ($file) use ($tableName) {
            $content = File::get($file);

            return Str::contains($content, "table('{$tableName}'") ||
                   Str::contains($content, "table(\"{$tableName}\"");
        });
    }

    /**
     * Extract properties from migration content.
     */
    protected function extractPropertiesFromMigration(string $content, array &$properties): void
    {
        // Extract column definitions with common patterns
        $this->extractStandardColumns($content, $properties);

        // Extract foreign keys
        $this->extractForeignKeys($content, $properties);

        // Extract JSON columns
        $this->extractJsonColumns($content, $properties);

        // Check for nullable columns
        $this->markNullableColumns($content, $properties);

        // Check for default values
        $this->extractDefaultValues($content, $properties);
    }

    /**
     * Extract standard column definitions.
     */
    protected function extractStandardColumns(string $content, array &$properties): void
    {
        // Match standard column definitions: $table->string('name'), etc.
        preg_match_all('/\$table->(\w+)\(\'(\w+)\'/', $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $type = $match[1];
            $column = $match[2];

            $properties[$column] = $this->mapColumnTypeToTypeScript($type);
        }

        // Match timestamp columns
        if (preg_match('/\$table->timestamps\(\)/', $content)) {
            $properties['createdAt'] = 'string';
            $properties['updatedAt'] = 'string';
        }

        // Match soft deletes
        if (preg_match('/\$table->softDeletes\(\)/', $content)) {
            $properties['deletedAt'] = 'string | null';
        }
    }

    /**
     * Extract foreign key columns.
     */
    protected function extractForeignKeys(string $content, array &$properties): void
    {
        // Match foreign key definitions
        preg_match_all('/\$table->foreignId\(\'(\w+)\'\)/', $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $column = $match[1];
            $properties[$column] = 'number';
        }

        // Match foreign UUID keys
        preg_match_all('/\$table->foreignUuid\(\'(\w+)\'\)/', $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $column = $match[1];
            $properties[$column] = 'string';
        }
    }

    /**
     * Extract JSON columns.
     */
    protected function extractJsonColumns(string $content, array &$properties): void
    {
        // Match JSON column definitions
        preg_match_all('/\$table->json\(\'(\w+)\'\)/', $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $column = $match[1];
            $properties[$column] = 'Record<string, any>';
        }

        // Match JSONB column definitions
        preg_match_all('/\$table->jsonb\(\'(\w+)\'\)/', $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $column = $match[1];
            $properties[$column] = 'Record<string, any>';
        }
    }

    /**
     * Mark nullable columns.
     */
    protected function markNullableColumns(string $content, array &$properties): void
    {
        // Match nullable columns
        preg_match_all('/\'(\w+)\'\)->nullable\(\)/', $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $column = $match[1];
            if (isset($properties[$column]) && ! str_contains($properties[$column], 'null')) {
                $properties[$column] .= ' | null';
            }
        }
    }

    /**
     * Extract default values for columns.
     */
    protected function extractDefaultValues(string $content, array &$properties): void
    {
        // This is a simplified implementation
        // A more comprehensive implementation would parse the default values
        // and adjust the TypeScript types accordingly

        // For example, if a column has a default of true/false, it's likely a boolean
        preg_match_all('/\'(\w+)\'\)->default\((true|false)\)/', $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $column = $match[1];
            $properties[$column] = 'boolean';
        }

        // If a column has a numeric default, it's likely a number
        preg_match_all('/\'(\w+)\'\)->default\((\d+)\)/', $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $column = $match[1];
            $properties[$column] = 'number';
        }
    }

    /**
     * Map Laravel column type to TypeScript type.
     */
    protected function mapColumnTypeToTypeScript(string $columnType): string
    {
        $typeMap = [
            'bigIncrements' => 'number',
            'bigInteger' => 'number',
            'binary' => 'string',
            'boolean' => 'boolean',
            'char' => 'string',
            'date' => 'string',
            'dateTime' => 'string',
            'decimal' => 'number',
            'double' => 'number',
            'enum' => 'string',
            'float' => 'number',
            'increments' => 'number',
            'integer' => 'number',
            'json' => 'Record<string, any>',
            'jsonb' => 'Record<string, any>',
            'longText' => 'string',
            'mediumInteger' => 'number',
            'mediumText' => 'string',
            'smallInteger' => 'number',
            'string' => 'string',
            'text' => 'string',
            'time' => 'string',
            'timestamp' => 'string',
            'timestamps' => 'string',
            'tinyInteger' => 'number',
            'unsignedBigInteger' => 'number',
            'unsignedInteger' => 'number',
            'unsignedMediumInteger' => 'number',
            'unsignedSmallInteger' => 'number',
            'unsignedTinyInteger' => 'number',
            'uuid' => 'string',
        ];

        return $typeMap[$columnType] ?? 'any';
    }
}
