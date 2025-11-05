<?php

namespace App\Console\Commands\HiddenGambia\TypeScript\Utils;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ResourceTypeScriptFileGenerator
{
    /**
     * Generate a TypeScript file for a resource.
     *
     * @param  string  $resourceName  The name of the resource
     * @param  array  $properties  The resource properties
     * @param  array  $dependencies  The resource dependencies
     * @param  string  $outputDir  The output directory
     * @return string The path to the generated file
     */
    public function generate(string $resourceName, array $properties, array $dependencies, string $outputDir): string
    {
        // Create output directory if it doesn't exist
        if (! File::isDirectory($outputDir)) {
            File::makeDirectory($outputDir, 0755, true);
        }

        // Generate the TypeScript interface
        $content = $this->generateTypeScriptInterface($resourceName, $properties, $dependencies);

        // Write the file
        $filePath = $outputDir.'/'.$resourceName.'.ts';
        File::put($filePath, $content);

        return $filePath;
    }

    /**
     * Generate a TypeScript interface for a resource.
     *
     * @param  string  $resourceName  The name of the resource
     * @param  array  $properties  The resource properties
     * @param  array  $dependencies  The resource dependencies
     * @return string The TypeScript interface content
     */
    protected function generateTypeScriptInterface(string $resourceName, array $properties, array $dependencies): string
    {
        // Collect imports for dependencies
        $imports = $this->collectImports($dependencies);

        // Start building the interface content
        $interfaceContent = "export interface {$resourceName} {\n";

        // Add properties
        foreach ($properties as $property => $type) {
            // Handle nullable properties
            $nullable = Str::endsWith($type, ' | null');
            $interfaceContent .= "  {$property}".($nullable ? '?' : '').": {$type};\n";
        }

        // Close the interface
        $interfaceContent .= "}\n";

        // Combine imports and interface
        $content = '';

        // Add Vue Composition API imports
        $content .= "import axios from 'axios';\n";
        $content .= "import { ref } from 'vue';\n";
        $content .= "import { PaginatedData } from '../shared';\n\n";

        // Add imports if any
        if (! empty($imports)) {
            foreach ($imports as $importName) {
                $content .= "import type { {$importName} } from './{$importName}';\n";
            }
            $content .= "\n";
        } else {
            $content .= "\n";
        }

        // Add interface
        $content .= $interfaceContent;

        // Add utility types for common use cases
        $content .= $this->generateUtilityTypes($resourceName);

        return $content;
    }

    /**
     * Collect imports for dependencies.
     */
    protected function collectImports(array $dependencies): array
    {
        $imports = [];

        foreach ($dependencies as $dependency) {
            // Skip primitive types and built-in TypeScript types
            if (! in_array($dependency, ['any', 'Record', 'string', 'number', 'boolean', 'null', 'undefined'])) {
                $imports[] = $dependency;
            }
        }

        return array_unique($imports);
    }

    /**
     * Generate utility types for common use cases.
     */
    protected function generateUtilityTypes(string $resourceName): string
    {
        $content = "\n// Utility types for {$resourceName}\n";

        // Add key type
        $content .= "export type {$resourceName}Key = keyof {$resourceName};\n";

        // Add collection and pagination types
        $content .= "export type {$resourceName}Collection = {$resourceName}[];\n";
        $content .= "export type {$resourceName}Pagination = PaginatedData<{$resourceName}>;\n";

        // Add Vue Composition API composable
        $content .= "\n// Vue Composition API composable\n";
        $content .= "export function use{$resourceName}() {\n";
        $content .= "  // State management\n";
        $content .= "  const loading = ref(false);\n";
        $content .= "  const error = ref<string | null>(null);\n";
        $content .= "  const item = ref<{$resourceName} | null>(null);\n";
        $content .= "  const items = ref<{$resourceName}[]>([]);\n";
        $content .= "  const pagination = ref<{$resourceName}Pagination | null>(null);\n\n";

        $content .= "  // Fetch single item\n";
        $content .= "  async function fetch(id: number | string): Promise<{$resourceName} | null> {\n";
        $content .= "    loading.value = true;\n";
        $content .= "    error.value = null;\n";
        $content .= "    try {\n";
        $content .= '      const response = await axios.get(`/api/'.$this->getApiEndpoint($resourceName)."/\${id}`);\n";
        $content .= "      item.value = response.data.data;\n";
        $content .= "      return item.value;\n";
        $content .= "    } catch (e) {\n";
        $content .= "      error.value = e.response?.data?.message || 'An error occurred';\n";
        $content .= "      return null;\n";
        $content .= "    } finally {\n";
        $content .= "      loading.value = false;\n";
        $content .= "    }\n";
        $content .= "  }\n\n";

        $content .= "  // Fetch collection\n";
        $content .= "  async function fetchAll(params?: Record<string, any>): Promise<{$resourceName}[] | null> {\n";
        $content .= "    loading.value = true;\n";
        $content .= "    error.value = null;\n";
        $content .= "    try {\n";
        $content .= '      const response = await axios.get(`/api/'.$this->getApiEndpoint($resourceName)."`, { params });\n";
        $content .= "      if (response.data.data) {\n";
        $content .= "        // Handle paginated response\n";
        $content .= "        items.value = response.data.data;\n";
        $content .= "        pagination.value = response.data;\n";
        $content .= "      } else {\n";
        $content .= "        // Handle non-paginated response\n";
        $content .= "        items.value = response.data;\n";
        $content .= "      }\n";
        $content .= "      return items.value;\n";
        $content .= "    } catch (e) {\n";
        $content .= "      error.value = e.response?.data?.message || 'An error occurred';\n";
        $content .= "      return null;\n";
        $content .= "    } finally {\n";
        $content .= "      loading.value = false;\n";
        $content .= "    }\n";
        $content .= "  }\n\n";

        $content .= "  return {\n";
        $content .= "    // State\n";
        $content .= "    loading,\n";
        $content .= "    error,\n";
        $content .= "    item,\n";
        $content .= "    items,\n";
        $content .= "    pagination,\n";
        $content .= "    // Methods\n";
        $content .= "    fetch,\n";
        $content .= "    fetchAll,\n";
        $content .= "  };\n";
        $content .= "}\n";

        return $content;
    }

    /**
     * Get the API endpoint for a resource.
     */
    private function getApiEndpoint(string $resourceName): string
    {
        // Remove "Resource" suffix
        $name = preg_replace('/Resource$/', '', $resourceName);

        // Convert to kebab-case
        $name = Str::kebab($name);

        // Properly pluralize the name using Laravel's Str utility
        return Str::plural($name);
    }
}
