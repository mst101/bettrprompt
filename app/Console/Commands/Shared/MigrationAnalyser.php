<?php

namespace App\Console\Commands\Shared;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MigrationAnalyser
{
    /**
     * Analyse migrations to extract column information for a table.
     */
    public function analyse(string $table, bool $isVerbose = false): array
    {
        $columnsMap = [];
        $migrationsPath = database_path('migrations');

        if (! File::exists($migrationsPath)) {
            return [];
        }

        $files = File::allFiles($migrationsPath);

        // Sort migrations by filename to ensure they're processed in chronological order
        usort($files, function ($a, $b) {
            return strcmp($a->getFilename(), $b->getFilename());
        });

        foreach ($files as $file) {
            $content = File::get($file->getPathname());

            // Match Schema::create or Schema::table for the specific table
            if (preg_match_all(
                '/Schema::(?:create|table)\s*\(\s*[\'"]'.preg_quote($table, '/').'[\'"]\s*,\s*function\s*\([^\)]*\)\s*{(.*?)}\);/s',
                $content,
                $matches,
                PREG_SET_ORDER
            )) {
                foreach ($matches as $match) {
                    if (! isset($match[1])) {
                        continue;
                    }
                    $block = $match[1];
                    $this->extractColumnsFromMigration($block, $columnsMap);
                    $this->extractDropsAndRenames($block, $columnsMap);
                }
            }
        }

        // Debug output
        if ($isVerbose) {
            echo 'MigrationAnalyser: Found '.count($columnsMap)." columns for table {$table}\n";
            foreach ($columnsMap as $name => $column) {
                if (isset($column['nullable']) && $column['nullable']) {
                    echo "MigrationAnalyser: Column {$name} is nullable\n";
                }
            }
        }

        // Convert associative array to indexed array with name as a field
        $columns = [];
        foreach ($columnsMap as $name => $column) {
            $column['name'] = $name;
            $columns[] = $column;
        }

        return $columns;
    }

    /**
     * Analyse migrations to extract column information and relationships for a table.
     */
    public function analyseWithRelationships(string $table, bool $isVerbose = false): array
    {
        $columns = $this->analyse($table, $isVerbose);
        $relationshipProxies = $this->extractRelationshipsFromMigrations($table, $columns);

        return [
            //            'columns' => $columns,
            'relationship_proxies' => $relationshipProxies,
        ];
    }

