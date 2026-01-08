<?php

namespace App\Http\Controllers;

use App\Data\GenerationPayload;
use App\Services\N8nClient;
use App\Services\N8nWorkflowClient;
use App\Services\WorkflowVariantService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use InvalidArgumentException;
use Log;
use Throwable;

class DebugN8nController extends Controller
{
    // ======== CLASS CONSTANTS ========

    /**
     * Base path for debug files (relative to storage/app/)
     */
    private const DEBUG_BASE_PATH = 'app/n8n_debug';

    /**
     * Directory for storing input data
     */
    private const INPUT_DIR = 'input';

    /**
     * Default prepare prompt node name
     */
    private const DEFAULT_NODE_NAME = 'Prepare Prompt';

    /**
     * @var array List of temporary files to clean up after execution
     */
    private array $tempFilesToCleanup = [];

    /**
     * Inject dependencies
     */
    public function __construct(
        private readonly WorkflowVariantService $variantService,
        private readonly N8nClient $n8nClient
    ) {}

    // ======== VARIANT AND PASS MANAGEMENT ========

    /**
     * Get current variant for workflow (from query parameter or default)
     */
    protected function getVariant(Request $request, int $workflowNumber): string
    {
        return $request->query('variant')
            ?? $this->variantService->getDefaultVariant($workflowNumber);
    }

    /**
     * Get current pass for workflow (from query parameter or default)
     * Returns 0-based index (0 = Pass 1, 1 = Pass 2, etc.)
     */
    protected function getPass(Request $request): int
    {
        $pass = $request->query('pass', 0);

        return is_numeric($pass) && $pass >= 0 ? (int) $pass : 0;
    }

