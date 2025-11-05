<?php

namespace App\Console\Commands\Shared;

use ReflectionClass;

class Utilities
{
    /**
     * Get the resource class name for a model class.
     */
    public static function getResourceClassName(string $modelClass): string
    {
        $modelName = class_basename($modelClass);

        return "{$modelName}Resource";
    }

    /**
     * Get the resource file path for a resource class.
     */
    public static function getResourcePath(string $resourceClass): string
    {
        return app_path("Http/Resources/{$resourceClass}.php");
    }

    /**
     * Determine if a relationship is a collection relationship.
     */
    public static function isCollectionRelationship(string $relationType): bool
    {
        return in_array($relationType, [
            'HasMany', 'BelongsToMany', 'MorphMany', 'HasManyThrough', 'MorphToMany',
        ]);
    }

    /**
     * Determine if a field is a date field based on its type.
     */
    public static function isDateField(string $type): bool
    {
        return in_array(strtolower($type), [
            'date', 'datetime', 'timestamp', 'datetimetz',
            'time', 'timetz', 'year', 'immutable_date', 'immutable_datetime',
        ]);
    }

    /**
     * Get the PHP type for a database column type.
     */
    public static function getPhpTypeForColumnType(string $type): string
    {
        $typeMap = [
            'biginteger' => 'int',
            'binary' => 'string',
            'boolean' => 'bool',
            'char' => 'string',
            'date' => 'string', // Formatted date
            'datetime' => 'string', // Formatted date
            'datetimetz' => 'string', // Formatted date with timezone
            'decimal' => 'float',
            'double' => 'float',
            'enum' => 'string',
            'float' => 'float',
            'foreignid' => 'int', // Foreign key (biginteger)
            'integer' => 'int',
            'json' => 'array',
            'jsonb' => 'array',
            'longtext' => 'string',
            'mediuminteger' => 'int',
            'mediumtext' => 'string',
            'morphs' => 'int', // ID part of the morph
            'nullablemorphs' => 'int', // ID part of the morph
            'nullabletimestamps' => 'string', // Formatted date
            'smallinteger' => 'int',
            'string' => 'string',
            'text' => 'string',
            'time' => 'string', // Formatted time
            'timetz' => 'string', // Formatted time with timezone
            'timestamp' => 'string', // Formatted date
            'timestamptz' => 'string', // Formatted date with timezone
            'timestamps' => 'string', // Formatted date
            'tinyinteger' => 'int',
            'unsignedbiginteger' => 'int',
            'unsignedinteger' => 'int',
            'unsignedmediuminteger' => 'int',
            'unsignedsmallinteger' => 'int',
            'unsignedtinyinteger' => 'int',
            'uuid' => 'string',
            'year' => 'int',
        ];

        return $typeMap[strtolower($type)] ?? 'mixed';
    }

    /**
     * Get the TypeScript type for a database column type.
     */
    public static function getTsTypeForColumnType(string $type): string
    {
        $typeMap = [
            'biginteger' => 'number',
            'binary' => 'string',
            'boolean' => 'boolean',
            'char' => 'string',
            'date' => 'string', // Formatted date
            'datetime' => 'string', // Formatted date
            'datetimetz' => 'string', // Formatted date with timezone
            'decimal' => 'number',
            'double' => 'number',
            'enum' => 'string',
            'float' => 'number',
            'foreignid' => 'number', // Foreign key (biginteger)
            'integer' => 'number',
            'json' => 'Record<string, any>',
            'jsonb' => 'Record<string, any>',
            'longtext' => 'string',
            'mediuminteger' => 'number',
            'mediumtext' => 'string',
            'morphs' => 'number', // ID part of the morph
            'nullablemorphs' => 'number | null', // ID part of the morph
            'nullabletimestamps' => 'string | null', // Formatted date
            'smallinteger' => 'number',
            'string' => 'string',
            'text' => 'string',
            'time' => 'string', // Formatted time
            'timetz' => 'string', // Formatted time with timezone
            'timestamp' => 'string', // Formatted date
            'timestamptz' => 'string', // Formatted date with timezone
            'timestamps' => 'string', // Formatted date
            'tinyinteger' => 'number',
            'unsignedbiginteger' => 'number',
            'unsignedinteger' => 'number',
            'unsignedmediuminteger' => 'number',
            'unsignedsmallinteger' => 'number',
            'unsignedtinyinteger' => 'number',
            'uuid' => 'string',
            'year' => 'number',
        ];

        $lowercaseType = strtolower($type);

        return $typeMap[$lowercaseType] ?? 'any';
    }

    /**
     * Get all model classes in the application.
     */
    public static function getAllModelClasses(): array
    {
        $models = [];
        $modelsPath = app_path('Models');

        if (! file_exists($modelsPath)) {
            return [];
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($modelsPath)
        );

        foreach ($files as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $className = self::getClassNameFromFile($file->getPathname());

                if ($className && self::isModelClass($className)) {
                    $models[] = $className;
                }
            }
        }

        return $models;
    }

    /**
     * Get the fully qualified class name from a file.
     */
    protected static function getClassNameFromFile(string $file): ?string
    {
        $content = file_get_contents($file);
        $namespace = '';
        $class = '';

        // Extract namespace
        if (preg_match('/namespace\s+([^;]+);/', $content, $matches)) {
            $namespace = $matches[1];
        }

        // Extract class name
        if (preg_match('/class\s+([^\s{]+)/', $content, $matches)) {
            $class = $matches[1];
        }

        if ($namespace && $class) {
            return $namespace.'\\'.$class;
        }

        return null;
    }

    /**
     * Check if a class is a Laravel model.
     */
    protected static function isModelClass(string $className): bool
    {
        if (! class_exists($className)) {
            return false;
        }

        try {
            $reflection = new ReflectionClass($className);

            return ! $reflection->isAbstract() && $reflection->isSubclassOf('Illuminate\Database\Eloquent\Model');
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Format a value based on its type.
     */
    public static function formatValueForResource($value, string $type): string
    {
        if (self::isDateField($type)) {
            return "\$this->{$value}?->format('Y-m-d H:i:s')";
        }

        return "\$this->{$value}";
    }

    /**
     * Generate a safe null check for a relationship.
     */
    public static function generateSafeNullCheck(string $relation, string $resourceClass, bool $isCollection = false): string
    {
        if ($isCollection) {
            return "\$this->safelyMapCollection('{$relation}', {$resourceClass}::class, [])";
        } else {
            return "\$this->safelyTransformRelation('{$relation}', {$resourceClass}::class)";
        }
    }

    /**
     * Check if a class uses a trait.
     */
    public static function classUsesTrait(string $class, string $trait): bool
    {
        return in_array($trait, class_uses_recursive($class));
    }
}
