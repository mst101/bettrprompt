<?php

namespace App\Console\Commands\HiddenGambia\TypeScript\Utils;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ModelTypeScriptInterfaceExtractor
{
    use PluralHelperTrait;

    /**
     * Extract TypeScript interface from a model class.
     *
     * @param  string  $modelClass  Fully qualified class name of the model
     * @return string TypeScript interface content
     *
     * @throws \Exception If there's an error during extraction
     */
    public function extract(string $modelClass): string
    {
        try {
            if (! class_exists($modelClass)) {
                throw new \Exception("Model class {$modelClass} does not exist");
            }

            // Get model name from class name
            $modelName = class_basename($modelClass);

            // Create an instance of the model
            $model = new $modelClass;
            if (! ($model instanceof Model)) {
                throw new \Exception("{$modelClass} is not an Eloquent model");
            }

            $reflection = new \ReflectionClass($model);

            // Extract properties from the model
            $properties = $this->extractPropertiesFromModel($model, $reflection);

            // Extract relationships from the model
            $relationships = $this->getModelRelationships($model, $reflection);

            // Generate TypeScript interface
            return $this->generateTypeScriptInterface($modelName, $properties, $relationships);
        } catch (\Exception $e) {
            throw new \Exception('Error extracting TypeScript interface: '.$e->getMessage(), 0, $e);
        } catch (\Error $e) {
            throw new \Exception('Error extracting TypeScript interface: '.$e->getMessage(), 0, $e);
        }
    }

    /**
     * Extract properties from a model class.
     *
     * @param  Model  $model  The model instance
     * @param  \ReflectionClass  $reflection  Reflection of the model class
     * @return array Properties with their types
     */
    private function extractPropertiesFromModel(Model $model, \ReflectionClass $reflection): array
    {
        $properties = [];

        // Add primary key
        // Check if model uses UUIDs
        $usesUuids = in_array('Illuminate\Database\Eloquent\Concerns\HasUuids', class_uses_recursive($model));
        $properties[$model->getKeyName()] = $usesUuids ? 'string' : 'number';

        // Add timestamps if the model uses them
        if ($model->usesTimestamps()) {
            $properties[$model->getCreatedAtColumn()] = 'string';
            $properties[$model->getUpdatedAtColumn()] = 'string';
        }

        // Add soft delete timestamp if the model uses it
        if (in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses_recursive($model))) {
            $properties[$model->getDeletedAtColumn()] = 'string | null';
        }

        // Get fillable attributes
        $fillable = $model->getFillable();
        foreach ($fillable as $attribute) {
            $properties[$attribute] = $this->inferTypeFromAttribute($attribute, $model);
        }

        // Get accessor properties
        $accessors = $this->getModelAccessors($model, $reflection);
        foreach ($accessors as $accessorName => $accessorType) {
            $properties[$accessorName] = $accessorType;
        }