    /**
     * Validate and confirm a variant selection
     */
    public function setVariant(Request $request, $workflowNumber)
    {
        $workflowNumber = (int) $workflowNumber;
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

            // Return success with redirect URL so client can navigate to variant
            return response()->json([
                'success' => true,
                'redirectUrl' => route('workflow.show', ['workflowNumber' => $workflowNumber, 'variant' => $variant]),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => "Failed to set variant: {$e->getMessage()}",
            ], 500);
        }
    }

    // ======== WORKFLOW DISPLAY ========

    /**
     * Display the workflow page
     */
    public function show(Request $request, $workflowNumber)
    {
        $workflowNumber = (int) $workflowNumber;
        $currentVariant = $this->getVariant($request, $workflowNumber);
        $currentPass = $this->getPass($request);
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
            $passNumber = $this->variantService->getPassNumberFromNodeName($nodeName);

            $jsOld = $this->variantService->loadJavaScript($workflowNumber, $currentVariant, $nodeName, false);
            $jsNew = $this->variantService->loadJavaScript($workflowNumber, $currentVariant, $nodeName, true);
            $promptOld_node = $this->variantService->loadPrompt($workflowNumber, $currentVariant, $nodeName, false);
            $promptNew_node = $this->variantService->loadPrompt($workflowNumber, $currentVariant, $nodeName, true);

            // For Pass 2+, check for pass-specific input file
            $passInput = null;
            if ($passNumber > 1) {
                $passInputFile = storage_path("app/n8n_debug/input/workflow_{$workflowNumber}_input_".($passNumber - 1).'.json');
                if (file_exists($passInputFile)) {
                    $passInput = json_decode(file_get_contents($passInputFile), true);
                }
            }

            $preparePromptNodes[] = [
                'name' => $nodeName,
                'passNumber' => $passNumber,
                'passInput' => $passInput,
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

        // Clamp pass to valid range in case variant changed
        if ($currentPass >= count($preparePromptNodes)) {
            $currentPass = 0;
        }

        return Inertia::render('Workflow/Show', [
            'workflowNumber' => $workflowNumber,
            'currentVariant' => $currentVariant,
            'currentPass' => $currentPass,
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

    // ======== INPUT DATA MANAGEMENT ========

    /**
     * Save webhook input data
     */
    public function saveInput(Request $request, $workflowNumber)
    {
        $workflowNumber = (int) $workflowNumber;
        try {
            $this->ensureDebugDirectory('input');
            $inputFile = storage_path("app/n8n_debug/input/workflow_{$workflowNumber}_input.json");
            file_put_contents($inputFile, json_encode($request->all(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return response()->json([
                'success' => true,
                'message' => 'Input saved successfully',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => "Failed to save input: {$e->getMessage()}",
            ], 500);
        }
    }

    /**
     * Save pass-specific input data for Pass 2+
     * Stored as workflow_X_input_Y.json where Y is passNumber - 1
     */
    public function savePassInput(Request $request, $workflowNumber, $passNumber)
    {
        $workflowNumber = (int) $workflowNumber;
        $passNumber = (int) $passNumber;
        try {
            $this->ensureDebugDirectory('input');

            // Pass input is stored as workflow_X_input_Y.json where Y is passNumber - 1
            // e.g., Pass 2 input is workflow_1_input_1.json (output from Pass 1)
            $inputFile = storage_path("app/n8n_debug/input/workflow_{$workflowNumber}_input_".($passNumber - 1).'.json');

            $content = json_encode($request->all(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            file_put_contents($inputFile, $content);

            return response()->json([
                'success' => true,
                'message' => 'Pass input saved successfully',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => "Failed to save pass input: {$e->getMessage()}",
            ], 500);
        }
    }

    // ======== JAVASCRIPT MANAGEMENT ========

    /**
     * Reload JavaScript from the n8n workflow file
     */
    public function reloadJavaScriptFromWorkflow(Request $request, $workflowNumber)
    {
        $workflowNumber = (int) $workflowNumber;

        return $this->reloadJavaScriptFromWorkflowInternal($request, $workflowNumber, false);
    }

    /**
     * Reload JavaScript from workflow file and save as new version
     */
    public function reloadJavaScriptFromWorkflowAsNew(Request $request, $workflowNumber)
    {
        $workflowNumber = (int) $workflowNumber;

        return $this->reloadJavaScriptFromWorkflowInternal($request, $workflowNumber, true);
    }

    /**
     * Reload JavaScript from n8n workflow and save to specified version
     */
    private function reloadJavaScriptFromWorkflowInternal(
        Request $request,
        int $workflowNumber,
        bool $isNew
    ): JsonResponse {
        try {
            $variant = $this->getVariant($request, $workflowNumber);
            $nodeNames = $this->variantService->extractPreparePromptNodeNames($workflowNumber, $variant);

            if (empty($nodeNames)) {
                return response()->json([
                    'success' => false,
                    'error' => 'No prepare prompt nodes found in variant configuration',
                ], 404);
            }

            $n8nWorkflowFile = $this->variantService->getWorkflowFilePath($workflowNumber, $variant);
            if (! file_exists($n8nWorkflowFile)) {
                return response()->json([
                    'success' => false,
                    'error' => "Workflow file not found: $n8nWorkflowFile",
                ], 404);
            }

            $reloadedNodes = [];
            foreach ($nodeNames as $nodeName) {
                $javascript = $this->variantService->extractJavaScriptFromNode($workflowNumber, $variant, $nodeName);

                if ($javascript === null) {
                    return response()->json([
                        'success' => false,
                        'error' => "Node '$nodeName' not found in workflow or has no jsCode",
                    ], 404);
                }

                $reloadedNodes[] = [
                    'nodeName' => $nodeName,
                    'javascript' => $javascript,
                    'codeLength' => strlen($javascript),
                ];
            }

            return response()->json([
                'success' => true,
                'message' => 'JavaScript reloaded from workflow',
                'reloadedNodes' => $reloadedNodes,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => "Failed to reload JavaScript from workflow: {$e->getMessage()}",
            ], 500);
        }
    }

    /**
     * Save JavaScript code (old version)
     */
    public function saveOldJavaScript(Request $request, $workflowNumber)
    {
        $workflowNumber = (int) $workflowNumber;

        return $this->saveJavaScript($request, $workflowNumber, false);
    }

    /**
     * Save new JavaScript code version
     */
    public function saveNewJavaScript(Request $request, $workflowNumber)
    {
        $workflowNumber = (int) $workflowNumber;

        return $this->saveJavaScript($request, $workflowNumber, true);
    }

    /**
     * Save JavaScript code with version control
     */
    private function saveJavaScript(Request $request, int $workflowNumber, bool $isNew): JsonResponse
    {
        try {
            $request->validate([
                'code' => 'required|string',
            ]);

            $variant = $request->input('variant', $this->getVariant($request, $workflowNumber));
            $nodeName = $request->input('nodeName', self::DEFAULT_NODE_NAME);
            $code = $request->input('code');

            $this->variantService->saveJavaScript($workflowNumber, $variant, $nodeName, $code, $isNew);

            $versionLabel = $isNew ? 'New' : '';

            return response()->json([
                'success' => true,
                'message' => "$versionLabel JavaScript saved successfully",
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => "Failed to save JavaScript: {$e->getMessage()}",
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

    // ======== WORKFLOW FILE OPERATIONS ========

    /**
     * Save JavaScript to the n8n workflow file
     */
    public function saveJavaScriptToN8nWorkflow(Request $request, $workflowNumber)
    {
        $workflowNumber = (int) $workflowNumber;
        try {
            $request->validate([
                'code' => 'required|string',
            ]);

            $n8nWorkflowFile = base_path("n8n/workflow_$workflowNumber.json");
            if (! file_exists($n8nWorkflowFile)) {
                return response()->json([
                    'success' => false,
                    'error' => "Workflow file not found: workflow_$workflowNumber.json",
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
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => "Failed to save to n8n workflow: {$e->getMessage()}",
            ], 500);
        }
    }

    /**
     * Upload old workflow version to n8n server
     */
    public function uploadOldWorkflowToN8n(Request $request, $workflowNumber)
    {
        $workflowNumber = (int) $workflowNumber;

        return $this->uploadWorkflowToN8nInternal($request, $workflowNumber, false);
    }

    /**
     * Upload new workflow version to n8n server
     */
    public function uploadNewWorkflowToN8n(Request $request, $workflowNumber)
    {
        $workflowNumber = (int) $workflowNumber;

        return $this->uploadWorkflowToN8nInternal($request, $workflowNumber, true);
    }

    /**
     * Upload workflow to n8n server with specified version
     */
    private function uploadWorkflowToN8nInternal(
        Request $request,
        int $workflowNumber,
        bool $isNew
    ): JsonResponse {
        try {
            $variant = $this->getVariant($request, $workflowNumber);
            $nodeNames = $this->variantService->extractPreparePromptNodeNames($workflowNumber, $variant);

            // Load JavaScript for each prepare prompt node
            $nodeJavaScript = [];
            foreach ($nodeNames as $nodeName) {
                $js = $this->variantService->loadJavaScript($workflowNumber, $variant, $nodeName, $isNew);
                $versionType = $isNew ? 'New' : 'Old';
                if ($js === null) {
                    return response()->json([
                        'success' => false,
                        'error' => "$versionType JavaScript file not found for node '$nodeName' in variant '$variant'",
                    ], 404);
                }
                $nodeJavaScript[$nodeName] = $js;
            }

            $n8nWorkflowFile = $this->variantService->getWorkflowFilePath($workflowNumber, $variant);
            if (! file_exists($n8nWorkflowFile)) {
                return response()->json([
                    'success' => false,
                    'error' => "Workflow file not found: $n8nWorkflowFile",
                ], 404);
            }

            $workflowId = $this->getWorkflowId($workflowNumber);
            if (! $workflowId) {
                return response()->json([
                    'success' => false,
                    'error' => "Unknown workflow number: $workflowNumber",
                ], 400);
            }

            $workflow = json_decode(file_get_contents($n8nWorkflowFile), true);
            if (! $workflow) {
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid workflow JSON format',
                ], 400);
            }

            // Update all Prepare Prompt nodes with the JavaScript
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

            // Build a clean workflow object with only required fields for n8n API
            $cleanWorkflow = [
                'name' => $workflow['name'] ?? 'workflow',
                'nodes' => $workflow['nodes'] ?? [],
                'connections' => $workflow['connections'] ?? [],
                'settings' => $workflow['settings'] ?? (object) [],
            ];

            $result = $this->n8nClient->updateWorkflow($workflowId, $cleanWorkflow);

            return response()->json($result);
        } catch (Throwable $e) {
            Log::error('Failed to upload workflow to n8n', [
                'workflowNumber' => $workflowNumber,
                'isNew' => $isNew,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => "Failed to upload workflow: {$e->getMessage()}",
            ], 500);
        }
    }

    // ======== LIVE SERVER OPERATIONS ========

    /**
     * Upload workflow to live production n8n server
     */
    public function uploadWorkflowToLive($workflowNumber)
    {
        $workflowNumber = (int) $workflowNumber;
        try {
            $n8nWorkflowFile = base_path("n8n/workflow_$workflowNumber.json");
            if (! file_exists($n8nWorkflowFile)) {
                return response()->json([
                    'success' => false,
                    'error' => "Workflow file not found: workflow_$workflowNumber.json",
                ], 404);
            }

            $workflowId = $this->getLiveWorkflowId($workflowNumber);
            if (! $workflowId) {
                return response()->json([
                    'success' => false,
                    'error' => "Live workflow ID not configured for workflow $workflowNumber. Please set N8N_WORKFLOW_{$workflowNumber}_ID_LIVE in your .env file.",
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
        } catch (Throwable $e) {
            Log::error('Failed to upload workflow to live n8n server', [
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
    private function createLiveN8nClient(): N8nClient
    {
        // Temporarily override the config to point to the live server
        config([
            'services.n8n.url' => 'https://n8n.bettrprompt.ai',
            'services.n8n.api_key' => config('services.n8n.api_key_live'),
        ]);

        return new N8nClient;
    }

    // ======== PROMPT PREPARATION ========

    /**
     * Prepare prompt using the old JavaScript version
     */
    public function preparePromptOld(Request $request, $workflowNumber)
    {
        $workflowNumber = (int) $workflowNumber;

        return $this->preparePromptInternal($request, $workflowNumber, false);
    }

    /**
     * Prepare prompt using the new JavaScript version
     */
    public function preparePromptNew(Request $request, $workflowNumber)
    {
        $workflowNumber = (int) $workflowNumber;

        return $this->preparePromptInternal($request, $workflowNumber, true);
    }

    /**
     * Prepare prompt with specified JavaScript version
     */
    private function preparePromptInternal(
        Request $request,
        int $workflowNumber,
        bool $isNew
    ): JsonResponse {
        try {
            $variant = $request->input('variant', $this->getVariant($request, $workflowNumber));
            $nodeName = $request->input('nodeName', self::DEFAULT_NODE_NAME);
            $passNumber = $this->variantService->getPassNumberFromNodeName($nodeName);

            // Load input data
            $inputData = $this->loadPreparePromptInput($request, $workflowNumber, $variant, $passNumber);
            if (! $inputData['success']) {
                return response()->json($inputData, $inputData['status_code'] ?? 400);
            }
            $inputData = $inputData['data'];

            // Load JavaScript from variant-specific path
            $javascript = $this->variantService->loadJavaScript($workflowNumber, $variant, $nodeName, $isNew);
            if ($javascript === null) {
                return response()->json([
                    'success' => false,
                    'error' => "JavaScript file not found for workflow_$workflowNumber, variant=$variant, node=$nodeName",
                ], 404);
            }

            // Execute JavaScript and capture output
            $nodeScript = $this->buildNodeScript($inputData, $javascript);
            if (empty($nodeScript)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to build Node.js script',
                ], 400);
            }

            $output = $this->executeNode($nodeScript);
            if (! $output['success']) {
                if ($isNew) {
                    Log::error('preparePromptNew execution failed', [
                        'error' => $output['error'],
                        'debug_output' => $output['debug_output'] ?? null,
                        'variant' => $variant,
                        'nodeName' => $nodeName,
                    ]);
                }

                return response()->json([
                    'success' => false,
                    'error' => $output['error'],
                ], 400);
            }

            $result = $output['result'];
            $this->variantService->savePrompt($workflowNumber, $variant, $nodeName, $result, $isNew);

            return response()->json([
                'success' => true,
                'prompt' => $result,
                'system' => $result['system'] ?? null,
                'messages' => $result['messages'] ?? null,
            ]);
        } catch (Exception $e) {
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
     * Load input data for prepare prompt execution
     *
     * @return array with 'success', 'data', and optional 'status_code' keys
     */
    private function loadPreparePromptInput(
        Request $request,
        int $workflowNumber,
        string $variant,
        int $passNumber
    ): array {
        $inputData = $request->input('input');

        if ($inputData === null) {
            $inputFile = storage_path(self::DEBUG_BASE_PATH.'/'.self::INPUT_DIR."/workflow_{$workflowNumber}_input.json");
            if (! file_exists($inputFile)) {
                return [
                    'success' => false,
                    'error' => "Input file not found for workflow_$workflowNumber",
                    'status_code' => 404,
                ];
            }

            $content = json_decode(file_get_contents($inputFile), true);
            if (is_array($content) && isset($content[0]['body'])) {
                $inputData = $content[0];
            } else {
                $inputData = $content;
            }

            if ($inputData === null) {
                return [
                    'success' => false,
                    'error' => 'Invalid input format',
                    'status_code' => 400,
                ];
            }
        } else {
            // Handle array format from request
            if (is_array($inputData) && isset($inputData[0]['body'])) {
                $inputData = $inputData[0];
            }
        }

        // Handle multi-pass scenarios
        if ($passNumber > 1) {
            $result = $this->mergePassInput($workflowNumber, $variant, $passNumber, $inputData);
            if (! $result['success']) {
                return $result;
            }
            $inputData = $result['data'];
        }

        return [
            'success' => true,
            'data' => $inputData,
        ];
    }

    /**
     * Merge pass-specific input or previous pass output into input data
     */
    private function mergePassInput(int $workflowNumber, string $variant, int $passNumber, array $inputData): array
    {
        $passInputFile = storage_path(self::DEBUG_BASE_PATH.'/'.self::INPUT_DIR."/workflow_{$workflowNumber}_input_".($passNumber - 1).'.json');

        if (file_exists($passInputFile)) {
            $passInputContent = json_decode(file_get_contents($passInputFile), true);
            if (is_array($passInputContent)) {
                if (isset($passInputContent[0]) && is_array($passInputContent[0])) {
                    $passInputContent = $passInputContent[0];
                }
                $this->mergePassFields($inputData, $passInputContent);
            }
        } else {
            // Try loading previous pass output (check both old and new versions)
            $previousPassOutput = $this->variantService->loadPassOutput($workflowNumber, $variant, $passNumber - 1,
                true);
            if ($previousPassOutput === null) {
                $previousPassOutput = $this->variantService->loadPassOutput($workflowNumber, $variant, $passNumber - 1,
                    false);
            }

            if ($previousPassOutput !== null) {
                $this->mergePassFields($inputData, $previousPassOutput);
            } else {
                return [
                    'success' => false,
                    'error' => "Pass $passNumber requires either a pass input file (workflow_{$workflowNumber}_input_".($passNumber - 1).'.json) or output from Pass '.($passNumber - 1).'.',
                    'status_code' => 404,
                ];
            }
        }

        return [
            'success' => true,
            'data' => $inputData,
        ];
    }

    /**
     * Merge specific fields from pass input/output into input data
     */
    private function mergePassFields(array &$inputData, array $passData): void
    {
        $fieldsToMerge = ['classification', 'selected_questions', 'cognitive_requirements', 'user_context'];
        foreach ($fieldsToMerge as $field) {
            if (isset($passData[$field])) {
                $inputData[$field] = $passData[$field];
            }
        }
    }

    // ======== INTERNAL HELPERS ========

    /**
     * Ensure debug directory structure exists
     */
    private function ensureDebugDirectory(string $subdirectory = ''): string
    {
        $basePath = storage_path('app/n8n_debug');
        $fullPath = $subdirectory ? "$basePath/$subdirectory" : $basePath;

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
        $frameworkFile = "$referenceDocsPath/framework_taxonomy.md";
        if (file_exists($frameworkFile)) {
            $referenceData['framework_taxonomy'] = [
                'content' => file_get_contents($frameworkFile),
            ];
        }

        // Load personality calibration
        $personalityFile = "$referenceDocsPath/personality_calibration.md";
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
        $questionBankFile = "$referenceDocsPath/question_bank.md";
        if (file_exists($questionBankFile)) {
            $referenceData['question_bank'] = [
                'content' => file_get_contents($questionBankFile),
            ];
        }

        // Load framework templates from directory
        $frameworkTemplatesDir = "$referenceDocsPath/framework_templates";
        if (is_dir($frameworkTemplatesDir)) {
            $templateFiles = glob("$frameworkTemplatesDir/*.md");
            foreach ($templateFiles as $templateFile) {
                $templateName = strtoupper(str_replace('.md', '', basename($templateFile)));
                $referenceData['framework_templates'][$templateName] = file_get_contents($templateFile);
            }
        }

        return $referenceData;
    }

    /**
     * Build a Node.js script to execute the workflow JavaScript
     *
     * @throws Exception
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
            throw new Exception('Failed to encode webhook data: '.json_last_error_msg());
        }

        $referenceDataJson = json_encode($referenceData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($referenceDataJson === false) {
            throw new Exception('Failed to encode reference data: '.json_last_error_msg());
        }

        // Write JavaScript and data to storage directory for debugging
        // Use storage path to avoid temp file cleanup issues
        $debugDir = $this->ensureDebugDirectory();

        $this->tempFilesToCleanup = [];

        $tempJsFile = "$debugDir/workflow_exec_code.js";
        file_put_contents($tempJsFile, $javascript);
        $this->tempFilesToCleanup[] = $tempJsFile;

        // Write data as a JSON file
        $tempDataFile = "$debugDir/workflow_exec_data.json";
        $dataObject = [
            'webhook' => $webhookData,
            'reference' => $referenceData,
            'input' => $inputNodeData,
        ];
        file_put_contents($tempDataFile, json_encode($dataObject, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        $this->tempFilesToCleanup[] = $tempDataFile;

        // Debug: Log what we're passing to Node.js
        Log::info('buildNodeScript: Passing to Node.js', [
            'isMultiPass' => $isMultiPass,
            'inputNodeDataType' => gettype($inputNodeData),
            'inputNodeDataKeys' => array_keys($inputNodeData),
            'hasClassificationInInput' => isset($inputNodeData['classification']) ? 'yes' : 'no',
            'inputNodeDataSnapshot' => [
                'has_body' => isset($inputNodeData['body']),
                'has_headers' => isset($inputNodeData['headers']),
                'has_classification' => isset($inputNodeData['classification']),
                'classification_type' => isset($inputNodeData['classification']) ? gettype($inputNodeData['classification']) : 'N/A',
            ],
            'webhookDataKeys' => array_keys($webhookData),
        ]);

        // Build the Node.js script with proper file paths
        // Properly escape file paths for Node.js string literals
        $dataFileEscaped = json_encode($tempDataFile);
        $jsFileEscaped = json_encode($tempJsFile);

        return "(async () => {
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
const data = JSON.parse(fs.readFileSync($dataFileEscaped, 'utf8'));

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
  const userCode = fs.readFileSync($jsFileEscaped, 'utf8');

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
            $output = shell_exec("timeout 30 node $tempFile 2>&1");

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

            // Strategy 1: Try decoding the entire output
            $result = json_decode($output, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($result) && isset($result['success'])) {
                return $result;
            }

            // Strategy 2: Find the last { ... } JSON block
            $trimmed = rtrim($output);
            if (preg_match('/\{[^{}]*(?:\{[^{}]*}[^{}]*)*}$/', $trimmed, $matches)) {
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
            Log::error('Node.js output parsing failed', [
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
        } catch (Exception $e) {
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

        // If no valid input data from request, try loading from file
        // Check if $inputData is empty, null, or missing required fields
        if (empty($inputData) || (! isset($inputData['task_description']) && ! isset($inputData['original_task_description']) && ! isset($inputData['analysis_data']))) {
            $inputFile = storage_path("app/n8n_debug/input/workflow_{$workflowNumber}_input.json");

            if (! file_exists($inputFile)) {
                return $inputData; // Return what we have from the request, or null
            }

            $content = json_decode(file_get_contents($inputFile), true);

            if (! $content) {
                return $inputData; // Return what we have from the request, or null
            }

            // Extract the body from the stored input
            if (is_array($content) && count($content) > 0 && isset($content[0]['body'])) {
                $inputData = $content[0]['body'];
            } elseif (is_array($content) && isset($content['body'])) {
                $inputData = $content['body'];
            } else {
                return $inputData; // Return what we have from the request, or null
            }
        }

        return $inputData;
    }

    /**
     * Execute workflow and return results using N8nWorkflowClient
     */
    private function executeWorkflowWithService(
        int $workflowNumber,
        ?string $taskDescription,
        ?array $userContext,
        ?array $inputData = null
    ): array {
        $workflowClient = new N8nWorkflowClient;

        return match ($workflowNumber) {
            0 => $workflowClient->executePreAnalysis($taskDescription, $userContext),
            1 => $workflowClient->executeAnalysis(
                $taskDescription,
                $userContext['personality']['type'] ?? null,
                $userContext['personality']['trait_percentages'] ?? null,
                null,
                null,
                $userContext
            ),
            2 => $workflowClient->executeGeneration(
                new GenerationPayload(
                    taskClassification: $inputData['analysis_data']['task_classification'] ?? [],
                    cognitiveRequirements: $inputData['analysis_data']['cognitive_requirements'] ?? [],
                    selectedFramework: $inputData['analysis_data']['selected_framework'] ?? [],
                    personalityTier: $inputData['analysis_data']['personality_tier'] ?? 'full',
                    taskTraitAlignment: $inputData['analysis_data']['task_trait_alignment'] ?? [],
                    originalTaskDescription: $inputData['original_task_description'] ?? $taskDescription ?? '',
                    questionAnswers: $inputData['question_answers'] ?? [],
                    personalityType: $userContext['personality']['type'] ?? null,
                    traitPercentages: $userContext['personality']['trait_percentages'] ?? null,
                    userContext: $userContext,
                    preAnalysisContext: $inputData['pre_analysis_context'] ?? null
                )
            ),
            default => throw new InvalidArgumentException("Workflow $workflowNumber is not supported for direct execution"),
        };
    }

    /**
     * Save workflow output to variant-specific storage
     */
    private function saveWorkflowOutput(
        Request $request,
        int $workflowNumber,
        array $resultData,
        bool $isNew = false
    ): void {
        $variant = $this->getVariant($request, $workflowNumber);
        $this->variantService->saveOutput($workflowNumber, $variant, $resultData, $isNew);
    }

    // ======== WORKFLOW EXECUTION ========

    /**
     * Execute the actual n8n workflow for the old version
     */
    public function executeOldWorkflow(Request $request, $workflowNumber)
    {
        $workflowNumber = (int) $workflowNumber;

        return $this->executeWorkflowInternal($request, $workflowNumber, false);
    }

    /**
     * Execute the actual n8n workflow for the new version
     */
    public function executeNewWorkflow(Request $request, $workflowNumber)
    {
        $workflowNumber = (int) $workflowNumber;

        return $this->executeWorkflowInternal($request, $workflowNumber, true);
    }

    /**
     * Execute n8n workflow with specified version
     */
    private function executeWorkflowInternal(
        Request $request,
        int $workflowNumber,
        bool $isNew
    ): JsonResponse {
        try {
            $inputData = $this->loadInputData($request, $workflowNumber);

            if ($inputData === null) {
                return response()->json([
                    'success' => false,
                    'error' => "Input file not found for workflow_$workflowNumber",
                ], 404);
            }

            $taskDescription = $inputData['task_description'] ?? $inputData['original_task_description'] ?? null;
            $userContext = $inputData['user_context'] ?? null;

            if (! $taskDescription) {
                return response()->json([
                    'success' => false,
                    'error' => 'task_description or original_task_description is required in input data',
                ], 400);
            }

            $resultData = $this->executeWorkflowWithService($workflowNumber, $taskDescription, $userContext,
                $inputData);
            $this->saveWorkflowOutput($request, $workflowNumber, $resultData, $isNew);

            return response()->json([
                'success' => true,
                'output' => $resultData,
                'system' => $resultData['system'] ?? null,
                'messages' => $resultData['messages'] ?? null,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
