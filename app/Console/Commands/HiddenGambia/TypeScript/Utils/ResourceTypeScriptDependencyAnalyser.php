<?php

namespace App\Console\Commands\HiddenGambia\TypeScript\Utils;

class ResourceTypeScriptDependencyAnalyser
{
    /**
     * Analyse dependencies in a resource TypeScript interface.
     *
     * @param  array  $properties  Properties extracted from the resource
     * @return array List of dependencies
     */
    public function analyse(array $properties): array
    {
        $dependencies = [];

        // Extract relationship types from property values
        foreach ($properties as $propertyName => $propertyType) {
            if (preg_match('/(\w+Resource)(\[\])?\s*[|]/', $propertyType, $matches)) {
                $dependencies[] = $matches[1];
            }
        }

        return array_unique($dependencies);
    }
}
