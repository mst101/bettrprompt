<?php

namespace App\Console\Commands\HiddenGambia\Resources;

use App\Console\Commands\Shared\Utilities;
use Illuminate\Support\Str;

class ResourceTemplate
{
    /**
     * The resource class name.
     */
    protected string $resourceClass;

    /**
     * The model class name.
     */
    protected string $modelClass;

    /**
     * The imports to include in the resource file.
     */
    protected array $imports = [];

    /**
     * The attributes to include in the resource.
     */
    protected array $attributes = [];

    /**
     * The relationships to include in the resource.
     */
    protected array $relationships = [];

    /**
     * The date fields to format in the resource.
     */
    protected array $dates = [];

    /**
     * The hidden fields to exclude from the resource.
     */
    protected array $hiddenFields = [];

    /**
     * Existing custom content from the resource file.
     */
    protected array $existingCustomisations = [];

    /**
     * Read and extract custom sections from existing resource file.
     */
    public function extractCustomSections(string $filePath): self
    {
        if (! file_exists($filePath)) {
            return $this;
        }

        $content = file_get_contents($filePath);
        $customSections = [];

        // Extract all custom sections marked with protection comments
        preg_match_all(
            '/\/\/ Custom field - DO NOT REGENERATE START\s*\n(.*?)\n\s*\/\/ Custom field - DO NOT REGENERATE END/s',
            $content,
            $matches,
            PREG_SET_ORDER
        );

        foreach ($matches as $match) {
            $customSections[] = $match[1];
        }

        $this->existingCustomisations = $customSections;

        return $this;
    }

    /**
     * Create a new resource template instance.
     */
    public function __construct(string $resourceClass, string $modelClass)
    {
        $this->resourceClass = $resourceClass;
        $this->modelClass = $modelClass;

        // Add default imports
        $this->addImport('Illuminate\Http\Request');
        $this->addImport('Illuminate\Http\Resources\Json\JsonResource');
    }

    /**
     * Add an import to the resource file.
     */
    public function addImport(string $import): self
    {
        if (! in_array($import, $this->imports)) {
            $this->imports[] = $import;
        }

        return $this;
    }

    /**
     * Add an attribute to the resource.
     *
     * @return $this
     */
    public function addAttribute(string $name, ?string $type = null, bool $isDate = false, bool $isNullable = false, ?array $columnInfo = null): self
    {
        $this->attributes[$name] = [
            'name' => $name,
            'type' => $type,
            'isDate' => $isDate,
            'isNullable' => $isNullable,
            'columnInfo' => $columnInfo,
        ];

        return $this;
    }

    /**
     * Add a relationship to the resource.
     *
     * @return $this
     */
    public function addRelationship(string $name, array $relation): self
    {
        $this->relationships[$name] = $relation;

        return $this;
    }

    /**
     * Set hidden fields that should be excluded from the resource.
     *
     * @return $this
     */
    public function setHiddenFields(array $hiddenFields): self
    {
        $this->hiddenFields = $hiddenFields;

        return $this;
    }