    /**
     * Extract column definitions from migration content.
     */
    protected function extractColumnsFromMigration(string $content, array &$columnsMap): void
    {
        // Extract column definitions using regex
        // Match patterns like: $table->string('name', 100)->nullable()->default('John');
        preg_match_all('/\$table->(\w+)\([\'"]([^\'"]+)[\'"](?:,\s*([^)]+))?\)((?:->(?:\w+)(?:\([^)]*\))?)*);/',
            $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $type = $match[1];
            $name = $match[2];
            $params = $match[3] ?? '';
            $modifiers = $match[4] ?? '';

            // Skip if this is a foreign key definition or index
            if (in_array($type, ['foreign', 'index', 'unique', 'primary'])) {
                continue;
            }

            // Skip if the column name is numeric (likely a bug in regex matching)
            if (is_numeric($name)) {
                continue;
            }

            $columnsMap[$name] = [
                'type' => $type,
                'params' => $params,
                'nullable' => Str::contains($modifiers, '->nullable()'),
                'unsigned' => Str::contains($modifiers, '->unsigned()') || Str::startsWith($type, 'unsigned'),
                'default' => $this->extractDefaultValue($modifiers),
                'phpType' => Utilities::getPhpTypeForColumnType($type),
                'tsType' => Utilities::getTsTypeForColumnType($type),
            ];
        }

        // Also try to match the more common patterns directly
        $this->extractCommonColumnPatterns($content, $columnsMap);

        // Extract Schema::table blocks (we don't need to filter by table name here because
        // we already filtered the migration file by table name in the analyse method)
        preg_match_all('/Schema::table\([\'"]([^\'"]+)[\'"],\s*function\s*\(Blueprint\s*\$table\)\s*{(.*?)}\);/s',
            $content, $schemaMatches, PREG_SET_ORDER);

        foreach ($schemaMatches as $schemaMatch) {
            if (! isset($schemaMatch[2])) {
                continue;
            }
            $tableContent = $schemaMatch[2];
            preg_match_all('/\$table->(\w+)\([\'"]([^\'"]+)[\'"](?:,\s*([^)]+))?\)((?:->(?:\w+)(?:\([^)]*\))?)*);/',
                $tableContent, $columnMatches, PREG_SET_ORDER);

            foreach ($columnMatches as $match) {
                $type = $match[1];
                $name = $match[2];
                $params = $match[3] ?? '';
                $modifiers = $match[4] ?? '';

                // Skip if this is a foreign key definition or index
                if (in_array($type, ['foreign', 'index', 'unique', 'primary'])) {
                    continue;
                }

                // Skip if the column name is numeric (likely a bug in regex matching)
                if (is_numeric($name)) {
                    continue;
                }

                $columnsMap[$name] = [
                    'type' => $type,
                    'params' => $params,
                    'nullable' => Str::contains($modifiers, '->nullable()'),
                    'unsigned' => Str::contains($modifiers, '->unsigned()') || Str::startsWith($type, 'unsigned'),
                    'default' => $this->extractDefaultValue($modifiers),
                    'phpType' => Utilities::getPhpTypeForColumnType($type),
                    'tsType' => Utilities::getTsTypeForColumnType($type),
                ];
            }
        }

        // Check for timestamps
        if (Str::contains($content, '->timestamps()')) {
            $columnsMap['created_at'] = [
                'type' => 'timestamp',
                'nullable' => false,
                'phpType' => 'string',
                'tsType' => 'string',
            ];

            $columnsMap['updated_at'] = [
                'type' => 'timestamp',
                'nullable' => false,
                'phpType' => 'string',
                'tsType' => 'string',
            ];
        }

        // Check for softDeletes
        if (Str::contains($content, '->softDeletes()')) {
            $columnsMap['deleted_at'] = [
                'type' => 'timestamp',
                'nullable' => true,
                'phpType' => 'string',
                'tsType' => 'string|null',
            ];
        }

        // Add ID column if it's not already added
        if (! isset($columnsMap['id']) && Str::contains($content, '->id()')) {
            $columnsMap['id'] = [
                'type' => 'bigInteger',
                'nullable' => false,
                'unsigned' => true,
                'phpType' => 'int',
                'tsType' => 'number',
            ];
        }

        // Extract custom column types like magellanPoint
        preg_match_all('/\$table->(\w+)\([\'"]([^\'"]+)[\'"](?:,\s*([^)]+))?\)((?:->(?:\w+)(?:\([^)]*\))?)*);/',
            $content, $customMatches, PREG_SET_ORDER);

        foreach ($customMatches as $match) {
            $type = $match[1];
            $name = $match[2];
            $params = $match[3] ?? '';
            $modifiers = $match[4] ?? '';

            // Skip if already processed or if it's a standard type
            if (isset($columnsMap[$name]) || in_array($type, [
                'string', 'integer', 'text', 'boolean', 'date', 'datetime', 'time', 'timestamp', 'decimal', 'float',
                'double', 'json', 'jsonb',
            ])) {
                continue;
            }

            // Handle custom column types
            $columnsMap[$name] = [
                'type' => $type,
                'params' => $params,
                'nullable' => Str::contains($modifiers, '->nullable()'),
                'unsigned' => Str::contains($modifiers, '->unsigned()'),
                'default' => $this->extractDefaultValue($modifiers),
                'phpType' => 'string', // Default to string for custom types
                'tsType' => 'string',  // Default to string for custom types
            ];
        }
    }

