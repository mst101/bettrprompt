<?php

namespace App\Console\Commands\HiddenGambia\TypeScript\Utils;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ModelTypeScriptFileGenerator
{
    /**
     * Generate a TypeScript file for a model.
     *
     * @param  string  $modelName  The name of the model
     * @param  array  $properties  The model properties
     * @param  array  $relationships  The model relationships
     * @param  string  $outputDir  The output directory
     * @return string The path to the generated file
     */
    public function generate(string $modelName, array $properties, array $relationships, string $outputDir): string
    {
        // Create output directory if it doesn't exist
        if (! File::isDirectory($outputDir)) {
            File::makeDirectory($outputDir, 0755, true);
        }

        // Generate the TypeScript interface
        $content = $this->generateTypeScriptInterface($modelName, $properties, $relationships, []);

        // Write the file
        $filePath = $outputDir.'/'.$modelName.'.ts';
        File::put($filePath, $content);

        return $filePath;
    }

    /**
     * Generate a TypeScript interface for a model.
     *
     * @param  string  $modelName  The name of the model
     * @param  array  $properties  The model properties
     * @param  array  $relationships  The model relationships
     * @param  array  $dependencies  The model dependencies
     * @return string The TypeScript interface content
     */
    protected function generateTypeScriptInterface(string $modelName, array $properties, array $relationships, array $dependencies): string
    {
        // Collect imports for dependencies
        $imports = $this->collectImports($dependencies);

        // Start building the interface content
        $interfaceContent = "export interface {$modelName} {\n";

        // Add properties
        foreach ($properties as $property => $type) {
            $interfaceContent .= "  {$property}: {$type};\n";
        }

        // Add relationships (always optional for defensive programming)
        foreach ($relationships as $relation => $type) {
            $interfaceContent .= "  {$relation}?: {$type};\n";
        }

        // Close the interface
        $interfaceContent .= "}\n";

        // Combine imports and interface
        $content = '';

        // Add Vue Composition API imports
        $content .= "import { ref } from 'vue';\n";
        $content .= "import axios from 'axios';\n";
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
        $content .= $this->generateUtilityTypes($modelName);

        return $content;
    }

    /**
     * Collect imports for related models.
     */
    protected function collectImports(array $relationships): array
    {
        $imports = [];

        foreach ($relationships as $relationship => $type) {
            // Extract the model name from the type (e.g., "User | null" -> "User")
            if (preg_match('/^([A-Za-z0-9_]+)(\[\])?\s*\|?\s*null?$/', $type, $matches)) {
                $modelName = $matches[1];
                if (! in_array($modelName, ['any', 'Record', 'string', 'number', 'boolean'])) {
                    $imports[] = $modelName;
                }
            }
        }

        return array_unique($imports);
    }

    /**
     * Generate utility types for common use cases.
     */
    protected function generateUtilityTypes(string $modelName): string
    {
        $content = "\n// Utility types for {$modelName}\n";

        // Add key type
        $content .= "export type {$modelName}Key = keyof {$modelName};\n";

        // Add create input type (omit auto-generated fields)
        $content .= "export type {$modelName}CreateInput = Omit<{$modelName}, 'id' | 'createdAt' | 'updatedAt' | 'deletedAt'>;\n";

        // Add update input type (make all fields optional)
        $content .= "export type {$modelName}UpdateInput = Partial<{$modelName}CreateInput>;\n";

        // Add collection and pagination types
        $content .= "export type {$modelName}Collection = {$modelName}[];\n";
        $content .= "export type {$modelName}Pagination = PaginatedData<{$modelName}>;\n";

        // Add Vue Composition API composable
        $content .= "\n// Vue Composition API composable\n";
        $content .= "export function use{$modelName}() {\n";
        $content .= "  // State management\n";
        $content .= "  const loading = ref(false);\n";
        $content .= "  const error = ref<string | null>(null);\n";
        $content .= "  const item = ref<{$modelName} | null>(null);\n";
        $content .= "  const items = ref<{$modelName}[]>([]);\n";
        $content .= "  const pagination = ref<{$modelName}Pagination | null>(null);\n\n";

        $content .= "  // Fetch single item\n";
        $content .= "  async function fetch(id: number | string): Promise<{$modelName} | null> {\n";
        $content .= "    loading.value = true;\n";
        $content .= "    error.value = null;\n";
        $content .= "    try {\n";
        $content .= '      const response = await axios.get(`/api/'.$this->getApiEndpoint($modelName)."/\${id}`);\n";
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
        $content .= "  async function fetchAll(params?: Record<string, any>): Promise<{$modelName}[] | null> {\n";
        $content .= "    loading.value = true;\n";
        $content .= "    error.value = null;\n";
        $content .= "    try {\n";
        $content .= '      const response = await axios.get(`/api/'.$this->getApiEndpoint($modelName)."`, { params });\n";
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

        $content .= "  // Create item\n";
        $content .= "  async function create(data: {$modelName}CreateInput): Promise<{$modelName} | null> {\n";
        $content .= "    loading.value = true;\n";
        $content .= "    error.value = null;\n";
        $content .= "    try {\n";
        $content .= '      const response = await axios.post(`/api/'.$this->getApiEndpoint($modelName)."`, data);\n";
        $content .= "      item.value = response.data.data;\n";
        $content .= "      return item.value;\n";
        $content .= "    } catch (e) {\n";
        $content .= "      error.value = e.response?.data?.message || 'An error occurred';\n";
        $content .= "      return null;\n";
        $content .= "    } finally {\n";
        $content .= "      loading.value = false;\n";
        $content .= "    }\n";
        $content .= "  }\n\n";

        $content .= "  // Update item\n";
        $content .= "  async function update(id: number | string, data: {$modelName}UpdateInput): Promise<{$modelName} | null> {\n";
        $content .= "    loading.value = true;\n";
        $content .= "    error.value = null;\n";
        $content .= "    try {\n";
        $content .= '      const response = await axios.put(`/api/'.$this->getApiEndpoint($modelName)."/\${id}`, data);\n";
        $content .= "      item.value = response.data.data;\n";
        $content .= "      return item.value;\n";
        $content .= "    } catch (e) {\n";
        $content .= "      error.value = e.response?.data?.message || 'An error occurred';\n";
        $content .= "      return null;\n";
        $content .= "    } finally {\n";
        $content .= "      loading.value = false;\n";
        $content .= "    }\n";
        $content .= "  }\n\n";

        $content .= "  // Delete item\n";
        $content .= "  async function remove(id: number | string): Promise<boolean> {\n";
        $content .= "    loading.value = true;\n";
        $content .= "    error.value = null;\n";
        $content .= "    try {\n";
        $content .= '      await axios.delete(`/api/'.$this->getApiEndpoint($modelName)."/\${id}`);\n";
        $content .= "      return true;\n";
        $content .= "    } catch (e) {\n";
        $content .= "      error.value = e.response?.data?.message || 'An error occurred';\n";
        $content .= "      return false;\n";
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
        $content .= "    create,\n";
        $content .= "    update,\n";
        $content .= "    remove,\n";
        $content .= "  };\n";
        $content .= "}\n";

        return $content;
    }

    /**
     * Get the API endpoint for a model.
     */
    private function getApiEndpoint(string $modelName): string
    {
        // Convert from PascalCase to kebab-case for API endpoints
        $name = Str::kebab($modelName);

        // Properly pluralize the name using Laravel's Str utility
        return Str::plural($name);
    }
}