    /**
     * Add a custom accessor to the resource.
     *
     * @return $this
     */
    public function addAccessor(array $accessor): self
    {
        // Check if this is a virtual relationship accessor
        if ($accessor['isRelationship'] ?? false) {
            // Treat as a relationship instead of a simple attribute
            $relationInfo = [
                'name' => $accessor['name'],
                'type' => $accessor['isCollection'] ? 'HasMany' : 'BelongsTo',
                'related' => $accessor['relatedModel'] ?? 'Unknown',
                'resource' => $this->getResourceClassForRelation($accessor['relatedModel'] ?? 'Unknown'),
                'isCollection' => $accessor['isCollection'] ?? false,
                'isVirtual' => true, // Mark as virtual so we handle it specially
            ];
            $this->addRelationship($accessor['name'], $relationInfo);
        } else {
            // Add accessor as a regular attribute
            $this->addAttribute(
                $accessor['name'],
                $accessor['type'] ?? 'string',
                false, // Accessors are not date fields by default
                $accessor['nullable'] ?? false
            );
        }

        return $this;
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
     * Generate the resource file content.
     */
    public function generate(): string
    {
        $content = "<?php\n\n";
        $content .= "namespace App\\Http\\Resources;\n\n";

        $content .= "use Illuminate\\Http\\Request;\n";
        $content .= "use Illuminate\\Http\\Resources\\Json\\JsonResource;\n\n";

        // Add PHPDoc with TypeScript interface
        $content .= "/**\n";
        $content .= ' * @see \\App\\Models\\'.class_basename($this->modelClass)."\n";
        $content .= " *\n";
        $content .= " * TypeScript interface:\n";
        $content .= " * ```typescript\n";
        $content .= ' * interface '.str_replace('Resource', '', $this->resourceClass)." {\n";

        // Add TypeScript properties for attributes
        foreach ($this->attributes as $attribute) {
            // Skip hidden fields
            if (in_array($attribute['name'], $this->hiddenFields)) {
                continue;
            }

            // Skip foreign key fields if a corresponding relationship exists
            if ($this->shouldSkipForeignKeyField($attribute['name'])) {
                continue;
            }

            $tsType = $this->getTypeScriptType($attribute);
            $content .= ' *   readonly '.Str::camel($attribute['name']).': '.$tsType.";\n";
        }

        // Add TypeScript properties for relationships
        if (! empty($this->relationships)) {
            $content .= " *\n";
            $content .= " *   // Relationships\n";
            foreach ($this->relationships as $relationship) {
                $relName = Str::camel($relationship['name']);
                $relType = Utilities::getResourceClassName($relationship['related']);

                if ($this->isCollectionRelationship($relationship['type'])) {
                    $content .= ' *   readonly '.$relName.'?: readonly '.$relType."[];\n";
                } else {
                    $content .= ' *   readonly '.$relName.'?: '.$relType." | null;\n";
                }
            }
        }

        $content .= " * }\n";
        $content .= " * ```\n";
        $content .= " * The TypeScript interface is generated based on the attributes and relationships defined in this resource.\n";
        $content .= " * It is intended to be used with Vue.js Composition API and TypeScript.\n";
        $content .= " */\n";

        // Class definition
        $content .= "class {$this->resourceClass} extends JsonResource\n";
        $content .= "{\n";
        $content .= "    /**\n";
        $content .= "     * Transform the resource into an array.\n";
        $content .= "     */\n";
        $content .= "    public function toArray(Request \$request): array\n";
        $content .= "    {\n";
        $content .= "        return [\n";

        // Add attributes
        foreach ($this->attributes as $attribute) {
            $line = $this->generateAttributeLine($attribute);
            if (! empty($line)) {
                $content .= $line;
            }
        }

        // Add timestamps if not already added
        if (! isset($this->attributes['createdAt']) && ! isset($this->attributes['created_at']) && ! in_array('created_at', $this->hiddenFields)) {
            $content .= "            'createdAt' => \$this->created_at?->format('Y-m-d H:i:s'),\n";
        }

        if (! isset($this->attributes['updatedAt']) && ! isset($this->attributes['updated_at']) && ! in_array('updated_at', $this->hiddenFields)) {
            $content .= "            'updatedAt' => \$this->updated_at?->format('Y-m-d H:i:s'),\n";
        }

        // Add custom sections if any exist
        foreach ($this->existingCustomisations as $customSection) {
            $content .= "            // Custom field - DO NOT REGENERATE START\n";
            $content .= $customSection."\n";
            $content .= "            // Custom field - DO NOT REGENERATE END\n";
        }

        // Add relationships if any
        if (! empty($this->relationships)) {
            $content .= "\n            // Relationships\n";

            foreach ($this->relationships as $relationship) {
                $content .= $this->generateRelationshipLine($relationship);
            }
        }

        // Close the array and method
        $content .= "        ];\n";
        $content .= "    }\n";
        $content .= "}\n";

        return $content;
    }

    /**
     * Generate attribute line for the resource.
     */
    protected function generateAttributeLine(array $attribute): string
    {
        $name = $attribute['name'];
        $camelKey = Str::camel($name);

        // Skip hidden fields
        if (in_array($name, $this->hiddenFields)) {
            return '';
        }

        // Skip foreign key fields if a corresponding relationship exists
        if ($this->shouldSkipForeignKeyField($name)) {
            return '';
        }

        // Handle different cast types
        if (isset($attribute['type']) && ! empty($attribute['type'])) {
            $castType = $attribute['type'];

            // Handle datetime casts with custom formats
            if (Str::startsWith($castType, 'datetime:')) {
                $format = Str::after($castType, 'datetime:');

                return "            '{$camelKey}' => \$this->{$name}?->format('{$format}'),\n";
            }

            // Handle date casts
            if ($castType === 'date') {
                return "            '{$camelKey}' => \$this->{$name}?->format('Y-m-d'),\n";
            }

            // Handle timestamp/datetime casts
            if ($castType === 'datetime' || $castType === 'timestamp') {
                return "            '{$camelKey}' => \$this->{$name}?->format('Y-m-d H:i:s'),\n";
            }

            // Handle encrypted:json casts
            if ($castType === 'encrypted:json') {
                return "            '{$camelKey}' => \$this->{$name},\n"; // Let Laravel handle decryption
            }

            // Handle hashed casts (typically passwords - should probably be excluded)
            if ($castType === 'hashed') {
                return ''; // Skip hashed fields like passwords
            }
        }

        // Fallback to date formatting if marked as date
        if (isset($attribute['isDate']) && $attribute['isDate']) {
            return "            '{$camelKey}' => \$this->{$name}?->format('Y-m-d H:i:s'),\n";
        }

        return "            '{$camelKey}' => \$this->{$name},\n";
    }

    /**
     * Generate a line for a relationship.
     */
    protected function generateRelationshipLine(array $relationship): string
    {
        $name = $relationship['name'];
        $camelKey = Str::camel($name);
        $resourceClass = Utilities::getResourceClassName($relationship['related']);

        // Handle virtual relationships (like usedChannels)
        if ($relationship['isVirtual'] ?? false) {
            // Virtual relationships typically depend on other loaded relationships
            // For usedChannels, it depends on 'messages' being loaded
            $dependsOn = $this->getDependencyForVirtualRelationship($name);

            if ($relationship['isCollection']) {
                return "            '{$camelKey}' => \$this->whenLoaded('{$dependsOn}', function () {
                return {$resourceClass}::collection(\$this->{$name});
            }, []),\n";
            } else {
                return "            '{$camelKey}' => \$this->whenLoaded('{$dependsOn}', function () {
                return \$this->{$name} ? new {$resourceClass}(\$this->{$name}) : null;
            }),\n";
            }
        }

        // Handle regular relationship types
        if ($this->isCollectionRelationship($relationship['type'])) {
            // Collection relationship using whenLoaded with proper null check
            return "            '{$camelKey}' => \$this->whenLoaded('{$name}', function () {
                return \$this->{$name} ? {$resourceClass}::collection(\$this->{$name}) : [];
            }, []),\n";
        } elseif ($relationship['type'] === 'MorphTo') {
            // Polymorphic relationship
            return "            '{$camelKey}' => \$this->whenLoaded('{$name}', function () {
                if (!\$this->{$name}) {
                    return null;
                }
                \$resourceClass = 'App\\\\Http\\\\Resources\\\\' . class_basename(\$this->{$name}) . 'Resource';
                return class_exists(\$resourceClass) ? new \$resourceClass(\$this->{$name}) : \$this->{$name};
            }),\n";
        } else {
            // Single relationship with proper null check
            return "            '{$camelKey}' => \$this->whenLoaded('{$name}', function () {
                return \$this->{$name} ? new {$resourceClass}(\$this->{$name}) : null;
            }),\n";
        }
    }

    /**
     * Determine what relationship a virtual relationship depends on.
     */
    protected function getDependencyForVirtualRelationship(string $virtualRelationshipName): string
    {
        // Map of known virtual relationships to their dependencies
        $dependencies = [
            'used_channels' => 'messages',
            'usedChannels' => 'messages',
            // Add more mappings as needed
        ];

        return $dependencies[$virtualRelationshipName] ?? $virtualRelationshipName;
    }

    /**
     * Get TypeScript type for an attribute.
     *
     * The types are optimised for Vue.js Composition API usage with TypeScript.
     * Using readonly modifiers ensures immutability which helps with Vue reactivity system.
     */
    protected function getTypeScriptType(array $attribute): string
    {
        $name = $attribute['name'];
        $baseType = '';

        // Check if this is an enum column from migration analysis
        if (isset($attribute['columnInfo']['type']) && $attribute['columnInfo']['type'] === 'enum') {
            $enumValues = $this->parseEnumValues($attribute['columnInfo']['params'] ?? '');
            if (! empty($enumValues)) {
                $baseType = implode(' | ', array_map(function ($value) {
                    return "'{$value}'";
                }, $enumValues));
            } else {
                $baseType = 'string'; // Fallback if we can't parse enum values
            }
        }
        // First check if there's a type provided (cast or migration-based)
        elseif (isset($attribute['type']) && ! empty($attribute['type'])) {
            $type = $attribute['type'];

            // Check if it's already a TypeScript type (from migration analysis)
            if (in_array($type, ['number', 'string', 'boolean', 'Record<string, any>'])) {
                $baseType = $type;
            }
            // Handle decimal with precision (e.g., decimal:2) - PHP cast type
            elseif (Str::startsWith($type, 'decimal:')) {
                $baseType = 'number';
            }
            // Handle other PHP cast types
            else {
                switch ($type) {
                    case 'int':
                    case 'integer':
                    case 'bigint':
                    case 'smallint':
                        $baseType = 'number';
                        break;
                    case 'float':
                    case 'double':
                    case 'decimal':
                        $baseType = 'number';
                        break;
                    case 'bool':
                    case 'boolean':
                        $baseType = 'boolean';
                        break;
                    case 'array':
                        $baseType = 'Array<unknown>';
                        break;
                    case 'json':
                    case 'object':
                        $baseType = 'Record<string, unknown>';
                        break;
                    default:
                        $baseType = 'string';
                }
            }
        }
        // If no cast type, fall back to field name patterns
        else {
            // Common field names that are typically IDs
            $idFields = ['id', 'user_id', 'parent_id', 'model_id', 'external_id'];
            $booleanFields = ['active', 'enabled', 'verified', 'published', 'default', 'primary'];
            $numericFields = ['total_reviews', 'count', 'quantity', 'amount', 'price', 'rating', 'overall_rating'];

            // Check if the attribute name suggests it's an ID
            if (in_array($name, $idFields) || Str::endsWith($name, '_id')) {
                $baseType = 'number';
            }

            // Check if the attribute name suggests it's a boolean
            elseif (in_array($name, $booleanFields) || Str::startsWith($name, 'is_') || Str::startsWith($name, 'has_')) {
                $baseType = 'boolean';
            }

            // Check if the attribute name suggests it's a numeric field
            elseif (in_array($name, $numericFields) || Str::endsWith($name, '_count') || Str::endsWith($name, '_total')) {
                $baseType = 'number';
            }

            // Default to string if type is not specified
            else {
                $baseType = 'string';
            }
        }

        // Add nullable modifier if the field is nullable
        if (isset($attribute['isNullable']) && $attribute['isNullable'] === true) {
            return $baseType.' | null';
        }

        return $baseType;
    }

    /**
     * Parse enum values from migration parameters.
     *
     * Expected format: ['pending', 'active', 'inactive'] or "[\"pending\", \"active\", \"inactive\"]"
     */
    protected function parseEnumValues(string $params): array
    {
        if (empty($params)) {
            return [];
        }

        // Try to match array format: ['value1', 'value2', 'value3']
        if (preg_match('/\[([^\]]+)\]/', $params, $matches)) {
            $values = $matches[1];

            // Split by comma and clean up quotes and whitespace
            $enumValues = [];
            $parts = explode(',', $values);

            foreach ($parts as $part) {
                $cleaned = trim($part);
                // Remove quotes (both single and double)
                $cleaned = trim($cleaned, '\'"');
                if (! empty($cleaned)) {
                    $enumValues[] = $cleaned;
                }
            }

            return $enumValues;
        }

        return [];
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
     * Determine if a foreign key field should be skipped because a relationship exists.
     */
    protected function shouldSkipForeignKeyField(string $fieldName): bool
    {
        $camelCaseField = Str::camel($fieldName);

        // Check if this field name would conflict with any relationship name
        foreach ($this->relationships as $relationship) {
            $relationshipName = Str::camel($relationship['name']);

            // Only skip if the camelCase field name exactly matches the relationship name
            if ($camelCaseField === $relationshipName) {
                return true;
            }
        }

        return false;
    }
}
