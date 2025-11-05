<?php

namespace App\Console\Commands\Shared;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Log;
use ReflectionClass;
use ReflectionMethod;

class ModelAnalyser
{
    /**
     * Analyse a model class to extract its properties and relationships.
     */
    public function analyse(string $modelClass): array
    {
        // Create a model instance
        $model = new $modelClass;

        // Extract model properties
        $properties = [
            'class' => $modelClass,
            'table' => $model->getTable(),
            'primaryKey' => $model->getKeyName(),
            'fillable' => $model->getFillable(),
            'hidden' => $model->getHidden(),
            'appends' => $model->getAppends(),
            'casts' => $model->getCasts(),
            'dates' => $this->extractDateFields($model),
        ];

        // Extract relationships
        $properties['relationships'] = $this->extractRelationships($modelClass);

        // Extract custom accessors
        $properties['accessors'] = $this->extractAccessors($modelClass);

        return $properties;
    }

    /**
     * Extract date fields from a model.
     */
    protected function extractDateFields(Model $model): array
    {
        $dates = [];

        // Get explicitly defined dates
        if (method_exists($model, 'getDates')) {
            $dates = array_merge($dates, $model->getDates());
        }

        // Get fields cast as dates
        $dateCastTypes = ['date', 'datetime', 'timestamp', 'immutable_date', 'immutable_datetime'];
        foreach ($model->getCasts() as $field => $cast) {
            if (in_array($cast, $dateCastTypes)) {
                $dates[] = $field;
            }
        }

        // Always include timestamps if the model uses them
        if ($model->usesTimestamps()) {
            $dates[] = $model->getCreatedAtColumn();
            $dates[] = $model->getUpdatedAtColumn();
        }

        // Check for soft deletes
        if (in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses_recursive($model))) {
            $dates[] = $model->getDeletedAtColumn() ?: 'deleted_at';
        }