    /**
     * Extract common column patterns that might be missed by the regex.
     */
    protected function extractCommonColumnPatterns(string $content, array &$columnsMap): void
    {
        // Match string columns: $table->string('name')
        preg_match_all('/\$table->string\([\'"]([^\'"]+)[\'"]\)/', $content, $stringMatches);
        foreach ($stringMatches[1] as $name) {
            if (! isset($columnsMap[$name])) {
                $columnsMap[$name] = [
                    'type' => 'string',
                    'params' => '',
                    'nullable' => false,
                    'unsigned' => false,
                    'default' => null,
                    'phpType' => 'string',
                    'tsType' => 'string',
                ];
            }
        }

        // Match integer columns: $table->integer('count')
        preg_match_all('/\$table->integer\([\'"]([^\'"]+)[\'"]\)/', $content, $intMatches);
        foreach ($intMatches[1] as $name) {
            if (! isset($columnsMap[$name])) {
                $columnsMap[$name] = [
                    'type' => 'integer',
                    'params' => '',
                    'nullable' => false,
                    'unsigned' => false,
                    'default' => null,
                    'phpType' => 'int',
                    'tsType' => 'number',
                ];
            }
        }

        // Match text columns: $table->text('description')
        preg_match_all('/\$table->text\([\'"]([^\'"]+)[\'"]\)/', $content, $textMatches);
        foreach ($textMatches[1] as $name) {
            if (! isset($columnsMap[$name])) {
                $columnsMap[$name] = [
                    'type' => 'text',
                    'params' => '',
                    'nullable' => false,
                    'unsigned' => false,
                    'default' => null,
                    'phpType' => 'string',
                    'tsType' => 'string',
                ];
            }
        }

        // Match boolean columns: $table->boolean('active')
        preg_match_all('/\$table->boolean\([\'"]([^\'"]+)[\'"]\)/', $content, $boolMatches);
        foreach ($boolMatches[1] as $name) {
            if (! isset($columnsMap[$name])) {
                $columnsMap[$name] = [
                    'type' => 'boolean',
                    'params' => '',
                    'nullable' => false,
                    'unsigned' => false,
                    'default' => null,
                    'phpType' => 'bool',
                    'tsType' => 'boolean',
                ];
            }
        }
    }

    /**
     * Extract drops and renames from migration content.
     */
    protected function extractDropsAndRenames(string $content, array &$columnsMap): void
    {
        // Extract column drops
        preg_match_all('/\$table->dropColumn\([\'"]([^\'"]+)[\'"]\);/', $content, $matches);

        if (! empty($matches[1])) {
            foreach ($matches[1] as $column) {
                unset($columnsMap[$column]);
            }
        }

        // Extract column renames
        preg_match_all('/\$table->renameColumn\([\'"]([^\'"]+)[\'"],\s*[\'"]([^\'"]+)[\'"]\);/', $content, $matches,
            PREG_SET_ORDER);

        if (! empty($matches)) {
            foreach ($matches as $match) {
                $oldName = $match[1];
                $newName = $match[2];

                if (isset($columnsMap[$oldName])) {
                    $columnsMap[$newName] = $columnsMap[$oldName];
                    unset($columnsMap[$oldName]);
                }
            }
        }
    }

    /**
     * Extract default value from column modifiers.
     */
    protected function extractDefaultValue(string $modifiers): ?string
    {
        if (preg_match('/->default\(([^)]+)\)/', $modifiers, $matches)) {
            $default = trim($matches[1]);

            // Remove quotes if present
            if (Str::startsWith($default, ['\'', '"']) && Str::endsWith($default, ['\'', '"'])) {
                $default = substr($default, 1, -1);
            }

            return $default;
        }

        return null;
    }

