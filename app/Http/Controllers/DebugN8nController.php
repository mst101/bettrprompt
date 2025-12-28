<?php

namespace App\Http\Controllers;

use App\Services\WorkflowVariantService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DebugN8nController extends Controller
{
    /**
     * @var array List of temporary files to clean up after execution
     */
    private array $tempFilesToCleanup = [];

    /**
     * Inject WorkflowVariantService dependency
     */
    public function __construct(
        private WorkflowVariantService $variantService
    ) {}

    /**
     * Get current variant for workflow (from session or default)
     */
    protected function getVariant(int $workflowNumber): string
    {
        return session("workflow.{$workflowNumber}.variant")
            ?? $this->variantService->getDefaultVariant($workflowNumber);
    }

    /**
     * Set variant preference in session
     */
    public function setVariant(Request $request, int $workflowNumber)
    {
        try {
            $variant = $request->input('variant');
            $availableVariants = $this->variantService->getVariants($workflowNumber);
            $variantKeys = array_column($availableVariants, 'key');

            if (! in_array($variant, $variantKeys)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid variant',
                ], 400);
            }

            session(["workflow.{$workflowNumber}.variant" => $variant]);

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => "Failed to set variant: {$e->getMessage()}",
            ], 500);
        }
    }

    /**
     * Display the workflow page
     */
    public function show(int $workflowNumber)
    {
        $currentVariant = $this->getVariant($workflowNumber);
        $availableVariants = $this->variantService->getVariants($workflowNumber);
        $nodeNames = $this->variantService->extractPreparePromptNodeNames($workflowNumber, $currentVariant);

        // Load input from storage/app/n8n_debug/input/
        $input = null;
        $inputFile = storage_path("app/n8n_debug/input/workflow_{$workflowNumber}_input.json");
        if (file_exists($inputFile)) {
            $content = json_decode(file_get_contents($inputFile), true);
            // Handle array format from n8n (wrap in body if needed)
            if (is_array($content) && isset($content[0]['body'])) {
                $input = $content[0];
            } else {
                $input = $content;
            }
        }

        // Load prepare prompt nodes
        $preparePromptNodes = [];
        $javascriptOld = null;
        $javascriptNew = null;
        $promptOld = null;
        $promptNew = null;

        foreach ($nodeNames as $nodeName) {
            $jsOld = $this->variantService->loadJavaScript($workflowNumber, $currentVariant, $nodeName, false);
            $jsNew = $this->variantService->loadJavaScript($workflowNumber, $currentVariant, $nodeName, true);
            $promptOld_node = $this->variantService->loadPrompt($workflowNumber, $currentVariant, $nodeName, false);
            $promptNew_node = $this->variantService->loadPrompt($workflowNumber, $currentVariant, $nodeName, true);

            $preparePromptNodes[] = [
                'name' => $nodeName,
                'javascriptOld' => $jsOld,
                'javascriptNew' => $jsNew,
                'promptOld' => $promptOld_node,
                'promptNew' => $promptNew_node,
            ];

            // For backwards compatibility, use first node's data as the main props
            if ($nodeName === 'Prepare Prompt' || empty($javascriptOld)) {
                $javascriptOld = $jsOld;
                $javascriptNew = $jsNew;
                $promptOld = $promptOld_node;
                $promptNew = $promptNew_node;
            }
        }

        // Load workflow outputs
        $outputOld = $this->variantService->loadOutput($workflowNumber, $currentVariant, false);
        $outputNew = $this->variantService->loadOutput($workflowNumber, $currentVariant, true);

        return Inertia::render('Workflow/Show', [
            'workflowNumber' => $workflowNumber,
            'currentVariant' => $currentVariant,
            'availableVariants' => $availableVariants,
            'input' => $input,
            'preparePromptNodes' => $preparePromptNodes,
            'javascriptOld' => $javascriptOld,
            'javascriptNew' => $javascriptNew,
            'promptOld' => $promptOld,
            'promptNew' => $promptNew,
            'outputOld' => $outputOld,
            'outputNew' => $outputNew,
        ]);
    }

    /**
     * Save webhook input data
     */
    public function saveInput(Request $request, int $workflowNumber)
    {
        try {
            $this->ensureDebugDirectory('input');
            $inputFile = storage_path("app/n8n_debug/input/workflow_{$workflowNumber}_input.json");
            file_put_contents($inputFile, json_encode($request->all(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return response()->json([
                'success' => true,
                'message' => 'Input saved successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => "Failed to save input: {$e->getMessage()}",
            ], 500);
        }
    }

    /**
     * Reload JavaScript from the n8n workflow file
     */
    public function reloadJavaScriptFromWorkflow(int $workflowNumber)
    {
        try {
            $n8nWorkflowFile = base_path("n8n/workflow_{$workflowNumber}.json");
            if (! file_exists($n8nWorkflowFile)) {
                return response()->json([
                    'success' => false,
                    'error' => "Workflow file not found: workflow_{$workflowNumber}.json",
                ], 404);
            }

            $workflow = json_decode(file_get_contents($n8nWorkflowFile), true);
            if (! isset($workflow['nodes'])) {
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid workflow format: nodes array not found',
                ], 400);
            }

            // Find and extract the "Prepare Prompt" node JavaScript
            $javascript = null;
            foreach ($workflow['nodes'] as $node) {
                if ($node['name'] === 'Prepare Prompt' && isset($node['parameters']['jsCode'])) {
                    $javascript = $node['parameters']['jsCode'];
                    break;
                }
            }

            if ($javascript === null) {
                return response()->json([
                    'success' => false,
                    'error' => '"Prepare Prompt" node not found in workflow or has no jsCode',
                ], 404);
            }

            // Save the extracted JavaScript to storage
            $this->ensureDebugDirectory('prepare_prompt/old');
            $jsFile = storage_path("app/n8n_debug/prepare_prompt/old/workflow_{$workflowNumber}_prepare_prompt.js");
            file_put_contents($jsFile, $javascript);
            chmod($jsFile, 0644);

            return response()->json([
                'success' => true,
                'message' => 'JavaScript reloaded from workflow successfully',
                'code' => $javascript,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => "Failed to reload JavaScript from workflow: {$e->getMessage()}",
            ], 500);
        }
    }

    /**
     * Reload JavaScript from workflow file and save as new version
     */
    public function reloadJavaScriptFromWorkflowAsNew(int $workflowNumber)
    {
        try {
            $n8nWorkflowFile = base_path("n8n/workflow_{$workflowNumber}.json");
            if (! file_exists($n8nWorkflowFile)) {
                return response()->json([
                    'success' => false,
                    'error' => "Workflow file not found: workflow_{$workflowNumber}.json",
                ], 404);
            }

            $workflow = json_decode(file_get_contents($n8nWorkflowFile), true);
            if (! isset($workflow['nodes'])) {
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid workflow format: nodes array not found',
                ], 400);
            }

            // Find and extract the "Prepare Prompt" node JavaScript
            $javascript = null;
            foreach ($workflow['nodes'] as $node) {
                if ($node['name'] === 'Prepare Prompt' && isset($node['parameters']['jsCode'])) {
                    $javascript = $node['parameters']['jsCode'];
                    break;
                }
            }

            if ($javascript === null) {
                return response()->json([
                    'success' => false,
                    'error' => '"Prepare Prompt" node not found in workflow or has no jsCode',
                ], 404);
            }

            // Save the extracted JavaScript to storage as new version
            $this->ensureDebugDirectory('prepare_prompt/new');
            $jsFile = storage_path("app/n8n_debug/prepare_prompt/new/workflow_{$workflowNumber}_prepare_prompt.js");
            file_put_contents($jsFile, $javascript);
            chmod($jsFile, 0644);

            return response()->json([
                'success' => true,
                'message' => 'JavaScript reloaded from workflow and saved as new version',
                'code' => $javascript,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => "Failed to reload JavaScript from workflow: {$e->getMessage()}",
            ], 500);
        }
    }

    /**
     * Load JavaScript from the new version directory
     */
    public function loadJavaScriptNew(int $workflowNumber)
    {
        try {
            $jsFile = storage_path("app/n8n_debug/prepare_prompt/new/workflow_{$workflowNumber}_prepare_prompt.js");

            if (! file_exists($jsFile)) {
                return response()->json([
                    'success' => false,
                    'error' => "New JavaScript file not found for workflow_{$workflowNumber}",
                ], 404);
            }

            $javascript = file_get_contents($jsFile);

            return response()->json([
                'success' => true,
                'code' => $javascript,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => "Failed to load new JavaScript: {$e->getMessage()}",
            ], 500);
        }
    }

    /**
     * Save JavaScript code (old version)
     */
    public function saveOldJavaScript(Request $request, int $workflowNumber)
    {
        try {
            $request->validate([
                'code' => 'required|string',
            ]);

            $variant = $request->input('variant', $this->getVariant($workflowNumber));
            $nodeName = $request->input('nodeName', 'Prepare Prompt');

            $this->variantService->saveJavaScript($workflowNumber, $variant, $nodeName, $request->input('code'), false);

            return response()->json([
                'success' => true,
                'message' => 'JavaScript saved successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => "Failed to save JavaScript: {$e->getMessage()}",
            ], 500);
        }
    }

    /**
     * Save new JavaScript code version
     */
    public function saveNewJavaScript(Request $request, int $workflowNumber)
    {
        try {
            $request->validate([
                'code' => 'required|string',
            ]);

            $variant = $request->input('variant', $this->getVariant($workflowNumber));
            $nodeName = $request->input('nodeName', 'Prepare Prompt');

            $this->variantService->saveJavaScript($workflowNumber, $variant, $nodeName, $request->input('code'), true);

            return response()->json([
                'success' => true,
                'message' => 'New JavaScript saved successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => "Failed to save new JavaScript: {$e->getMessage()}",
            ], 500);
        }
    }

    /**
     * Workflow ID mapping for local development (from config)
     */
    private function getWorkflowId(int $workflowNumber): ?string
    {
        $workflowIds = config('services.n8n.workflow_ids', []);

        return $workflowIds[$workflowNumber] ?? null;
    }

    /**
     * Workflow ID mapping for live production server
     */
    private function getLiveWorkflowId(int $workflowNumber): ?string
    {
        $workflowIds = config('services.n8n.workflow_ids_live', []);

        return $workflowIds[$workflowNumber] ?? null;
    }

    /**
     * Save JavaScript to the n8n workflow file
     */
    public function saveJavaScriptToN8nWorkflow(Request $request, int $workflowNumber)
    {
        try {
            $request->validate([
                'code' => 'required|string',
            ]);

            $n8nWorkflowFile = base_path("n8n/workflow_{$workflowNumber}.json");
            if (! file_exists($n8nWorkflowFile)) {
                return response()->json([
                    'success' => false,
                    'error' => "Workflow file not found: workflow_{$workflowNumber}.json",
                ], 404);
            }

            $workflow = json_decode(file_get_contents($n8nWorkflowFile), true);
            if (! isset($workflow['nodes'])) {
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid workflow format: nodes array not found',
                ], 400);
            }

            // Find and update the "Prepare Prompt" node
            $found = false;
            foreach ($workflow['nodes'] as &$node) {
                if ($node['name'] === 'Prepare Prompt') {
                    $node['parameters']['jsCode'] = $request->input('code');
                    $found = true;
                    break;
                }
            }

            if (! $found) {
                return response()->json([
                    'success' => false,
                    'error' => '"Prepare Prompt" node not found in workflow',
                ], 404);
            }

            // Write the updated workflow back to file
            file_put_contents(
                $n8nWorkflowFile,
                json_encode($workflow, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            );

            return response()->json([
                'success' => true,
                'message' => 'JavaScript updated in n8n workflow successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => "Failed to save to n8n workflow: {$e->getMessage()}",
            ], 500);
        }
    }

    /**
     * Upload old workflow to n8n server
     */
    public function uploadOldWorkflowToN8n(int $workflowNumber)
    {
        try {
            // Get the current variant
            $variant = $this->getVariant($workflowNumber);
            $nodeNames = $this->variantService->extractPreparePromptNodeNames($workflowNumber, $variant);

            // Load JavaScript for each prepare prompt node
            $nodeJavaScript = [];
            foreach ($nodeNames as $nodeName) {
                $js = $this->variantService->loadJavaScript($workflowNumber, $variant, $nodeName, false);
                if ($js === null) {
                    return response()->json([
                        'success' => false,
                        'error' => "Old JavaScript file not found for node '{$nodeName}' in variant '{$variant}'",
                    ], 404);
                }
                $nodeJavaScript[$nodeName] = $js;
            }

            // Get the correct workflow file for this variant
            $n8nWorkflowFile = $this->variantService->getWorkflowFilePath($workflowNumber, $variant);
            if (! file_exists($n8nWorkflowFile)) {
                return response()->json([
                    'success' => false,
                    'error' => "Workflow file not found: {$n8nWorkflowFile}",
                ], 404);
            }

            $workflowId = $this->getWorkflowId($workflowNumber);
            if (! $workflowId) {
                return response()->json([
                    'success' => false,
                    'error' => "Unknown workflow number: {$workflowNumber}",
                ], 400);
            }

            $workflow = json_decode(file_get_contents($n8nWorkflowFile), true);
            if (! $workflow) {
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid workflow JSON format',
                ], 400);
            }

            // Update all Prepare Prompt nodes with the old JavaScript
            $foundCount = 0;
            foreach ($workflow['nodes'] as &$node) {
                foreach ($nodeNames as $nodeName) {
                    if ($node['name'] === $nodeName) {
                        $node['parameters']['jsCode'] = $nodeJavaScript[$nodeName];
                        $foundCount++;
                        break;
                    }
                }
            }

            if ($foundCount === 0) {
                return response()->json([
                    'success' => false,
                    'error' => 'No Prepare Prompt nodes found in workflow',
                ], 404);
            }

            // Save the updated workflow back to the file
            file_put_contents(
                $n8nWorkflowFile,
                json_encode($workflow, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            );

            // Build a clean workflow object with only the required fields for n8n API
            // The PUT /api/v1/workflows/{id} endpoint requires: name, nodes, connections, settings
            // All other fields are read-only or auto-managed by n8n
            $cleanWorkflow = [
                'name' => $workflow['name'] ?? 'workflow',
                'nodes' => $workflow['nodes'] ?? [],
                'connections' => $workflow['connections'] ?? [],
                'settings' => $workflow['settings'] ?? (object) [],
            ];

            // Upload to n8n
            $n8nClient = new \App\Services\N8nClient;
            $result = $n8nClient->updateWorkflow($workflowId, $cleanWorkflow);

            return response()->json($result);
        } catch (\Exception $e) {
            \Log::error('Failed to upload workflow to n8n', [
                'workflowNumber' => $workflowNumber,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => "Failed to upload workflow: {$e->getMessage()}",
            ], 500);
        }
    }

    /**
     * Upload new workflow version to n8n server
     */
    public function uploadNewWorkflowToN8n(int $workflowNumber)
    {
        try {
            // Get the current variant
            $variant = $this->getVariant($workflowNumber);
            $nodeNames = $this->variantService->extractPreparePromptNodeNames($workflowNumber, $variant);

            // Load JavaScript for each prepare prompt node
            $nodeJavaScript = [];
            foreach ($nodeNames as $nodeName) {
                $js = $this->variantService->loadJavaScript($workflowNumber, $variant, $nodeName, true);
                if ($js === null) {
                    return response()->json([
                        'success' => false,
                        'error' => "New JavaScript file not found for node '{$nodeName}' in variant '{$variant}'",
                    ], 404);
                }
                $nodeJavaScript[$nodeName] = $js;
            }

            // Get the correct workflow file for this variant
            $n8nWorkflowFile = $this->variantService->getWorkflowFilePath($workflowNumber, $variant);
            if (! file_exists($n8nWorkflowFile)) {
                return response()->json([
                    'success' => false,
                    'error' => "Workflow file not found: {$n8nWorkflowFile}",
                ], 404);
            }

            $workflowId = $this->getWorkflowId($workflowNumber);
            if (! $workflowId) {
                return response()->json([
                    'success' => false,
                    'error' => "Unknown workflow number: {$workflowNumber}",
                ], 400);
            }

            $workflow = json_decode(file_get_contents($n8nWorkflowFile), true);
            if (! $workflow) {
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid workflow JSON format',
                ], 400);
            }

            // Update all Prepare Prompt nodes with the new JavaScript
            $foundCount = 0;
            foreach ($workflow['nodes'] as &$node) {
                foreach ($nodeNames as $nodeName) {
                    if ($node['name'] === $nodeName) {
                        $node['parameters']['jsCode'] = $nodeJavaScript[$nodeName];
                        $foundCount++;
                        break;
                    }
                }
            }

            if ($foundCount === 0) {
                return response()->json([
                    'success' => false,
                    'error' => 'No Prepare Prompt nodes found in workflow',
                ], 404);
            }

            // Save the updated workflow back to the file
            file_put_contents(
                $n8nWorkflowFile,
                json_encode($workflow, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            );

            // Build a clean workflow object with only the required fields for n8n API
            $cleanWorkflow = [
                'name' => $workflow['name'] ?? 'workflow',
                'nodes' => $workflow['nodes'] ?? [],
                'connections' => $workflow['connections'] ?? [],
                'settings' => $workflow['settings'] ?? (object) [],
            ];

            // Upload to n8n
            $n8nClient = new \App\Services\N8nClient;
            $result = $n8nClient->updateWorkflow($workflowId, $cleanWorkflow);

            return response()->json($result);
        } catch (\Exception $e) {
            \Log::error('Failed to upload new workflow to n8n', [
                'workflowNumber' => $workflowNumber,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => "Failed to upload new workflow: {$e->getMessage()}",
            ], 500);
        }
    }

    /**
     * Upload workflow to live production n8n server
     */
    public function uploadWorkflowToLive(int $workflowNumber)
    {
        try {
            $n8nWorkflowFile = base_path("n8n/workflow_{$workflowNumber}.json");
            if (! file_exists($n8nWorkflowFile)) {
                return response()->json([
                    'success' => false,
                    'error' => "Workflow file not found: workflow_{$workflowNumber}.json",
                ], 404);
            }

            $workflowId = $this->getLiveWorkflowId($workflowNumber);
            if (! $workflowId) {
                return response()->json([
                    'success' => false,
                    'error' => "Live workflow ID not configured for workflow {$workflowNumber}. Please set N8N_WORKFLOW_{$workflowNumber}_ID_LIVE in your .env file.",
                ], 400);
            }

            $workflow = json_decode(file_get_contents($n8nWorkflowFile), true);
            if (! $workflow) {
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid workflow JSON format',
                ], 400);
            }

            // Build a clean workflow object with only the required fields for n8n API
            $cleanWorkflow = [
                'name' => $workflow['name'] ?? 'workflow',
                'nodes' => $workflow['nodes'] ?? [],
                'connections' => $workflow['connections'] ?? [],
                'settings' => $workflow['settings'] ?? (object) [],
            ];

            // Create a special N8nClient instance configured for the live server
            $liveN8nClient = $this->createLiveN8nClient();
            $result = $liveN8nClient->updateWorkflow($workflowId, $cleanWorkflow);

            return response()->json($result);
        } catch (\Exception $e) {
            \Log::error('Failed to upload workflow to live n8n server', [
                'workflowNumber' => $workflowNumber,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => "Failed to upload workflow to live server: {$e->getMessage()}",
            ], 500);
        }
    }

    /**
     * Create an N8nClient instance configured for the live production server
     */
    private function createLiveN8nClient(): \App\Services\N8nClient
    {
        // Temporarily override the config to point to the live server
        config([
            'services.n8n.url' => 'https://n8n.bettrprompt.ai',
            'services.n8n.api_key' => config('services.n8n.api_key_live'),
        ]);

        return new \App\Services\N8nClient;
    }

    /**
     * Prepare prompt using the old JavaScript version
     */
    public function preparePromptOld(Request $request, int $workflowNumber)
    {
        try {
            // Get variant and node name from request
            $variant = $request->input('variant', $this->getVariant($workflowNumber));
            $nodeName = $request->input('nodeName', 'Prepare Prompt');

            // Get input from request or load from storage
            $inputData = $request->input('input');

            if ($inputData === null) {
                // Fall back to loading from storage/app/n8n_debug/input/
                $inputFile = storage_path("app/n8n_debug/input/workflow_{$workflowNumber}_input.json");

                if (! file_exists($inputFile)) {
                    return response()->json([
                        'success' => false,
                        'error' => "Input file not found for workflow_{$workflowNumber}",
                    ], 404);
                }

                $content = json_decode(file_get_contents($inputFile), true);
                // Handle array format from n8n
                if (is_array($content) && isset($content[0]['body'])) {
                    $inputData = $content[0];
                } else {
                    $inputData = $content;
                }

                if ($inputData === null) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Invalid input format',
                    ], 400);
                }
            } else {
                // Handle array format from request (e.g., when user pastes n8n array format)
                if (is_array($inputData) && isset($inputData[0]['body'])) {
                    $inputData = $inputData[0];
                }
            }

            // Load JavaScript from variant-specific path
            $javascript = $this->variantService->loadJavaScript($workflowNumber, $variant, $nodeName, false);

            if ($javascript === null) {
                return response()->json([
                    'success' => false,
                    'error' => "JavaScript file not found for workflow_{$workflowNumber}, variant={$variant}, node={$nodeName}",
                ], 404);
            }

            // Execute the JavaScript using Node.js
            // Note: We don't normalize JavaScript anymore since buildNodeScript handles modern syntax
            $nodeScript = $this->buildNodeScript($inputData, $javascript);

            if (empty($nodeScript)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to build Node.js script',
                ], 400);
            }

            $output = $this->executeNode($nodeScript);

            if (! $output['success']) {
                return response()->json([
                    'success' => false,
                    'error' => $output['error'],
                ], 400);
            }

            $result = $output['result'];

            // Save prompt to variant-specific path
            $this->variantService->savePrompt($workflowNumber, $variant, $nodeName, $result, false);

            return response()->json([
                'success' => true,
                'prompt' => $result,
                'system' => $result['system'] ?? null,
                'messages' => $result['messages'] ?? null,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        } finally {
            // Clean up temporary files created during execution
            foreach ($this->tempFilesToCleanup as $file) {
                if (file_exists($file)) {
                    unlink($file);
                }
            }
        }
    }

    /**
     * Prepare prompt using the new JavaScript version
     */
    public function preparePromptNew(Request $request, int $workflowNumber)
    {
        try {
            // Get variant and node name from request
            $variant = $request->input('variant', $this->getVariant($workflowNumber));
            $nodeName = $request->input('nodeName', 'Prepare Prompt');

            // Get input from request or load from storage
            $inputData = $request->input('input');

            if ($inputData === null) {
                // Fall back to loading from storage/app/n8n_debug/input/
                $inputFile = storage_path("app/n8n_debug/input/workflow_{$workflowNumber}_input.json");

                if (! file_exists($inputFile)) {
                    return response()->json([
                        'success' => false,
                        'error' => "Input file not found for workflow_{$workflowNumber}",
                    ], 404);
                }

                $content = json_decode(file_get_contents($inputFile), true);
                // Handle array format from n8n
                if (is_array($content) && isset($content[0]['body'])) {
                    $inputData = $content[0];
                } else {
                    $inputData = $content;
                }

                if ($inputData === null) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Invalid input format',
                    ], 400);
                }
            } else {
                // Handle array format from request (e.g., when user pastes n8n array format)
                if (is_array($inputData) && isset($inputData[0]['body'])) {
                    $inputData = $inputData[0];
                }
            }

            // Debug: Log what we received for multi-pass scenarios
            $hasClassification = isset($inputData['classification']);
            $hasSelectedQuestions = isset($inputData['selected_questions']);
            \Log::info('preparePromptNew debug - Input received', [
                'variant' => $variant,
                'nodeName' => $nodeName,
                'hasClassification' => $hasClassification,
                'hasSelectedQuestions' => $hasSelectedQuestions,
                'inputDataKeys' => array_keys((array) $inputData),
                'inputDataStructure' => [
                    'has_body' => isset($inputData['body']),
                    'has_headers' => isset($inputData['headers']),
                    'has_classification' => isset($inputData['classification']),
                    'classification_type' => isset($inputData['classification']) ? gettype($inputData['classification']) : 'N/A',
                    'classification_keys' => isset($inputData['classification']) ? array_keys((array) $inputData['classification']) : [],
                ],
            ]);

            // Load JavaScript from variant-specific path
            $javascript = $this->variantService->loadJavaScript($workflowNumber, $variant, $nodeName, true);

            if ($javascript === null) {
                return response()->json([
                    'success' => false,
                    'error' => "New JavaScript file not found for workflow_{$workflowNumber}, variant={$variant}, node={$nodeName}",
                ], 404);
            }

            // Execute the JavaScript using Node.js
            $nodeScript = $this->buildNodeScript($inputData, $javascript);

            if (empty($nodeScript)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to build Node.js script',
                ], 400);
            }

            $output = $this->executeNode($nodeScript);

            if (! $output['success']) {
                // Log the error for debugging
                \Log::error('preparePromptNew execution failed', [
                    'error' => $output['error'],
                    'debug_output' => $output['debug_output'] ?? null,
                    'variant' => $variant,
                    'nodeName' => $nodeName,
                ]);

                return response()->json([
                    'success' => false,
                    'error' => $output['error'],
                ], 400);
            }

            $result = $output['result'];

            // Save prompt to variant-specific path
            $this->variantService->savePrompt($workflowNumber, $variant, $nodeName, $result, true);

            return response()->json([
                'success' => true,
                'prompt' => $result,
                'system' => $result['system'] ?? null,
                'messages' => $result['messages'] ?? null,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        } finally {
            // Clean up temporary files created during execution
            foreach ($this->tempFilesToCleanup as $file) {
                if (file_exists($file)) {
                    unlink($file);
                }
            }
        }
    }

    /**
     * Ensure debug directory structure exists
     */
    private function ensureDebugDirectory(string $subdirectory = ''): string
    {
        $basePath = storage_path('app/n8n_debug');
        $fullPath = $subdirectory ? "{$basePath}/{$subdirectory}" : $basePath;

        if (! is_dir($fullPath)) {
            mkdir($fullPath, 0755, true);
        }

        return $fullPath;
    }

    /**
     * Load reference documents from resources/reference_documents/
     */
    private function loadReferenceDocuments(): array
    {
        $referenceDocsPath = resource_path('reference_documents');

        $referenceData = [
            'framework_taxonomy' => null,
            'personality_calibration' => null,
            'question_bank' => null,
            'framework_templates' => [],
        ];

        // Load framework taxonomy
        $frameworkFile = "{$referenceDocsPath}/framework_taxonomy.md";
        if (file_exists($frameworkFile)) {
            $referenceData['framework_taxonomy'] = [
                'content' => file_get_contents($frameworkFile),
            ];
        }

        // Load personality calibration
        $personalityFile = "{$referenceDocsPath}/personality_calibration.md";
        if (file_exists($personalityFile)) {
            $personalityContent = file_get_contents($personalityFile);
            // Support both key names for compatibility with different workflows
            $referenceData['personality_calibration'] = [
                'content' => $personalityContent,
            ];
            $referenceData['personality_calibration_full'] = [
                'content' => $personalityContent,
            ];
        }

        // Load question bank
        $questionBankFile = "{$referenceDocsPath}/question_bank.md";
        if (file_exists($questionBankFile)) {
            $referenceData['question_bank'] = [
                'content' => file_get_contents($questionBankFile),
            ];
        }

        // Load framework templates from directory
        $frameworkTemplatesDir = "{$referenceDocsPath}/framework_templates";
        if (is_dir($frameworkTemplatesDir)) {
            $templateFiles = glob("{$frameworkTemplatesDir}/*.md");
            foreach ($templateFiles as $templateFile) {
                $templateName = strtoupper(str_replace('.md', '', basename($templateFile)));
                $referenceData['framework_templates'][$templateName] = file_get_contents($templateFile);
            }
        }

        return $referenceData;
    }

    /**
     * Build a Node.js script to execute the workflow JavaScript
     */
    private function buildNodeScript(array $inputData, string $javascript): string
    {
        // Prepare mock objects for the n8n workflow context
        $webhookData = [
            'body' => $inputData['body'] ?? $inputData,
            'headers' => $inputData['headers'] ?? [],
            'params' => $inputData['params'] ?? [],
            'query' => $inputData['query'] ?? [],
        ];

        // Check if this is a multi-pass scenario (has classification or selected_questions from previous pass)
        $isMultiPass = isset($inputData['classification']) || isset($inputData['selected_questions']);

        // For multi-pass scenarios, use the merged input data as $input (simulating previous node's output)
        $inputNodeData = $isMultiPass ? $inputData : $webhookData;

        // Load reference documents from resources/reference_documents/
        $referenceData = $this->loadReferenceDocuments();

        $webhookDataJson = json_encode($webhookData, JSON_UNESCAPED_SLASHES);
        if ($webhookDataJson === false) {
            throw new \Exception('Failed to encode webhook data: '.json_last_error_msg());
        }

        $referenceDataJson = json_encode($referenceData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($referenceDataJson === false) {
            throw new \Exception('Failed to encode reference data: '.json_last_error_msg());
        }

        // Write JavaScript and data to storage directory for debugging
        // Use storage path to avoid temp file cleanup issues
        $debugDir = $this->ensureDebugDirectory();

        $this->tempFilesToCleanup = [];

        $tempJsFile = "{$debugDir}/workflow_exec_code.js";
        file_put_contents($tempJsFile, $javascript);
        $this->tempFilesToCleanup[] = $tempJsFile;

        // Write data as a JSON file
        $tempDataFile = "{$debugDir}/workflow_exec_data.json";
        $dataObject = [
            'webhook' => $webhookData,
            'reference' => $referenceData,
            'input' => $inputNodeData,
        ];
        file_put_contents($tempDataFile, json_encode($dataObject, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        $this->tempFilesToCleanup[] = $tempDataFile;

        // Debug: Log what we're passing to Node.js
        \Log::info('buildNodeScript: Passing to Node.js', [
            'isMultiPass' => $isMultiPass,
            'inputNodeDataType' => gettype($inputNodeData),
            'inputNodeDataKeys' => array_keys((array) $inputNodeData),
            'hasClassificationInInput' => isset($inputNodeData['classification']) ? 'yes' : 'no',
            'inputNodeDataSnapshot' => [
                'has_body' => isset($inputNodeData['body']),
                'has_headers' => isset($inputNodeData['headers']),
                'has_classification' => isset($inputNodeData['classification']),
                'classification_type' => isset($inputNodeData['classification']) ? gettype($inputNodeData['classification']) : 'N/A',
            ],
            'webhookDataKeys' => array_keys((array) $webhookData),
        ]);

        // Build the Node.js script with proper file paths
        // Properly escape file paths for Node.js string literals
        $dataFileEscaped = json_encode($tempDataFile);
        $jsFileEscaped = json_encode($tempJsFile);

        $nodeScript = "(async () => {
// Mock n8n environment
const $ = function(nodeName) {
  return {
    first() {
      return {
        json: \$._nodeData[nodeName] || {}
      };
    }
  };
};

// Initialize node data from file
const fs = require('fs');
const data = JSON.parse(fs.readFileSync({$dataFileEscaped}, 'utf8'));

// Store node data
\$._nodeData = {
  'Webhook Trigger': data.webhook,
  'Load Reference Documents': data.reference
};

// Mock \$input for accessing current node input
// For multi-pass workflows, this will be the output from the previous node (including classification)
// For single-pass workflows, this will be the webhook data
const \$input = {
  first() {
    return {
      json: data.input || {}
    };
  }
};

// Execute the workflow code
try {
  // Load and execute the user's code from file
  const userCode = fs.readFileSync({$jsFileEscaped}, 'utf8');

  // Wrap the code and execute it
  let evalResult;
  try {
    // Try wrapping in an async function to allow return statements
    evalResult = eval('(async () => { ' + userCode + ' })()');
    // Need to use Promise since we wrapped it in async
    evalResult = await evalResult;
  } catch (e1) {
    try {
      // If that fails, try wrapping in parentheses for expression eval
      evalResult = eval('(' + userCode + ')');
    } catch (e2) {
      try {
        // Last resort: execute the code directly for statements that don't return
        eval(userCode);
        evalResult = null;  // Code executed via eval with statements, not expressions
      } catch (e3) {
        // All attempts failed, throw the first error
        throw e1;
      }
    }
  }

  // Check if the result is an array with json object (n8n workflow return format)
  let systemValue = null;
  let messagesValue = null;

  if (evalResult) {
    if (Array.isArray(evalResult) && evalResult[0]?.json) {
      // Extract from n8n return format: [{ json: { system: '...', messages: [...] } }]
      systemValue = evalResult[0].json.system || null;
      messagesValue = evalResult[0].json.messages || null;
    }
  }

  // Fall back to looking for global variables
  if (!systemValue) {
    systemValue = typeof system !== 'undefined' ? system : null;
  }
  if (!messagesValue) {
    messagesValue = typeof messages !== 'undefined' ? messages : null;
  }

  console.log(JSON.stringify({
    success: true,
    system: systemValue,
    messages: messagesValue,
    result: {
      system: systemValue,
      messages: messagesValue
    }
  }));
} catch (error) {
  console.log(JSON.stringify({
    success: false,
    error: error.message,
    stack: error.stack
  }));
  process.exit(1);
}
})();";

        return $nodeScript;
    }

    /**
     * Execute Node.js script and return the result
     */
    private function executeNode(string $script): array
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'n8n_debug_');
        file_put_contents($tempFile, $script);

        try {
            // Verify temp files exist before executing
            if (! file_exists($tempFile)) {
                return [
                    'success' => false,
                    'error' => 'Wrapper script file was not created',
                ];
            }

            // Note: JS and data files should exist due to buildNodeScript creating them
            // They will be cleaned up after this method returns

            // Execute with timeout (30 seconds should be plenty for JS execution)
            $output = shell_exec("timeout 30 node {$tempFile} 2>&1");

            // The output might contain console.log() output before the JSON
            // Find the last JSON object (which should be our result)
            if (empty($output)) {
                return [
                    'success' => false,
                    'error' => 'Node.js produced no output',
                ];
            }

            // Try to extract JSON from the output
            // Look for the pattern: {...success...}
            $result = null;

            // Strategy 1: Try decoding the entire output
            $result = json_decode($output, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($result) && isset($result['success'])) {
                return $result;
            }

            // Strategy 2: Find the last { ... } JSON block
            $trimmed = rtrim($output);
            if (preg_match('/\{[^{}]*(?:\{[^{}]*\}[^{}]*)*\}$/', $trimmed, $matches)) {
                $result = json_decode($matches[0], true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($result)) {
                    return $result;
                }
            }

            // Strategy 3: Extract JSON starting from last {
            $lastBrace = strrpos($output, '{');
            if ($lastBrace !== false) {
                $jsonString = substr($output, $lastBrace);
                $result = json_decode($jsonString, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($result)) {
                    return $result;
                }
            }

            // If all strategies failed, return the raw output as error
            \Log::error('Node.js output parsing failed', [
                'output' => $output,
                'output_length' => strlen($output),
                'first_200_chars' => substr($output, 0, 200),
                'last_200_chars' => substr($output, -200),
            ]);

            return [
                'success' => false,
                'error' => 'Invalid or unexpected token',
                'debug_output' => substr($output, 0, 500), // Include first 500 chars for debugging
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        } finally {
            // Clean up the wrapper script file
            unlink($tempFile);
            // Note: JS and data files cleanup is handled by executeJavaScript finally block
        }
    }

    /**
     * Load and extract input data from request or file
     */
    private function loadInputData(Request $request, int $workflowNumber): ?array
    {
        $inputData = $request->input('input');

        // If inputData came from the request and has envelope fields, extract the body
        if ($inputData !== null && isset($inputData['body']) && ! isset($inputData['task_description'])) {
            $inputData = $inputData['body'];
        }

        if ($inputData === null) {
            $inputFile = storage_path("app/n8n_debug/input/workflow_{$workflowNumber}_input.json");

            if (! file_exists($inputFile)) {
                return null;
            }

            $content = json_decode(file_get_contents($inputFile), true);

            if (! $content) {
                return null;
            }

            // Extract the body from the stored input
            if (is_array($content) && count($content) > 0 && isset($content[0]['body'])) {
                $inputData = $content[0]['body'];
            } elseif (is_array($content) && isset($content['body'])) {
                $inputData = $content['body'];
            } else {
                return null;
            }
        }

        return $inputData;
    }

    /**
     * Execute workflow and return results using PromptFrameworkService
     */
    private function executeWorkflowWithService(
        int $workflowNumber,
        ?string $taskDescription,
        ?array $userContext
    ): array {
        $promptService = new \App\Services\PromptFrameworkService;

        return match ($workflowNumber) {
            0 => $promptService->preAnalyseTask($taskDescription, $userContext),
            1 => $promptService->analyseTask(
                $taskDescription,
                $userContext['personality']['personality_type'] ?? null,
                $userContext['personality']['trait_percentages'] ?? null,
                null,
                null,
                $userContext
            ),
            default => throw new \InvalidArgumentException("Workflow {$workflowNumber} is not supported for direct execution"),
        };
    }

    /**
     * Save workflow output to storage and n8n directory
     */
    private function saveWorkflowOutput(int $workflowNumber, array $resultData, bool $isNew = false): void
    {
        $variant = $isNew ? 'new' : 'old';

        // Save to storage/app/n8n_debug/output/{old|new}/
        $this->ensureDebugDirectory("output/{$variant}");
        $debugOutputFile = storage_path("app/n8n_debug/output/{$variant}/workflow_{$workflowNumber}_output.json");
        file_put_contents($debugOutputFile, json_encode($resultData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    /**
     * Execute the actual n8n workflow for the old version
     */
    public function executeOldWorkflow(Request $request, int $workflowNumber)
    {
        try {
            $inputData = $this->loadInputData($request, $workflowNumber);

            if ($inputData === null) {
                return response()->json([
                    'success' => false,
                    'error' => "Input file not found for workflow_{$workflowNumber}",
                ], 404);
            }

            $taskDescription = $inputData['task_description'] ?? null;
            $userContext = $inputData['user_context'] ?? null;

            if (! $taskDescription) {
                return response()->json([
                    'success' => false,
                    'error' => 'task_description is required in input data',
                ], 400);
            }

            // Execute the already-uploaded workflow without uploading it again
            // The "Upload to n8n" button is responsible for uploading the workflow
            $resultData = $this->executeWorkflowWithService($workflowNumber, $taskDescription, $userContext);
            $this->saveWorkflowOutput($workflowNumber, $resultData);

            return response()->json([
                'success' => true,
                'output' => $resultData,
                'system' => $resultData['system'] ?? null,
                'messages' => $resultData['messages'] ?? null,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Execute the actual n8n workflow for the new version
     */
    public function executeNewWorkflow(Request $request, int $workflowNumber)
    {
        try {
            $inputData = $this->loadInputData($request, $workflowNumber);

            if ($inputData === null) {
                return response()->json([
                    'success' => false,
                    'error' => "Input file not found for workflow_{$workflowNumber}",
                ], 404);
            }

            $taskDescription = $inputData['task_description'] ?? null;
            $userContext = $inputData['user_context'] ?? null;

            if (! $taskDescription) {
                return response()->json([
                    'success' => false,
                    'error' => 'task_description is required in input data',
                ], 400);
            }

            // Execute the already-uploaded workflow without uploading it again
            // The "Upload to n8n" button is responsible for uploading the workflow
            $resultData = $this->executeWorkflowWithService($workflowNumber, $taskDescription, $userContext);
            $this->saveWorkflowOutput($workflowNumber, $resultData, true);

            return response()->json([
                'success' => true,
                'output' => $resultData,
                'system' => $resultData['system'] ?? null,
                'messages' => $resultData['messages'] ?? null,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
