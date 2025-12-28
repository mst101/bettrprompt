<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class WorkflowVariantService
{
    /**
     * Get all variants for a workflow
     */
    public function getVariants(int $workflowNumber): array
    {
        $config = config('n8n_variants');
        if (! isset($config[$workflowNumber])) {
            return [];
        }

        $variants = $config[$workflowNumber]['variants'];
        $result = [];

        foreach ($variants as $key => $variant) {
            $result[] = [
                'key' => $key,
                'name' => $variant['name'],
                'description' => $variant['description'] ?? '',
            ];
        }

        return $result;
    }

    /**
     * Get default variant for a workflow
     */
    public function getDefaultVariant(int $workflowNumber): string
    {
        $config = config('n8n_variants');
        if (! isset($config[$workflowNumber]['default'])) {
            return 'default';
        }

        return $config[$workflowNumber]['default'];
    }

    /**
     * Get variant configuration
     */
    public function getVariantConfig(int $workflowNumber, string $variant): array
    {
        $config = config('n8n_variants');
        if (! isset($config[$workflowNumber]['variants'][$variant])) {
            return [];
        }

        return $config[$workflowNumber]['variants'][$variant];
    }

    /**
     * Get workflow file path for variant
     */
    public function getWorkflowFilePath(int $workflowNumber, string $variant): string
    {
        $config = $this->getVariantConfig($workflowNumber, $variant);
        if (empty($config)) {
            return base_path("n8n/workflow_{$workflowNumber}.json");
        }

        return base_path("n8n/{$config['workflow_file']}");
    }

    /**
     * Get storage path for variant data
     * e.g., getVariantStoragePath(1, 'two-pass', 'prepare_prompt/old')
     *   → '/home/mark/repos/bettrprompt/storage/app/n8n_debug/variants/two-pass/prepare_prompt/old/'
     */
    public function getVariantStoragePath(
        int $workflowNumber,
        string $variant,
        string $type
    ): string {
        return storage_path("app/n8n_debug/variants/{$variant}/{$type}/");
    }

    /**
     * Extract prepare prompt node names from workflow JSON
     * Returns array of node names like ['Prepare Prompt', 'Another Node'] or ['Prepare Prompt 1', 'Prepare Prompt 2']
     */
    public function extractPreparePromptNodeNames(
        int $workflowNumber,
        string $variant
    ): array {
        $config = $this->getVariantConfig($workflowNumber, $variant);
        if (empty($config)) {
            return [];
        }

        return $config['prepare_prompt_nodes'] ?? [];
    }

    /**
     * Extract JavaScript code from a specific prepare prompt node in workflow JSON
     */
    public function extractJavaScriptFromNode(
        int $workflowNumber,
        string $variant,
        string $nodeName
    ): ?string {
        $workflowFilePath = $this->getWorkflowFilePath($workflowNumber, $variant);

        if (! file_exists($workflowFilePath)) {
            return null;
        }

        $workflow = json_decode(file_get_contents($workflowFilePath), true);
        if (! isset($workflow['nodes'])) {
            return null;
        }

        foreach ($workflow['nodes'] as $node) {
            if ($node['name'] === $nodeName && isset($node['parameters']['jsCode'])) {
                return $node['parameters']['jsCode'];
            }
        }

        return null;
    }

    /**
     * Load JavaScript for a specific prepare prompt node
     * Falls back to legacy structure if variant path doesn't exist
     */
    public function loadJavaScript(
        int $workflowNumber,
        string $variant,
        string $nodeName,
        bool $isNew
    ): ?string {
        $suffix = $this->getNodeFilenameSuffix($nodeName);
        $version = $isNew ? 'new' : 'old';

        // Try variant-specific path first
        $variantPath = $this->getVariantStoragePath($workflowNumber, $variant, "prepare_prompt/{$version}");
        $fileName = "workflow_{$workflowNumber}_prepare_prompt{$suffix}.js";
        $variantFile = $variantPath.$fileName;

        if (file_exists($variantFile)) {
            return file_get_contents($variantFile);
        }

        // Fall back to legacy structure for backwards compatibility
        $legacyPath = storage_path("app/n8n_debug/prepare_prompt/{$version}/");
        $legacyFile = $legacyPath.$fileName;

        if (file_exists($legacyFile)) {
            return file_get_contents($legacyFile);
        }

        // If nothing found, try extracting from workflow JSON
        return $this->extractJavaScriptFromNode($workflowNumber, $variant, $nodeName);
    }

    /**
     * Save JavaScript for a specific prepare prompt node
     */
    public function saveJavaScript(
        int $workflowNumber,
        string $variant,
        string $nodeName,
        string $code,
        bool $isNew
    ): void {
        $suffix = $this->getNodeFilenameSuffix($nodeName);
        $version = $isNew ? 'new' : 'old';

        $variantPath = $this->getVariantStoragePath($workflowNumber, $variant, "prepare_prompt/{$version}");
        $this->ensureDebugDirectory($variantPath);

        $fileName = "workflow_{$workflowNumber}_prepare_prompt{$suffix}.js";
        $filePath = $variantPath.$fileName;

        file_put_contents($filePath, $code);
        chmod($filePath, 0644);
    }

    /**
     * Load prompt data (JSON) for a specific node
     */
    public function loadPrompt(
        int $workflowNumber,
        string $variant,
        string $nodeName,
        bool $isNew
    ): ?array {
        $suffix = $this->getNodeFilenameSuffix($nodeName);
        $version = $isNew ? 'new' : 'old';

        // Try variant-specific path first
        $variantPath = $this->getVariantStoragePath($workflowNumber, $variant, "prompt/{$version}");
        $fileName = "workflow_{$workflowNumber}_prompt{$suffix}.json";
        $variantFile = $variantPath.$fileName;

        if (file_exists($variantFile)) {
            return json_decode(file_get_contents($variantFile), true);
        }

        // Fall back to legacy structure
        $legacyPath = storage_path("app/n8n_debug/prompt/{$version}/");
        $legacyFile = $legacyPath.$fileName;

        if (file_exists($legacyFile)) {
            return json_decode(file_get_contents($legacyFile), true);
        }

        return null;
    }

    /**
     * Save prompt data (JSON) for a specific node
     */
    public function savePrompt(
        int $workflowNumber,
        string $variant,
        string $nodeName,
        array $promptData,
        bool $isNew
    ): void {
        $suffix = $this->getNodeFilenameSuffix($nodeName);
        $version = $isNew ? 'new' : 'old';

        $variantPath = $this->getVariantStoragePath($workflowNumber, $variant, "prompt/{$version}");
        $this->ensureDebugDirectory($variantPath);

        $fileName = "workflow_{$workflowNumber}_prompt{$suffix}.json";
        $filePath = $variantPath.$fileName;

        file_put_contents($filePath, json_encode($promptData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        chmod($filePath, 0644);
    }

    /**
     * Load workflow output
     */
    public function loadOutput(
        int $workflowNumber,
        string $variant,
        bool $isNew
    ): ?array {
        $version = $isNew ? 'new' : 'old';

        // Try variant-specific path first
        $variantPath = $this->getVariantStoragePath($workflowNumber, $variant, "output/{$version}");
        $fileName = "workflow_{$workflowNumber}_output.json";
        $variantFile = $variantPath.$fileName;

        if (file_exists($variantFile)) {
            return json_decode(file_get_contents($variantFile), true);
        }

        // Fall back to legacy structure
        $legacyPath = storage_path("app/n8n_debug/output/{$version}/");
        $legacyFile = $legacyPath.$fileName;

        if (file_exists($legacyFile)) {
            return json_decode(file_get_contents($legacyFile), true);
        }

        return null;
    }

    /**
     * Save workflow output
     */
    public function saveOutput(
        int $workflowNumber,
        string $variant,
        array $outputData,
        bool $isNew
    ): void {
        $version = $isNew ? 'new' : 'old';

        $variantPath = $this->getVariantStoragePath($workflowNumber, $variant, "output/{$version}");
        $this->ensureDebugDirectory($variantPath);

        $fileName = "workflow_{$workflowNumber}_output.json";
        $filePath = $variantPath.$fileName;

        file_put_contents($filePath, json_encode($outputData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        chmod($filePath, 0644);
    }

    /**
     * Extract pass number from node name
     * 'Prepare Prompt' → 1
     * 'Prepare Prompt 1' → 1
     * 'Prepare Prompt 2' → 2
     */
    public function getPassNumberFromNodeName(string $nodeName): int
    {
        if ($nodeName === 'Prepare Prompt') {
            return 1;
        }

        // Extract number from node name like "Prepare Prompt 2" → 2
        if (preg_match('/(\d+)$/', $nodeName, $matches)) {
            return (int) $matches[1];
        }

        return 1;
    }

    /**
     * Load pass output (e.g., output from Pass 1 to be used by Pass 2)
     */
    public function loadPassOutput(
        int $workflowNumber,
        string $variant,
        int $passNumber,
        bool $isNew
    ): ?array {
        $version = $isNew ? 'new' : 'old';
        $variantPath = $this->getVariantStoragePath($workflowNumber, $variant, "output/{$version}");
        $fileName = "workflow_{$workflowNumber}_output_{$passNumber}.json";
        $filePath = $variantPath.$fileName;

        if (! file_exists($filePath)) {
            return null;
        }

        return json_decode(file_get_contents($filePath), true);
    }

    /**
     * Save pass output (e.g., save Pass 1 output so Pass 2 can use it)
     */
    public function savePassOutput(
        int $workflowNumber,
        string $variant,
        int $passNumber,
        array $outputData,
        bool $isNew
    ): void {
        $version = $isNew ? 'new' : 'old';
        $variantPath = $this->getVariantStoragePath($workflowNumber, $variant, "output/{$version}");
        $this->ensureDebugDirectory($variantPath);

        $fileName = "workflow_{$workflowNumber}_output_{$passNumber}.json";
        $filePath = $variantPath.$fileName;

        file_put_contents($filePath, json_encode($outputData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        chmod($filePath, 0644);
    }

    /**
     * Convert node name to filename suffix
     * 'Prepare Prompt' → ''
     * 'Prepare Prompt 1' → '_1'
     * 'Prepare Prompt 2' → '_2'
     */
    protected function getNodeFilenameSuffix(string $nodeName): string
    {
        if ($nodeName === 'Prepare Prompt') {
            return '';
        }

        // Extract number from node name like "Prepare Prompt 1" → "_1"
        if (preg_match('/(\d+)$/', $nodeName, $matches)) {
            return '_'.$matches[1];
        }

        // For other node names, convert to lowercase and replace spaces with underscores
        return '_'.strtolower(str_replace(' ', '_', $nodeName));
    }

    /**
     * Ensure debug directory exists and is writable
     */
    private function ensureDebugDirectory(string $path): void
    {
        if (! is_dir($path)) {
            mkdir($path, 0755, true);
        }
    }
}