    /**
     * Extract relationships from migration foreign keys and conventions.
     * Returns [methodName => [chain...]] for proxy chains.
     */
    public function extractRelationshipsFromMigrations(string $table, array $columns): array
    {
        $relationshipProxies = [];
        $allModels = \App\Console\Commands\Shared\Utilities::getAllModelClasses();
        $tableSingular = \Illuminate\Support\Str::singular($table);
        $tableStudly = \Illuminate\Support\Str::studly($tableSingular);
        // 1. belongsTo (foreign keys in this table)
        foreach ($columns as $column) {
            if (! isset($column['name'])) {
                continue;
            }
            $colName = $column['name'];
            // Morphs
            if (\Illuminate\Support\Str::endsWith($colName, ['_id', '_type'])) {
                $base = \Illuminate\Support\Str::beforeLast($colName, '_id');
                if (isset($columns[$base.'_type'])) {
                    // morphTo
                    $relationshipProxies[$base] = ['\$this', $base];

                    continue;
                }
            }
            // belongsTo
            if (\Illuminate\Support\Str::endsWith($colName, '_id')) {
                $relationName = \Illuminate\Support\Str::camel(\Illuminate\Support\Str::beforeLast($colName, '_id'));
                $relatedModel = 'App\\Models\\'.\Illuminate\Support\Str::studly(\Illuminate\Support\Str::singular($relationName));
                if (class_exists($relatedModel)) {
                    $relationshipProxies[$relationName] = ['\$this', $relationName];
                }
            }
        }
        // 2. hasMany/hasOne (foreign keys in other tables)
        foreach ($allModels as $modelClass) {
            if (! class_exists($modelClass)) {
                continue;
            }
            $modelTable = (new $modelClass)->getTable();
            if ($modelTable === $table) {
                continue;
            }
            $otherColumns = $this->analyse($modelTable);
            foreach ($otherColumns as $otherCol) {
                if (! isset($otherCol['name'])) {
                    continue;
                }
                // MorphMany/MorphOne
                if (\Illuminate\Support\Str::endsWith($otherCol['name'], ['_id', '_type'])) {
                    $base = \Illuminate\Support\Str::beforeLast($otherCol['name'], '_id');
                    if (isset($otherColumns[$base.'_type']) && $base === $tableSingular) {
                        $relationName = \Illuminate\Support\Str::camel(\Illuminate\Support\Str::pluralStudly($modelTable));
                        $relationshipProxies[$relationName] = ['\$this', $relationName];
                    }
                }
                // hasMany/hasOne
                if ($otherCol['name'] === $tableSingular.'_id') {
                    $relationName = \Illuminate\Support\Str::camel(\Illuminate\Support\Str::pluralStudly($modelTable));
                    $relationshipProxies[$relationName] = ['\$this', $relationName];
                    // Check for unique index for hasOne
                    if (isset($otherCol['unique']) && $otherCol['unique']) {
                        $hasOneName = \Illuminate\Support\Str::camel(\Illuminate\Support\Str::singularStudly($modelTable));
                        $relationshipProxies[$hasOneName] = ['\$this', $hasOneName];
                    }
                }
            }
        }
        // 3. belongsToMany (pivot tables)
        foreach ($allModels as $modelClass) {
            if (! class_exists($modelClass)) {
                continue;
            }
            $modelTable = (new $modelClass)->getTable();
            if (! \Illuminate\Support\Str::contains($modelTable, '_')) {
                continue;
            }
            $parts = explode('_', $modelTable);
            if (in_array($table, $parts)) {
                foreach ($parts as $other) {
                    if ($other !== $table) {
                        $relationName = \Illuminate\Support\Str::camel(\Illuminate\Support\Str::pluralStudly($other));
                        $relationshipProxies[$relationName] = ['\$this', $relationName];
                    }
                }
            }
        }
        // 4. hasManyThrough/hasOneThrough (2-hop via intermediate table)
        foreach ($allModels as $modelClass) {
            if (! class_exists($modelClass)) {
                continue;
            }
            $modelTable = (new $modelClass)->getTable();
            if ($modelTable === $table) {
                continue;
            }
            $otherColumns = $this->analyse($modelTable);
            foreach ($otherColumns as $otherCol) {
                if (! isset($otherCol['name'])) {
                    continue;
                }
                if ($otherCol['name'] === $tableSingular.'_id') {
                    // Look for a third table referencing this intermediate table
                    foreach ($allModels as $thirdClass) {
                        if (! class_exists($thirdClass)) {
                            continue;
                        }
                        $thirdTable = (new $thirdClass)->getTable();
                        if ($thirdTable === $table || $thirdTable === $modelTable) {
                            continue;
                        }
                        $thirdCols = $this->analyse($thirdTable);
                        foreach ($thirdCols as $thirdCol) {
                            if (! isset($thirdCol['name'])) {
                                continue;
                            }
                            if ($thirdCol['name'] === \Illuminate\Support\Str::singular($modelTable).'_id') {
                                // hasManyThrough
                                $relationName = \Illuminate\Support\Str::camel(\Illuminate\Support\Str::pluralStudly($thirdTable));
                                $relationshipProxies[$relationName] = ['\$this', $relationName];
                                // hasOneThrough if unique
                                if (isset($thirdCol['unique']) && $thirdCol['unique']) {
                                    $hasOneThroughName = \Illuminate\Support\Str::camel(\Illuminate\Support\Str::singularStudly($thirdTable));
                                    $relationshipProxies[$hasOneThroughName] = ['\$this', $hasOneThroughName];
                                }
                            }
                        }
                    }
                }
            }
        }
        ksort($relationshipProxies);

        return $relationshipProxies;
    }