        return array_unique($dates);
    }

    /**
     * Extract relationships from a model class.
     */
    protected function extractRelationships(string $modelClass): array
    {
        $relationships = [];

        // First try to extract relationships by return type
        $this->extractRelationshipsByReturnType($modelClass, $relationships);

        // If that doesn't find all relationships, try by method body
        $this->extractRelationshipsByMethodBody($modelClass, $relationships);

        return $relationships;
    }

    /**
     * Extract relationships by analysing method return types.
     */
    protected function extractRelationshipsByReturnType(string $modelClass, array &$relationships): void
    {
        try {
            $reflection = new ReflectionClass($modelClass);
            $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);

            foreach ($methods as $method) {
                $methodName = $method->getName();

                // Skip if it's a non-relationship method
                if ($this->isNonRelationshipMethod($methodName)) {
                    continue;
                }

                // Check for return type
                $returnType = $method->getReturnType();
                if ($returnType && ! $returnType->isBuiltin()) {
                    $typeName = $returnType->getName();

                    // Check if it's a relationship return type
                    if ($this->isRelationshipReturnType($typeName)) {
                        $relationType = class_basename($typeName);

                        // Try to extract the related model from the method body
                        $methodBody = $this->getMethodBody($method);
                        $relatedModel = $this->extractRelatedModelFromMethodBody($methodBody);

                        // If we couldn't extract from method body, try to infer from method name
                        if (! $relatedModel) {
                            $relatedModel = $this->inferRelatedModelFromMethodName($methodName);
                        }

                        if ($relatedModel) {
                            $relationships[$methodName] = [
                                'name' => $methodName,
                                'type' => $relationType,
                                'related' => $relatedModel,
                                'resource' => $this->getResourceClassForRelation($relatedModel),
                                'isCollection' => $this->isCollectionRelationship($relationType),
                            ];
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            // Log the error but continue
            Log::error("Error extracting relationships by return type for {$modelClass}: ".$e->getMessage());
        }
    }

    /**
     * Extract relationships by analysing method body content.
     */
    protected function extractRelationshipsByMethodBody(string $modelClass, array &$relationships): void
    {
        $reflection = new ReflectionClass($modelClass);
        $fileName = $reflection->getFileName();

        if (! $fileName || ! file_exists($fileName)) {
            return;
        }

        $fileContent = file_get_contents($fileName);

        // Look for common relationship method patterns
        $relationPatterns = [
            'hasOne' => '/function\s+([a-zA-Z0-9_]+)\s*\(\s*\)\s*\{(?:[\s\S]*?)return\s+\$this->hasOne\s*\(\s*([^,\)]+)/i',
            'hasMany' => '/function\s+([a-zA-Z0-9_]+)\s*\(\s*\)\s*\{(?:[\s\S]*?)return\s+\$this->hasMany\s*\(\s*([^,\)]+)/i',
            'belongsTo' => '/function\s+([a-zA-Z0-9_]+)\s*\(\s*\)\s*\{(?:[\s\S]*?)return\s+\$this->belongsTo\s*\(\s*([^,\)]+)/i',
            'belongsToMany' => '/function\s+([a-zA-Z0-9_]+)\s*\(\s*\)\s*\{(?:[\s\S]*?)return\s+\$this->belongsToMany\s*\(\s*([^,\)]+)/i',
            'morphTo' => '/function\s+([a-zA-Z0-9_]+)\s*\(\s*\)\s*\{(?:[\s\S]*?)return\s+\$this->morphTo\s*\(/i',
            'morphOne' => '/function\s+([a-zA-Z0-9_]+)\s*\(\s*\)\s*\{(?:[\s\S]*?)return\s+\$this->morphOne\s*\(\s*([^,\)]+)/i',
            'morphMany' => '/function\s+([a-zA-Z0-9_]+)\s*\(\s*\)\s*\{(?:[\s\S]*?)return\s+\$this->morphMany\s*\(\s*([^,\)]+)/i',
            'morphToMany' => '/function\s+([a-zA-Z0-9_]+)\s*\(\s*\)\s*\{(?:[\s\S]*?)return\s+\$this->morphToMany\s*\(\s*([^,\)]+)/i',
            'hasManyThrough' => '/function\s+([a-zA-Z0-9_]+)\s*\(\s*\)\s*\{(?:[\s\S]*?)return\s+\$this->hasManyThrough\s*\(\s*([^,\)]+)/i',
            'hasOneThrough' => '/function\s+([a-zA-Z0-9_]+)\s*\(\s*\)\s*\{(?:[\s\S]*?)return\s+\$this->hasOneThrough\s*\(\s*([^,\)]+)/i',
        ];

        foreach ($relationPatterns as $relationType => $pattern) {
            preg_match_all($pattern, $fileContent, $matches, PREG_SET_ORDER);

            foreach ($matches as $match) {
                $methodName = $match[1];
                $relatedModel = $match[2] ?? null;

                // Skip if we already have this relationship
                if (isset($relationships[$methodName])) {
                    continue;
                }

                // Try to extract the model class from the match
                if ($relatedModel) {
                    $relatedModel = trim($relatedModel, '\'"');

                    // Handle ::class syntax
                    if (Str::endsWith($relatedModel, '::class')) {
                        $relatedModel = str_replace('::class', '', $relatedModel);
                    }

                    // If it's not a fully qualified class name, assume it's in App\Models
                    if (! Str::contains($relatedModel, '\\')) {
                        $relatedModel = "App\\Models\\{$relatedModel}";
                    }
                } else {
                    // Try to infer from method name
                    $relatedModel = $this->inferRelatedModelFromMethodName($methodName);
                }

                if ($relatedModel) {
                    // Convert relationship type to proper casing (e.g., hasManyThrough -> HasManyThrough)
                    $formattedType = str_replace(' ', '', ucwords(str_replace(['_', '-'], ' ', $relationType)));

                    $relationships[$methodName] = [
                        'name' => $methodName,
                        'type' => $formattedType,
                        'related' => $relatedModel,
                        'resource' => $this->getResourceClassForRelation($relatedModel),
                        'isCollection' => $this->isCollectionRelationship($formattedType),
                    ];
                }
            }
        }

        // Fallback: Try to extract relationships from method return types
        $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
        foreach ($methods as $method) {
            $methodName = $method->getName();

            // Skip if we already have this relationship or if it's a non-relationship method
            if (isset($relationships[$methodName]) || $this->isNonRelationshipMethod($methodName)) {
                continue;
            }

            // Check method return type
            $returnType = $method->getReturnType();
            if ($returnType && $this->isRelationshipReturnType($returnType->getName())) {
                $relationType = class_basename($returnType->getName());

                // Try to infer related model from method name
                $relatedModel = $this->inferRelatedModelFromMethodName($methodName);
                if (! $relatedModel) {
                    // Try to infer from method body
                    $methodBody = $this->getMethodBody($method);
                    $relatedModel = $this->extractRelatedModelFromMethodBody($methodBody);
                }

                if ($relatedModel) {
                    $relationships[$methodName] = [
                        'name' => $methodName,
                        'type' => $relationType,
                        'related' => $relatedModel,
                        'resource' => $this->getResourceClassForRelation($relatedModel),
                        'isCollection' => $this->isCollectionRelationship($relationType),
                    ];
                }
            }
        }
    }

    /**
     * Check if a type is a relationship return type.
     */
    protected function isRelationshipReturnType(string $type): bool
    {
        $relationshipTypes = [
            'Illuminate\Database\Eloquent\Relations\HasOne',
            'Illuminate\Database\Eloquent\Relations\HasMany',
            'Illuminate\Database\Eloquent\Relations\BelongsTo',
            'Illuminate\Database\Eloquent\Relations\BelongsToMany',
            'Illuminate\Database\Eloquent\Relations\MorphTo',
            'Illuminate\Database\Eloquent\Relations\MorphOne',
            'Illuminate\Database\Eloquent\Relations\MorphMany',
            'Illuminate\Database\Eloquent\Relations\MorphToMany',
            'Illuminate\Database\Eloquent\Relations\HasManyThrough',
            'Illuminate\Database\Eloquent\Relations\HasOneThrough',
        ];

        foreach ($relationshipTypes as $relationshipType) {
            if ($type === $relationshipType || is_subclass_of($type, $relationshipType)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a method name is a common non-relationship method.
     */
    protected function isNonRelationshipMethod(string $methodName): bool
    {
        $nonRelationshipMethods = [
            'toArray', 'jsonSerialize', 'toJson', 'getFillable', 'getHidden',
            'getTable', 'getAttributes', 'getKey', 'getKeyName', 'getCasts',
            'getRouteKey', 'getRouteKeyName', 'getIncrementing', 'getConnection',
            'getPerPage', 'getQualifiedKeyName', 'getRelations', 'getAppends',
            'boot', 'booted', 'newCollection', 'newQuery', 'newModelQuery',
            'query', 'with', 'withoutGlobalScopes', 'withoutTrashed', 'withTrashed',
            'onlyTrashed', 'trashed', 'delete', 'forceDelete', 'restore',
            'replicate', 'is', 'isNot', 'getOriginal', 'getChanges',
            'getDirty', 'getAttributes', 'getAttribute', 'setAttribute',
            'fill', 'save', 'update', 'push', 'touch', 'timestamps',
            'usesTimestamps', 'freshTimestamp', 'freshTimestampString',
            'fromDateTime', 'fromJson', 'qualifyColumn', 'getMutatedAttributes',
            'relationLoaded', 'load', 'loadMissing', 'loadCount', 'loadMorph',
            'loadMorphCount', 'exists', 'wasRecentlyCreated', 'wasChanged',
            'syncOriginal', 'syncChanges', 'getConnectionName', 'setConnection',
            'getTable', 'setTable', 'getKeyName', 'setKeyName', 'getKeyType',
            'setKeyType', 'getIncrementing', 'setIncrementing', 'getCreatedAtColumn',
            'getUpdatedAtColumn', 'setCreatedAtColumn', 'setUpdatedAtColumn',
            'getDeletedAtColumn', 'setDeletedAtColumn', 'getQualifiedKeyName',
            'getQualifiedCreatedAtColumn', 'getQualifiedUpdatedAtColumn',
            'getQualifiedDeletedAtColumn', 'getDateFormat', 'setDateFormat',
            'fromDateTime', 'asDateTime', 'getConnectionResolver', 'setConnectionResolver',
            'unsetConnectionResolver', 'getEventDispatcher', 'setEventDispatcher',
            'unsetEventDispatcher', 'getMorphClass', 'getObservableEvents',
            'getGlobalScopes', 'hasGlobalScope', 'withGlobalScope', 'withoutGlobalScope',
            'observe', 'getObservableEvents', 'setObservableEvents', 'addObservableEvents',
            'removeObservableEvents', 'getRelationValue', 'getRelationshipFromMethod',
            'hasGetMutator', 'hasSetMutator', 'hasAttributeMutator', 'hasCast',
            'getCasts', 'mergeCasts', 'getAttributes', 'getAttributeValue',
            'getRelationValue', 'getRelationshipFromMethod', 'hasGetMutator',
            'hasSetMutator', 'hasAttributeMutator', 'hasCast', 'getCasts',
            'mergeCasts', 'getAttributes', 'getAttributeValue', 'getRelationValue',
            'getRelationshipFromMethod', 'hasGetMutator', 'hasSetMutator',
            'hasAttributeMutator', 'hasCast', 'getCasts', 'mergeCasts',
            'getAttributes', 'getAttributeValue', 'getRelationValue',
            'getRelationshipFromMethod', 'hasGetMutator', 'hasSetMutator',
            'hasAttributeMutator', 'hasCast', 'getCasts', 'mergeCasts',
        ];

        // Explicitly exclude accessor methods (getXxxAttribute pattern)
        if (preg_match('/^get(.+)Attribute$/', $methodName)) {
            return true;
        }

        return in_array($methodName, $nonRelationshipMethods) ||
               Str::startsWith($methodName, ['get', 'set', 'scope', 'is', 'has', 'can']);
    }

    /**
     * Infer the related model from a method name.
     */
    protected function inferRelatedModelFromMethodName(string $methodName): ?string
    {
        // Convert method name to singular studly case
        $modelName = Str::studly(Str::singular($methodName));

        // Check if the model exists
        $modelClass = "App\\Models\\{$modelName}";
        if (class_exists($modelClass)) {
            return $modelClass;
        }

        return null;
    }

    /**
     * Extract related model from method body.
     */
    protected function extractRelatedModelFromMethodBody(string $methodBody): ?string
    {
        // Try to extract model class from method body
        $patterns = [
            // Match return $this->hasMany(Model::class)
            '/return\s+\$this->\w+\s*\(\s*([^,\)]+)/',
            // Match return $this->hasMany(Model::class, 'foreign_key')
            '/return\s+\$this->\w+\s*\(\s*([^,\)]+)\s*,/',
            // Match new patterns for different code styles
            '/\$this->\w+\s*\(\s*([^,\)]+)/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $methodBody, $matches)) {
                $relatedModel = trim($matches[1], '\'"');

                // Handle Jetstream model method calls
                if (Str::contains($relatedModel, 'Jetstream::') && Str::contains($relatedModel, 'Model')) {
                    // Extract the method name (e.g., teamModel, userModel)
                    if (preg_match('/Jetstream::(\w+Model)/', $relatedModel, $jetstreamMatches)) {
                        $methodName = $jetstreamMatches[1];

                        // Map Jetstream method names to actual model classes
                        $jetstreamModels = [
                            'teamModel' => 'App\\Models\\Team',
                            'userModel' => 'App\\Models\\User',
                            'membershipModel' => 'App\\Models\\Membership',
                            'teamInvitationModel' => 'App\\Models\\TeamInvitation',
                        ];

                        if (isset($jetstreamModels[$methodName])) {
                            return $jetstreamModels[$methodName];
                        }
                    }

                    // If we can't resolve it, try to infer from the method name
                    return null;
                }

                // Handle ::class syntax
                if (Str::endsWith($relatedModel, '::class')) {
                    $relatedModel = str_replace('::class', '', $relatedModel);
                }

                // If it's not a fully qualified class name, assume it's in App\Models
                if (! Str::contains($relatedModel, '\\')) {
                    $relatedModel = "App\\Models\\{$relatedModel}";
                }

                return $relatedModel;
            }
        }

        return null;
    }

    /**
     * Get the method body content.
     */
    protected function getMethodBody(ReflectionMethod $method): string
    {
        $fileName = $method->getFileName();
        $startLine = $method->getStartLine();
        $endLine = $method->getEndLine();

        if (! $fileName || ! file_exists($fileName)) {
            return '';
        }

        $content = file_get_contents($fileName);
        $lines = explode("\n", $content);

        $methodBody = implode("\n", array_slice($lines, $startLine - 1, $endLine - $startLine + 1));

        return $methodBody;
    }

    /**
     * Get the resource class name for a related model.
     */
    protected function getResourceClassForRelation(string $relatedModel): string
    {
        $modelName = class_basename($relatedModel);

        return "{$modelName}Resource";
    }

    /**
     * Determine if a relationship returns a collection.
     */
    protected function isCollectionRelationship(string $relationType): bool
    {
        return in_array($relationType, [
            'HasMany', 'BelongsToMany', 'MorphMany', 'HasManyThrough', 'MorphToMany',
        ]);
    }

    /**
     * Analyse a model file using token_get_all to extract all code parts for canonical rebuilding.
     * Returns an array of categorized code blocks: namespace, uses, classDoc, traits, properties, relationships, accessors, mutators, scopes, methods, footer.
     * Also returns an associative array of docblocks keyed by property/method name for later output.
     */
    public function analyseFileWithTokens($file)
    {
        $parts = [
            'namespace' => '',
            'uses' => '',
            'classDoc' => '',
            'classHeader' => '',
            'footer' => '',
            'traits' => '',
            'properties' => '',
            'accessors' => '',
            'mutators' => '',
            'relationships' => '',
            'scopes' => '',
            'methods' => '',
        ];
        $docblocks = []; // Declare $docblocks as an array
        $relationshipProxies = [];
        $src = file_get_contents($file);
        $tokens = token_get_all($src);
        $n = count($tokens);
        $i = 0;
        // --- Namespace ---
        while ($i < $n) {
            $token = $tokens[$i];
            if (is_array($token) && $token[0] === T_NAMESPACE) {
                $ns = '';
                $i++;
                while ($i < $n && $tokens[$i] !== ';') {
                    $ns .= is_array($tokens[$i]) ? $tokens[$i][1] : $tokens[$i];
                    $i++;
                }
                $parts['namespace'] = 'namespace '.trim($ns).';';
                break;
            }
            $i++;
        }
        // --- Uses ---
        $parts['uses'] = $this->extractUsesSection($src);

        // --- Class Docblock & Header ---
        $i = 0;
        $classDoc = '';
        $classHeader = '';
        while ($i < $n) {
            $token = $tokens[$i];
            if (is_array($token) && $token[0] === T_DOC_COMMENT) {
                $classDoc = $token[1];
                $i++;

                continue;
            }
            if (is_array($token) && $token[0] === T_CLASS) {
                $header = $classDoc;
                $classDoc = '';
                while ($i < $n && $tokens[$i] !== '{') {
                    $header .= is_array($tokens[$i]) ? $tokens[$i][1] : $tokens[$i];
                    $i++;
                }
                $classHeader = trim($header);
                break;
            }
            $i++;
        }
        $parts['classDoc'] = $classDoc;
        $parts['classHeader'] = $classHeader;

        // --- Class Body & Sections ---
        // Find class start and end
        $classStart = null;
        $classEnd = null;
        $braceDepth = 0;
        for ($i = 0; $i < $n; $i++) {
            if (is_array($tokens[$i]) && $tokens[$i][0] === T_CLASS) {
                // Find first brace after class
                while ($i < $n && $tokens[$i] !== '{') {
                    $i++;
                }
                $classStart = $i;
                $braceDepth = 1;
                $i++; // Move past the opening brace
                break;
            }
        }
        if ($classStart === null) {
            // No class found
            return $parts;
        }
        // Find class end (matching closing brace)
        for (; $i < $n; $i++) {
            if ($tokens[$i] === '{') {
                $braceDepth++;
            } elseif ($tokens[$i] === '}') {
                $braceDepth--;
            }
            if ($braceDepth === 0) {
                $classEnd = $i;
                break;
            }
        }
        // --- Extract class body tokens ---
        $bodyTokens = array_slice($tokens, $classStart + 1, $classEnd - $classStart - 1);
        $m = count($bodyTokens);
        // --- Section Extraction and Docblock Association ---
        $sectionMap = [
            'traits' => '',
            'properties' => '',
            'accessors' => '',
            'mutators' => '',
            'relationships' => '',
            'scopes' => '',
            'methods' => '',
        ];
        $docblocks = [];
        $currentDoc = '';
        $braceDepth = 0;
        $i = 0;
        while ($i < $m) {
            $token = $bodyTokens[$i];
            // Track braces for top-level detection
            if ($token === '{') {
                $braceDepth++;
            } elseif ($token === '}') {
                $braceDepth--;
            }
            // Robust docblock association (methods/properties)
            if (is_array($token) && $token[0] === T_DOC_COMMENT) {
                // Lookahead up to 6 tokens to find property or method
                $lookahead = $i + 1;
                $maxLookahead = $i + 6;
                while ($lookahead < $m && $lookahead < $maxLookahead) {
                    $next = $bodyTokens[$lookahead];
                    // Skip whitespace/comments/attributes/modifiers
                    if (is_array($next) && in_array($next[0], [T_WHITESPACE, T_COMMENT, T_ATTRIBUTE, T_FINAL, T_ABSTRACT, T_STATIC])) {
                        $lookahead++;

                        continue;
                    }
                    // Property: visibility + variable
                    if (is_array($next) && in_array($next[0], [T_PUBLIC, T_PROTECTED, T_PRIVATE, T_VAR])) {
                        $j = $lookahead + 1;
                        while ($j < $m && is_array($bodyTokens[$j]) && in_array($bodyTokens[$j][0], [T_WHITESPACE, T_COMMENT, T_ATTRIBUTE])) {
                            $j++;
                        }
                        if ($j < $m && is_array($bodyTokens[$j]) && $bodyTokens[$j][0] === T_VARIABLE) {
                            $name = ltrim($bodyTokens[$j][1], '$');
                            if ($name) {
                                $docblocks['property:'.$name] = trim($token[1]);
                            }
                            break;
                        }
                        $lookahead = $j;

                        continue;
                    }
                    // Method: visibility + function
                    if (is_array($next) && $next[0] === T_FUNCTION) {
                        $k = $lookahead + 1;
                        while ($k < $m && ((is_array($bodyTokens[$k]) && in_array($bodyTokens[$k][0], [T_WHITESPACE, T_STATIC])) || $bodyTokens[$k] === '&')) {
                            $k++;
                        }
                        if ($k < $m && is_array($bodyTokens[$k]) && $bodyTokens[$k][0] === T_STRING) {
                            $fnName = $bodyTokens[$k][1];
                            if ($fnName) {
                                $docblocks['method:'.$fnName] = trim($token[1]);
                            }
                            break;
                        }
                        $lookahead = $k;

                        continue;
                    }
                    break;
                }
            }
            // Traits (use inside class, not global)
            if (is_array($token) && $token[0] === T_USE && $braceDepth === 0) {
                $trait = '';
                $currentDoc = '';
                while ($i < $m && $bodyTokens[$i] !== ';') {
                    $trait .= is_array($bodyTokens[$i]) ? $bodyTokens[$i][1] : $bodyTokens[$i];
                    $i++;
                }
                if ($i < $m && $bodyTokens[$i] === ';') {
                    $trait .= ';';
                    $i++;
                }
                $sectionMap['traits'] .= trim($trait)."\n";

                continue;
            }
            // Properties
            if (is_array($token) && in_array($token[0], [T_PUBLIC, T_PROTECTED, T_PRIVATE, T_VAR]) && $braceDepth === 0) {
                $prop = '';
                $propertyIsValid = true;
                $name = '';
                $j = $i + 1;
                while ($j < $m && is_array($bodyTokens[$j]) && in_array($bodyTokens[$j][0], [T_WHITESPACE, T_COMMENT, T_ATTRIBUTE])) {
                    $j++;
                }
                if ($j < $m && is_array($bodyTokens[$j]) && $bodyTokens[$j][0] === T_VARIABLE) {
                    $name = ltrim($bodyTokens[$j][1], '$');
                    if ($name && trim($currentDoc)) {
                        $docblocks['property:'.$name] = trim($currentDoc);
                    }
                }
                $currentDoc = '';
                while ($i < $m && $bodyTokens[$i] !== ';') {
                    if (is_array($bodyTokens[$i]) && $bodyTokens[$i][0] === T_FUNCTION) {
                        $propertyIsValid = false;
                        break;
                    }
                    $prop .= is_array($bodyTokens[$i]) ? $bodyTokens[$i][1] : $bodyTokens[$i];
                    $i++;
                }
                if ($i < $m && $bodyTokens[$i] === ';') {
                    $prop .= ';';
                    $i++;
                }
                if ($propertyIsValid) {
                    $sectionMap['properties'] .= trim($prop)."\n";
                }

                continue;
            }
            // Methods (including relationships)
            if (is_array($token) && $token[0] === T_FUNCTION && $braceDepth === 0) {
                $lookahead = $i + 1;
                while ($lookahead < $m && ((is_array($bodyTokens[$lookahead]) && in_array($bodyTokens[$lookahead][0], [T_WHITESPACE, T_STATIC])) || $bodyTokens[$lookahead] === '&')) {
                    $lookahead++;
                }
                $fnName = '';
                if ($lookahead < $m && is_array($bodyTokens[$lookahead]) && $bodyTokens[$lookahead][0] === T_STRING) {
                    $fnName = $bodyTokens[$lookahead][1];
                }
                // Always associate docblock with every method (including relationships)
                if ($fnName && trim($currentDoc)) {
                    $docblocks['method:'.$fnName] = trim($currentDoc);
                }
                $fnCode = ($currentDoc ? $currentDoc."\n" : '').$this->gatherFunction($bodyTokens, $i);
                $currentDoc = '';
                // Categorize
                if (preg_match('/^get.+Attribute$/', $fnName)) {
                    $sectionMap['accessors'] .= $fnCode."\n";
                } elseif (preg_match('/^set.+Attribute$/', $fnName)) {
                    $sectionMap['mutators'] .= $fnCode."\n";
                } elseif (preg_match('/^scope.+$/', $fnName)) {
                    $sectionMap['scopes'] .= $fnCode."\n";
                } else {
                    $relResult = $this->isRelationshipMethod($fnCode);
                    if (is_array($relResult) && $relResult[0] === true) {
                        $sectionMap['relationships'] .= $fnCode."\n\n";
                        $relationshipProxies[$fnName] = $relResult[1];
                    } else {
                        // Ensure double newline between custom methods
                        $sectionMap['methods'] .= $fnCode."\n\n";
                    }
                }
                $i++;

                continue;
            }
            $i++;
        }
        foreach ($sectionMap as $section => $value) {
            $parts[$section] = trim($value);
        }
        $parts['docblocks'] = $docblocks;
        $parts['relationship_proxies'] = $relationshipProxies;

        return $parts;
    }

    /**
     * Get the return type of a function.
     */
    protected function getReturnType($fnCode)
    {
        // IMPROVE: Use more robust detection of Eloquent relationships
        // If the function body contains $this->hasOne(...), $this->belongsTo(...), etc., treat as relationship
        if (preg_match('/return\s+\$this->\s*(hasOne|hasMany|belongsTo|belongsToMany|morphTo|morphOne|morphMany|morphToMany|morphedByMany)\s*\(/i', $fnCode, $matches)) {
            return $matches[1];
        }

        return '';
    }

    /**
     * Gather full function code from tokens robustly.
     */
    protected function gatherFunction($tokens, &$i, $docblock = '')
    {
        $str = $docblock;
        $depth = 0;
        $foundBrace = false;
        $n = count($tokens);
        $start = $i;
        // Gather function signature
        while ($start < $n) {
            $tok = $tokens[$start];
            $str .= is_array($tok) ? $tok[1] : $tok;
            if ($tok === '{') {
                $depth = 1;
                $start++;
                break;
            }
            $start++;
        }
        // Gather function body (including nested braces)
        while ($start < $n && $depth > 0) {
            $tok = $tokens[$start];
            $str .= is_array($tok) ? $tok[1] : $tok;
            if ($tok === '{') {
                $depth++;
            } elseif ($tok === '}') {
                $depth--;
            }
            $start++;
        }
        $i = $start - 1;

        return $str;
    }

    /**
     * Relationship detection by return type and known calls.
     */
    protected function isRelationshipMethod($fn)
    {
        // 1. Direct Eloquent relationship
        $relationshipTypes = [
            'hasOne', 'hasMany', 'belongsTo', 'belongsToMany', 'morphOne', 'morphMany', 'morphTo', 'morphToMany',
            'hasManyThrough', 'hasOneThrough', 'morphOneThrough', 'morphManyThrough',
        ];
        foreach ($relationshipTypes as $rel) {
            if (preg_match('/return\\s+\\$this->'.$rel.'\\s*\\(/', $fn)) {
                return [true, ['$this', $rel]];
            }
        }
        // 2. Proxy relationships (track chain)
        // e.g. return $this->unitType->accommodation();
        //      return $this->unitType->accommodation->supplier();
        if (preg_match('/return\\s+\\$this->((?:\\w+->)+)(\\w+)\\s*\\(/', $fn, $matches)) {
            // $matches[1] is the chain (e.g. "unitType->accommodation->"), $matches[2] is the last method
            $chain = explode('->', rtrim($matches[1], '->'));
            $chain[] = $matches[2];
            array_unshift($chain, '$this');

            return [true, $chain];
        }
        // 3. Return type hint (e.g., : BelongsTo)
        if (preg_match('/function\\s+\\w+\\s*\\([^)]*\\)\\s*:\\s*([\\\\\\w]+)/', $fn, $m)) {
            $type = strtolower($m[1]);
            foreach ([
                'belongsto', 'hasmany', 'hasone', 'belongstomany', 'morphone', 'morphmany', 'morphto', 'morphtomany',
                'hasmanythrough', 'hasonethrough', 'morphonethrough', 'morphmanythrough',
            ] as $relType) {
                if (strpos($type, $relType) !== false) {
                    return [true, ['$this', $type]];
                }
            }
        }

        return false;
    }

    /**
     * Only collect use statements at file scope for imports, and only if namespaced.
     */
    protected function extractUsesSection($src)
    {
        $tokens = token_get_all($src);
        $uses = [];
        $i = 0;
        $n = count($tokens);
        while ($i < $n) {
            $token = $tokens[$i];
            if (is_array($token) && $token[0] === T_CLASS) {
                break; // Stop at class definition
            }
            if (is_array($token) && $token[0] === T_USE) {
                // Gather the entire use statement up to semicolon
                $useStmt = '';
                while ($i < $n && $tokens[$i] !== ';') {
                    $useStmt .= is_array($tokens[$i]) ? $tokens[$i][1] : $tokens[$i];
                    $i++;
                }
                if ($i < $n && $tokens[$i] === ';') {
                    $useStmt .= ';';
                }
                // Only include if it contains a namespace separator (not a trait)
                if (strpos($useStmt, '\\') !== false) {
                    $uses[] = trim($useStmt);
                }
            }
            $i++;
        }

        return implode("\n", $uses);
    }

    /**
     * Extract custom accessors from a model class.
     */
    protected function extractAccessors(string $modelClass): array
    {
        $accessors = [];

        try {
            $reflection = new ReflectionClass($modelClass);
            $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);

            foreach ($methods as $method) {
                $methodName = $method->getName();

                // Check if it's a custom accessor method (getXxxAttribute pattern)
                if (preg_match('/^get(.+)Attribute$/', $methodName, $matches)) {
                    $attributeName = Str::snake($matches[1]);

                    // Skip default Laravel accessors and common ones
                    $skipAccessors = [
                        'created_at',
                        'updated_at',
                        'deleted_at',
                        'timestamps',
                        'original',
                        'dirty',
                        'changes',
                        'attributes',
                        'fillable',
                        'hidden',
                        'visible',
                        'appends',
                        'casts',
                        'dates',
                        'table',
                        'connection',
                        'key_name',
                        'key_type',
                        'incrementing',
                        'per_page',
                        'exists',
                        'was_recently_created',
                    ];

                    if (in_array($attributeName, $skipAccessors)) {
                        continue;
                    }

                    // Check if this is a complex accessor that should be skipped
                    // Some collection accessors (like usedChannels) are virtual relationships that should be included
                    $accessorInfo = $this->analyseAccessor($method);
                    if ($accessorInfo['shouldSkip']) {
                        continue;
                    }

                    // Use the accessor info from our analysis
                    $accessors[] = [
                        'name' => $attributeName,
                        'method' => $methodName,
                        'type' => $accessorInfo['type'],
                        'nullable' => $accessorInfo['nullable'],
                        'isRelationship' => $accessorInfo['isRelationship'],
                        'relatedModel' => $accessorInfo['relatedModel'] ?? null,
                        'isCollection' => $accessorInfo['isCollection'],
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::error("Error extracting accessors for {$modelClass}: ".$e->getMessage());
        }

        return $accessors;
    }

    /**
     * Analyse an accessor method to determine its type and how it should be handled.
     */
    protected function analyseAccessor(ReflectionMethod $method): array
    {
        $methodBody = $this->getMethodBody($method);
        $returnType = $method->getReturnType();

        // Default result
        $result = [
            'shouldSkip' => false,
            'isRelationship' => false,
            'isCollection' => false,
            'type' => 'string',
            'nullable' => false,
            'relatedModel' => null,
        ];

        // Check if this is a virtual relationship accessor (like usedChannels)
        if ($this->isVirtualRelationshipAccessor($methodBody)) {
            $result['isRelationship'] = true;
            $result['isCollection'] = true;
            $result['type'] = 'Array<unknown>'; // Will be handled specially in resource template
            $result['relatedModel'] = $this->extractRelatedModelFromAccessor($methodBody);

            return $result;
        }

        // Check if this accessor is too complex and should be skipped
        if ($this->isOverlyComplexAccessor($methodBody)) {
            $result['shouldSkip'] = true;

            return $result;
        }

        // Determine type for simple accessors
        if ($returnType) {
            $typeName = $returnType->getName();
            $result['nullable'] = $returnType->allowsNull();

            switch ($typeName) {
                case 'int':
                case 'integer':
                case 'float':
                case 'double':
                    $result['type'] = 'number';
                    break;
                case 'bool':
                case 'boolean':
                    $result['type'] = 'boolean';
                    break;
                case 'array':
                    $result['type'] = 'Array<unknown>';
                    break;
                case 'string':
                default:
                    $result['type'] = 'string';
                    break;
            }
        } else {
            // Guess type from method name pattern
            $methodName = $method->getName();
            if (preg_match('/get(.+)Attribute/', $methodName, $matches)) {
                $attributeName = Str::snake($matches[1]);

                if (Str::endsWith($attributeName, ['_count', '_total'])) {
                    $result['type'] = 'number';
                } elseif (Str::startsWith($attributeName, ['is_', 'has_', 'can_'])) {
                    $result['type'] = 'boolean';
                } elseif (Str::endsWith($attributeName, ['_time', '_date'])) {
                    $result['type'] = 'string';
                } else {
                    $result['type'] = 'string';
                }
            }
        }

        return $result;
    }

    /**
     * Check if an accessor is a virtual relationship (like usedChannels).
     */
    protected function isVirtualRelationshipAccessor(string $methodBody): bool
    {
        // Patterns that indicate virtual relationships
        $virtualRelationshipPatterns = [
            // Returns a collection of models from other tables
            '/return.*collect\(\).*->push\(/i',
            '/return.*->unique\(.*id.*\)/i',
            // Checks relation loading and returns model collections
            '/relationLoaded.*return.*collect/i',
            // Channel/Model queries that build collections
            '/Channel::where.*push\(/i',
            '/Model::where.*collect/i',
        ];

        foreach ($virtualRelationshipPatterns as $pattern) {
            if (preg_match($pattern, $methodBody)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if an accessor is too complex and should be handled manually.
     */
    protected function isOverlyComplexAccessor(string $methodBody): bool
    {
        // Patterns that indicate overly complex logic that should be manual
        $complexPatterns = [
            // Multiple database queries
            '/\w+::\w+.*\w+::\w+/i',
            // Complex business logic
            '/if.*else.*if.*else/i',
            // File operations
            '/file_get_contents|fopen|Storage::/i',
            // External API calls
            '/curl_|Http::|Client::/i',
        ];

        foreach ($complexPatterns as $pattern) {
            if (preg_match($pattern, $methodBody)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Extract the related model from a virtual relationship accessor.
     */
    protected function extractRelatedModelFromAccessor(string $methodBody): ?string
    {
        // Look for Channel::where, User::where, etc. - find the most common model
        $modelCounts = [];
        if (preg_match_all('/(\w+)::where/', $methodBody, $matches)) {
            foreach ($matches[1] as $modelName) {
                if ($modelName && $modelName !== 'Model') {
                    $modelCounts[$modelName] = ($modelCounts[$modelName] ?? 0) + 1;
                }
            }
        }

        // Return the most frequently mentioned model
        if (! empty($modelCounts)) {
            $mostCommonModel = array_search(max($modelCounts), $modelCounts);

            return "App\\Models\\{$mostCommonModel}";
        }

        // Fallback: look for ->push() calls with model instantiation
        if (preg_match('/->push\s*\(\s*\$(\w+)Channel\s*\)/', $methodBody, $matches)) {
            return 'App\\Models\\Channel';
        }

        return null;
    }
}