        return $properties;
    }

    /**
     * Get the model's relationships.
     *
     * @param  Model  $model  The model instance
     * @param  \ReflectionClass  $reflection  Reflection of the model class
     * @return array Relationships with their types
     */
    private function getModelRelationships(Model $model, \ReflectionClass $reflection): array
    {
        $relationships = [];

        foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            // Skip methods that are not defined in the model class
            if ($method->class !== get_class($model)) {
                continue;
            }

            // Skip methods with parameters
            if ($method->getNumberOfParameters() > 0) {
                continue;
            }

            // Skip common non-relationship methods
            $skipMethods = [
                'getKey', 'getTable', 'getFillable', 'getHidden', 'getVisible',
                'getConnection', 'getConnectionName', 'getKeyName', 'getQualifiedKeyName',
                'getKeyType', 'getIncrementing', 'getForeignKey', 'getPerPage',
                'getCreatedAtColumn', 'getUpdatedAtColumn', 'getDeletedAtColumn',
                'getDates', 'getCasts', 'getAttributes', 'getOriginal',
                'getAttribute', 'setAttribute', 'getAttributeValue', 'getRelation',
                'setRelation', 'getRelationValue', 'getRelationshipFromMethod',
                'newCollection', 'newQuery', 'newModelQuery', 'newEloquentBuilder',
                'newQueryWithoutScopes', 'newQueryWithoutScope', 'query',
                'with', 'withCount', 'withoutGlobalScopes', 'withoutGlobalScope',
                'withTrashed', 'withoutTrashed', 'onlyTrashed', 'trashed',
                'restore', 'forceDelete', 'delete', 'save', 'update', 'push',
                'fresh', 'refresh', 'replicate', 'is', 'isNot', 'exists', 'doesntExist',
                'wasChanged', 'wasRecentlyCreated', 'getDirty', 'getChanges',
                'syncChanges', 'syncOriginal', 'syncAttributes', 'fill', 'forceFill',
                'getMutatedAttributes', 'toArray', 'toJson', 'jsonSerialize',
                'serializeForFrontend', 'getRouteKey', 'getRouteKeyName',
                'resolveRouteBinding', 'resolveChildRouteBinding',
                'getDurationInDays', // Skip methods that might cause issues
            ];

            if (in_array($method->getName(), $skipMethods)) {
                continue;
            }

            try {
                $return = $method->invoke($model);

                if (is_object($return)) {
                    $returnClass = get_class($return);

                    // Check if this is a relationship
                    if (
                        is_a($return, 'Illuminate\Database\Eloquent\Relations\Relation') ||
                        is_a($return, 'Illuminate\Database\Eloquent\Relations\BelongsTo') ||
                        is_a($return, 'Illuminate\Database\Eloquent\Relations\HasOne') ||
                        is_a($return, 'Illuminate\Database\Eloquent\Relations\HasMany') ||
                        is_a($return, 'Illuminate\Database\Eloquent\Relations\BelongsToMany') ||
                        is_a($return, 'Illuminate\Database\Eloquent\Relations\HasManyThrough') ||
                        is_a($return, 'Illuminate\Database\Eloquent\Relations\MorphTo') ||
                        is_a($return, 'Illuminate\Database\Eloquent\Relations\MorphOne') ||
                        is_a($return, 'Illuminate\Database\Eloquent\Relations\MorphMany') ||
                        is_a($return, 'Illuminate\Database\Eloquent\Relations\MorphToMany')
                    ) {
                        $relationName = $method->getName();

                        // Get the related model class
                        $relatedModel = null;

                        if (method_exists($return, 'getRelated')) {
                            $relatedModel = $return->getRelated();
                        }

                        if ($relatedModel) {
                            $relatedModelName = class_basename($relatedModel);

                            // Determine if it's a "to many" relationship
                            $isToMany = (
                                is_a($return, 'Illuminate\Database\Eloquent\Relations\HasMany') ||
                                is_a($return, 'Illuminate\Database\Eloquent\Relations\BelongsToMany') ||
                                is_a($return, 'Illuminate\Database\Eloquent\Relations\HasManyThrough') ||
                                is_a($return, 'Illuminate\Database\Eloquent\Relations\MorphMany') ||
                                is_a($return, 'Illuminate\Database\Eloquent\Relations\MorphToMany')
                            );

                            if ($isToMany) {
                                $relationships[$relationName] = "{$relatedModelName}[] | null";
                            } else {
                                $relationships[$relationName] = "{$relatedModelName} | null";
                            }
                        } else {
                            // If we can't determine the related model, use a generic type
                            $relationships[$relationName] = 'any | null';
                        }
                    }
                }
            } catch (\Exception $e) {
                // Skip methods that throw exceptions when called without parameters
                continue;
            } catch (\Error $e) {
                // Also catch PHP errors like null pointer exceptions
                continue;
            }
        }

        return $relationships;
    }

    /**
     * Get the model's accessor properties.
     *
     * @param  Model  $model  The model instance
     * @param  \ReflectionClass  $reflection  Reflection of the model class
     * @return array Accessors with their types
     */
    private function getModelAccessors(Model $model, \ReflectionClass $reflection): array
    {
        $accessors = [];
        $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);

        foreach ($methods as $method) {
            $methodName = $method->getName();

            // Check if method is a getter (starts with 'get' and ends with 'Attribute')
            if (preg_match('/^get([A-Z][a-zA-Z0-9]*)Attribute$/', $methodName, $matches)) {
                $attributeName = Str::lcfirst($matches[1]);
                $accessors[$attributeName] = $this->inferAccessorType($method);
            }
        }

        return $accessors;
    }

    /**
     * Infer TypeScript type from model attribute.
     *
     * @param  string  $attribute  The attribute name
     * @param  Model  $model  The model instance
     * @return string TypeScript type
     */
    private function inferTypeFromAttribute(string $attribute, Model $model): string
    {
        // Check if the attribute has a cast
        $casts = $model->getCasts();
        if (isset($casts[$attribute])) {
            return $this->mapPhpTypeToTypeScript($casts[$attribute]);
        }

        // Check if this is a foreign key (ends with _id)
        if (Str::endsWith($attribute, '_id')) {
            // Try to find the related model to check if it uses UUIDs
            $relationshipName = Str::camel(Str::beforeLast($attribute, '_id'));

            if (method_exists($model, $relationshipName)) {
                try {
                    $relation = $model->$relationshipName();
                    if (method_exists($relation, 'getRelated')) {
                        $relatedModel = $relation->getRelated();
                        $relatedUsesUuids = in_array('Illuminate\Database\Eloquent\Concerns\HasUuids', class_uses_recursive($relatedModel));
                        if ($relatedUsesUuids) {
                            return 'string | null';
                        }
                    }
                } catch (\Exception $e) {
                    // Relationship method might fail, continue with default
                }
            }

            return 'number | null';
        }

        // Infer from common attribute naming patterns
        if (in_array($attribute, ['id', 'created_by', 'updated_by', 'deleted_by', 'user_id', 'parent_id'])) {
            return 'number';
        }

        // Infer from common date field patterns
        if (in_array($attribute, [
            'created_at', 'updated_at', 'deleted_at', 'published_at', 'start_date',
            'end_date', 'date', 'birth_date', 'expiry_date', 'due_date',
        ]) || Str::endsWith($attribute, '_at') || Str::endsWith($attribute, '_date')) {
            return 'string | null';
        }

        // Infer from common boolean field patterns
        if (Str::startsWith($attribute, 'is_') || Str::startsWith($attribute, 'has_') ||
            in_array($attribute, ['active', 'enabled', 'visible', 'published', 'featured'])) {
            return 'boolean';
        }

        // Check for numeric field patterns directly in the attribute name
        $numericPatterns = [
            'amount', 'price', 'cost', 'total', 'quantity', 'count', 'size', 'width',
            'height', 'length', 'weight', 'duration', 'rating', 'position', 'order',
            'charge', 'fee', 'rate', 'discount', 'tax', 'balance', 'limit', 'max', 'min',
        ];

        foreach ($numericPatterns as $pattern) {
            if (Str::contains(strtolower($attribute), $pattern)) {
                return 'number | null';
            }
        }

        // Check for common numeric field suffixes
        if (Str::endsWith($attribute, '_count') || Str::endsWith($attribute, '_amount') ||
            Str::endsWith($attribute, '_price') || Str::endsWith($attribute, '_cost') ||
            Str::endsWith($attribute, '_total') || Str::endsWith($attribute, '_quantity') ||
            Str::endsWith($attribute, '_size') || Str::endsWith($attribute, '_rating') ||
            Str::endsWith($attribute, '_charge') || Str::endsWith($attribute, '_fee') ||
            Str::endsWith($attribute, '_rate') || Str::endsWith($attribute, '_limit')) {
            return 'number | null';
        }

        // Try to infer from the attribute value
        $value = $model->getAttribute($attribute);
        if (is_null($value)) {
            // For null values, try to infer from the attribute name
            if (Str::contains($attribute, ['description', 'content', 'text', 'name', 'title', 'label',
                'address', 'email', 'phone', 'url', 'path', 'location', 'notes', 'comment'])) {
                return 'string | null';
            }

            // Default to string | null for most text fields
            return 'string | null';
        }

        return match (gettype($value)) {
            'boolean' => 'boolean',
            'integer' => 'number',
            'double' => 'number',
            'string' => 'string',
            'array' => 'any[]',
            'object' => 'Record<string, any>',
            default => 'any',
        };
    }

    /**
     * Map PHP type to TypeScript type.
     */
    private function mapPhpTypeToTypeScript(string $phpType): string
    {
        // Handle decimal with precision (e.g., decimal:2)
        if (Str::startsWith($phpType, 'decimal:')) {
            return 'number | null';
        }

        $typeMap = [
            'int' => 'number',
            'integer' => 'number',
            'float' => 'number',
            'double' => 'number',
            'decimal' => 'number',
            'bool' => 'boolean',
            'boolean' => 'boolean',
            'string' => 'string',
            'array' => 'Record<string, any>',
            'object' => 'Record<string, any>',
            'mixed' => 'any',
            'Carbon\\Carbon' => 'string',
            'Illuminate\\Support\\Carbon' => 'string',
            'DateTime' => 'string',
            'Illuminate\\Support\\Collection' => 'any[]',
            'json' => 'Record<string, any>',
            'collection' => 'any[]',
            'date' => 'string',
            'datetime' => 'string',
            'timestamp' => 'string',
            'time' => 'string',
            'encrypted' => 'string',
            'hashed' => 'string',
            'uuid' => 'string',
        ];

        // Handle nullable types (e.g., ?string)
        if (Str::startsWith($phpType, '?')) {
            $baseType = Str::substr($phpType, 1);

            return ($typeMap[$baseType] ?? 'any').' | null';
        }

        // Handle array types (e.g., string[])
        if (Str::contains($phpType, '[]')) {
            $baseType = Str::replace('[]', '', $phpType);

            return ($typeMap[$baseType] ?? 'any').'[]';
        }

        // Handle Laravel cast types
        if (Str::contains($phpType, '\\')) {
            // Check if it's a custom cast class
            if (Str::contains($phpType, 'AsCollection')) {
                return 'Record<string, any>[]';
            }
            if (Str::contains($phpType, 'AsArrayObject')) {
                return 'Record<string, any>';
            }
            if (Str::contains($phpType, 'AsEncrypted')) {
                return 'string';
            }
            if (Str::contains($phpType, 'AsEnumCollection')) {
                return 'string[]';
            }
            if (Str::contains($phpType, 'AsEnumArrayObject')) {
                return 'Record<string, string>';
            }
        }

        return $typeMap[$phpType] ?? 'any';
    }

    /**
     * Infer the return type of an accessor method.
     *
     * @param  \ReflectionMethod  $method  The accessor method
     * @return string TypeScript type
     */
    private function inferAccessorType(\ReflectionMethod $method): string
    {
        // Try to infer the return type
        $returnType = 'any';

        if ($method->hasReturnType()) {
            $type = $method->getReturnType();
            $returnType = $this->mapPhpTypeToTypeScript($type->getName());
        } else {
            // Try to infer from docblock
            $docComment = $method->getDocComment();
            if ($docComment && preg_match('/@return\s+(\S+)/', $docComment, $matches)) {
                $returnType = $this->mapPhpTypeToTypeScript($matches[1]);
            }
        }

        return $returnType;
    }

    /**
     * Generate TypeScript interface for a model.
     *
     * @param  array<string, string>  $properties
     * @param  array<string, string>  $relations
     */
    private function generateTypeScriptInterface(string $modelName, array $properties, array $relations): string
    {
        // Start building the TypeScript file
        $content = "/**\n";
        $content .= " * TypeScript definition for the {$modelName} model\n";
        $content .= " * Auto-generated by hg:generate:typescript\n";
        $content .= " */\n\n";

        // Generate imports from relations
        $imports = $this->generateImports($modelName, $relations);
        if (! empty($imports)) {
            $content .= $imports."\n";
        }

        // Add interface definition
        $content .= "export interface {$modelName} {\n";

        // Add properties
        foreach ($properties as $propertyName => $propertyType) {
            // Convert snake_case to camelCase for property names
            $camelCasePropertyName = Str::camel($propertyName);

            // Skip foreign key fields if a corresponding relationship exists
            if ($this->shouldSkipForeignKeyField($propertyName, $relations)) {
                continue;
            }

            $content .= "    readonly {$camelCasePropertyName}: {$propertyType};\n";
        }

        // Add relations
        foreach ($relations as $relationName => $relationType) {
            $content .= "    readonly {$relationName}: {$relationType};\n";
        }

        $content .= "}\n";

        return $content;
    }

    /**
     * Generate imports for the model.
     *
     * @param  array<string, string>  $relations
     */
    private function generateImports(string $modelName, array $relations): string
    {
        $imports = [];

        foreach ($relations as $relationName => $relationType) {
            // Extract base type without nullable or array notation
            if (preg_match('/^([A-Za-z0-9_]+)(\[\])?\s*(\|\s*null)?$/', $relationType, $matches)) {
                $relatedModel = $matches[1];

                // Skip primitive types
                if (in_array($relatedModel, ['string', 'number', 'boolean', 'any', 'object'])) {
                    continue;
                }

                // Skip the current model
                if ($relatedModel === $modelName) {
                    continue;
                }

                // Add to imports if not already there
                if (! in_array($relatedModel, $imports)) {
                    $imports[] = $relatedModel;
                }
            }
        }

        // Sort imports alphabetically
        sort($imports);

        // Add grouped imports from @/types
        if (! empty($imports)) {
            return 'import type { '.implode(', ', $imports)." } from '@/types';\n";
        }

        return '';
    }

    /**
     * Get the plural function name for a model.
     */
    private function getPluralFunctionName(string $modelName): string
    {
        return Str::studly(Str::plural($modelName));
    }

    /**
     * Get the plural variable name for a model.
     */
    private function getPluralVariableName(string $modelName): string
    {
        return Str::camel(Str::plural($modelName));
    }

    /**
     * Check if a type is a model type.
     */
    private function isModelType(string $type): bool
    {
        // Check if the type is likely a model (has uppercase first letter and no "Resource" suffix)
        return preg_match('/^[A-Z][a-zA-Z0-9_]*$/', $type) && strpos($type, 'Resource') === false;
    }

    /**
     * Determine if a foreign key field should be skipped because a relationship exists.
     */
    private function shouldSkipForeignKeyField(string $fieldName, array $relationships): bool
    {
        $camelCaseField = Str::camel($fieldName);

        // Check if this field name would conflict with any relationship name
        foreach ($relationships as $relationshipName => $relationType) {
            // Only skip if the camelCase field name exactly matches the relationship name
            if ($camelCaseField === $relationshipName) {
                return true;
            }
        }

        return false;
    }

    /**
     * Ensure a directory exists.
     */
    private function ensureDirectoryExists(string $path): void
    {
        if (! is_dir($path)) {
            mkdir($path, 0777, true);
        }
    }
}