    /**
     * Get columns for a table.
     *
     * This is an alias for the analyse method to maintain a more descriptive API.
     */
    public function getTableColumns(string $table): array
    {
        return $this->analyse($table);
    }

    /**
     * Get a list of relationships for a given model table. Each entry contains:
     * [name, type, docblock, code]
     */
    public function getRelationships(string $table): array
    {
        $columns = $this->analyse($table);
        $proxies = $this->extractRelationshipsFromMigrations($table, $columns);
        $relationships = [];
        foreach ($proxies as $name => $chain) {
            // Guess relationship type based on chain and naming
            $type = 'BelongsTo';
            if (\Illuminate\Support\Str::endsWith($name, 's')) {
                $type = 'HasMany';
            }
            // Through chains: check if chain has more than two elements
            $isThrough = count($chain) > 2;
            // Morphs
            if (isset($columns[$name.'_type']) && isset($columns[$name.'_id'])) {
                $type = 'MorphTo';
            }
            // Docblock
            $doc = '';
            if ($isThrough) {
                $through = implode('->', array_slice($chain, 1, -1));
                $doc = "/**\n     * Get the {$name} through the {$through} relationship.\n     */";
                $code = "public function {$name}(): {$type}\n{\n    return ".implode('->', array_slice($chain, 1))."();\n}";
            } elseif ($type === 'HasMany') {
                $doc = "/**\n     * Get the {$name} associated with this {$table}.\n     */";
                $code = "public function {$name}(): HasMany\n{\n    return \$this->hasMany(\"App\\Models\\".\Illuminate\Support\Str::studly(\Illuminate\Support\Str::singular($name))."::class, '{$table}_id');\n}";
            } elseif ($type === 'MorphTo') {
                $doc = "/**\n     * Get the parent {$name} model (morph to).\n     */";
                $code = "public function {$name}(): MorphTo\n{\n    return \$this->morphTo();\n}";
            } else {
                $doc = "/**\n     * Get the {$name} that owns the {$table}.\n     */";
                $code = "public function {$name}(): BelongsTo\n{\n    return \$this->belongsTo(\"App\\Models\\".\Illuminate\Support\Str::studly($name)."::class);\n}";
            }
            $relationships[] = [
                'name' => $name,
                'type' => $type,
                'docblock' => $doc,
                'code' => $code,
            ];
        }

        return $relationships;
    }
}
