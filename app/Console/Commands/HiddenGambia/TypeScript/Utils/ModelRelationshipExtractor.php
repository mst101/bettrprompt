<?php

namespace App\Console\Commands\HiddenGambia\TypeScript\Utils;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ModelRelationshipExtractor
{
    /**
     * Extract relationships from a model file.
     *
     * @param  string  $modelPath  Path to the model file
     * @return array Array of relationships with their types
     */
    public function extract(string $modelPath): array
    {
        if (! File::exists($modelPath)) {
            return [];
        }

        $content = File::get($modelPath);
        $relationships = [];

        // Extract model name
        preg_match('/class\s+(\w+)\s+extends\s+Model/i', $content, $matches);
        $modelName = $matches[1] ?? '';

        // Extract relationship methods
        $this->extractHasOneRelationships($content, $relationships);
        $this->extractHasManyRelationships($content, $relationships);
        $this->extractBelongsToRelationships($content, $relationships);
        $this->extractBelongsToManyRelationships($content, $relationships);
        $this->extractHasOneThrough($content, $relationships);
        $this->extractHasManyThrough($content, $relationships);
        $this->extractMorphTo($content, $relationships);
        $this->extractMorphOne($content, $relationships);
        $this->extractMorphMany($content, $relationships);
        $this->extractMorphToMany($content, $relationships);
        $this->extractMorphedByMany($content, $relationships);

        return $relationships;
    }

    /**
     * Extract hasOne relationships.
     */
    protected function extractHasOneRelationships(string $content, array &$relationships): void
    {
        preg_match_all('/public\s+function\s+(\w+)\s*\(\s*\)\s*\{[\s\S]*?return\s+\$this->hasOne\s*\(\s*([^,\)]+)/', $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $methodName = $match[1];
            $relatedModel = $this->extractClassName($match[2]);
            $relationships[$methodName] = $relatedModel.' | null';
        }
    }

    /**
     * Extract hasMany relationships.
     */
    protected function extractHasManyRelationships(string $content, array &$relationships): void
    {
        preg_match_all('/public\s+function\s+(\w+)\s*\(\s*\)\s*\{[\s\S]*?return\s+\$this->hasMany\s*\(\s*([^,\)]+)/', $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $methodName = $match[1];
            $relatedModel = $this->extractClassName($match[2]);
            $relationships[$methodName] = $relatedModel.'[] | null';
        }
    }

    /**
     * Extract belongsTo relationships.
     */
    protected function extractBelongsToRelationships(string $content, array &$relationships): void
    {
        preg_match_all('/public\s+function\s+(\w+)\s*\(\s*\)\s*\{[\s\S]*?return\s+\$this->belongsTo\s*\(\s*([^,\)]+)/', $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $methodName = $match[1];
            $relatedModel = $this->extractClassName($match[2]);
            $relationships[$methodName] = $relatedModel.' | null';
        }
    }

    /**
     * Extract belongsToMany relationships.
     */
    protected function extractBelongsToManyRelationships(string $content, array &$relationships): void
    {
        preg_match_all('/public\s+function\s+(\w+)\s*\(\s*\)\s*\{[\s\S]*?return\s+\$this->belongsToMany\s*\(\s*([^,\)]+)/', $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $methodName = $match[1];
            $relatedModel = $this->extractClassName($match[2]);
            $relationships[$methodName] = $relatedModel.'[] | null';
        }
    }

    /**
     * Extract hasOneThrough relationships.
     */
    protected function extractHasOneThrough(string $content, array &$relationships): void
    {
        preg_match_all('/public\s+function\s+(\w+)\s*\(\s*\)\s*\{[\s\S]*?return\s+\$this->hasOneThrough\s*\(\s*([^,\)]+)/', $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $methodName = $match[1];
            $relatedModel = $this->extractClassName($match[2]);
            $relationships[$methodName] = $relatedModel.' | null';
        }
    }

    /**
     * Extract hasManyThrough relationships.
     */
    protected function extractHasManyThrough(string $content, array &$relationships): void
    {
        preg_match_all('/public\s+function\s+(\w+)\s*\(\s*\)\s*\{[\s\S]*?return\s+\$this->hasManyThrough\s*\(\s*([^,\)]+)/', $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $methodName = $match[1];
            $relatedModel = $this->extractClassName($match[2]);
            $relationships[$methodName] = $relatedModel.'[] | null';
        }
    }

    /**
     * Extract morphTo relationships.
     */
    protected function extractMorphTo(string $content, array &$relationships): void
    {
        preg_match_all('/public\s+function\s+(\w+)\s*\(\s*\)\s*\{[\s\S]*?return\s+\$this->morphTo\s*\(/', $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $methodName = $match[1];
            // For morphTo, we can't determine the exact type, so we use any
            $relationships[$methodName] = 'any | null';
        }
    }

    /**
     * Extract morphOne relationships.
     */
    protected function extractMorphOne(string $content, array &$relationships): void
    {
        preg_match_all('/public\s+function\s+(\w+)\s*\(\s*\)\s*\{[\s\S]*?return\s+\$this->morphOne\s*\(\s*([^,\)]+)/', $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $methodName = $match[1];
            $relatedModel = $this->extractClassName($match[2]);
            $relationships[$methodName] = $relatedModel.' | null';
        }
    }

    /**
     * Extract morphMany relationships.
     */
    protected function extractMorphMany(string $content, array &$relationships): void
    {
        preg_match_all('/public\s+function\s+(\w+)\s*\(\s*\)\s*\{[\s\S]*?return\s+\$this->morphMany\s*\(\s*([^,\)]+)/', $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $methodName = $match[1];
            $relatedModel = $this->extractClassName($match[2]);
            $relationships[$methodName] = $relatedModel.'[] | null';
        }
    }

    /**
     * Extract morphToMany relationships.
     */
    protected function extractMorphToMany(string $content, array &$relationships): void
    {
        preg_match_all('/public\s+function\s+(\w+)\s*\(\s*\)\s*\{[\s\S]*?return\s+\$this->morphToMany\s*\(\s*([^,\)]+)/', $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $methodName = $match[1];
            $relatedModel = $this->extractClassName($match[2]);
            $relationships[$methodName] = $relatedModel.'[] | null';
        }
    }

    /**
     * Extract morphedByMany relationships.
     */
    protected function extractMorphedByMany(string $content, array &$relationships): void
    {
        preg_match_all('/public\s+function\s+(\w+)\s*\(\s*\)\s*\{[\s\S]*?return\s+\$this->morphedByMany\s*\(\s*([^,\)]+)/', $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $methodName = $match[1];
            $relatedModel = $this->extractClassName($match[2]);
            $relationships[$methodName] = $relatedModel.'[] | null';
        }
    }

    /**
     * Extract class name from a string.
     */
    protected function extractClassName(string $classString): string
    {
        $classString = Str::trim($classString);

        // Remove namespace if using ::class syntax
        if (Str::endsWith($classString, '::class')) {
            $classString = Str::replace('::class', '', $classString);
            $parts = explode('\\', $classString);

            return end($parts);
        }

        // If it's a string literal, remove quotes
        return Str::trim($classString, '\'"');
    }
}
