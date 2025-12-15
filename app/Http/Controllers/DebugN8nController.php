<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class DebugN8nController extends Controller
{
    /**
     * @var array List of temporary files to clean up after execution
     */
    private array $tempFilesToCleanup = [];

    /**
     * Display the workflow page
     */
    public function show(int $workflowNumber)
    {
        $input = null;
        $javascript = null;
        $javascriptNew = null;
        $output = null;
        $outputNew = null;

        // Load input from storage/app/n8n_debug/input/
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

        // Load JavaScript from storage/app/n8n_debug/prepare_prompt/
        // If not present, extract from the actual n8n workflow file
        $jsFile = storage_path("app/n8n_debug/prepare_prompt/workflow_{$workflowNumber}_prepare_prompt.js");
        if (file_exists($jsFile)) {
            $javascript = file_get_contents($jsFile);
        } else {
            // Extract from n8n workflow and cache it
            $n8nWorkflowFile = base_path("n8n/workflow_{$workflowNumber}.json");
            if (file_exists($n8nWorkflowFile)) {
                $workflow = json_decode(file_get_contents($n8nWorkflowFile), true);
                // Extract the "Prepare Prompt" node JavaScript
                if (isset($workflow['nodes'])) {
                    foreach ($workflow['nodes'] as $node) {
                        if ($node['name'] === 'Prepare Prompt' && isset($node['parameters']['jsCode'])) {
                            $javascript = $node['parameters']['jsCode'];
                            // Cache it for future use
                            $this->ensureDebugDirectory('prepare_prompt');
                            file_put_contents($jsFile, $javascript);
                            break;
                        }
                    }
                }
            }
        }

        // Load new JavaScript from storage/app/n8n_debug/prepare_prompt/new/
        $jsFileNew = storage_path("app/n8n_debug/prepare_prompt/new/workflow_{$workflowNumber}_prepare_prompt.js");
        if (file_exists($jsFileNew)) {
            $javascriptNew = file_get_contents($jsFileNew);
        }

        // Load output from storage/app/n8n_debug/output/
        $outputFile = storage_path("app/n8n_debug/output/workflow_{$workflowNumber}_output.json");
        if (file_exists($outputFile)) {
            $output = json_decode(file_get_contents($outputFile), true);
        }

        // Load new output from storage/app/n8n_debug/output/
        $outputFileNew = storage_path("app/n8n_debug/output/workflow_{$workflowNumber}_output_new.json");
        if (file_exists($outputFileNew)) {
            $outputNew = json_decode(file_get_contents($outputFileNew), true);
        }

        return Inertia::render('Workflow/Show', [
            'workflowNumber' => $workflowNumber,
            'input' => $input,
            'javascript' => $javascript,
            'javascriptNew' => $javascriptNew,
            'output' => $output,
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
            $this->ensureDebugDirectory('prepare_prompt');
            $jsFile = storage_path("app/n8n_debug/prepare_prompt/workflow_{$workflowNumber}_prepare_prompt.js");
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
     * Save JavaScript code
     */
    public function saveJavaScript(Request $request, int $workflowNumber)
    {
        try {
            $request->validate([
                'code' => 'required|string',
            ]);

            $this->ensureDebugDirectory('prepare_prompt');
            $jsFile = storage_path("app/n8n_debug/prepare_prompt/workflow_{$workflowNumber}_prepare_prompt.js");
            file_put_contents($jsFile, $request->input('code'));
            chmod($jsFile, 0644);

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
    public function saveJavaScriptNew(Request $request, int $workflowNumber)
    {
        try {
            $request->validate([
                'code' => 'required|string',
            ]);

            $this->ensureDebugDirectory('prepare_prompt/new');
            $jsFile = storage_path("app/n8n_debug/prepare_prompt/new/workflow_{$workflowNumber}_prepare_prompt.js");
            file_put_contents($jsFile, $request->input('code'));
            chmod($jsFile, 0644);

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
     * Workflow ID mapping (from n8n API)
     */
    private function getWorkflowId(int $workflowNumber): ?string
    {
        $workflowIds = [
            0 => 'YW4AdQE919uLrpLx',
            1 => 'bfMRMEHRhwxbr6V9',
            2 => 'YMvRB5aEeEqeZzO0',
        ];

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
     * Upload workflow to n8n server
     */
    public function uploadWorkflowToN8n(int $workflowNumber)
    {
        try {
            $n8nWorkflowFile = base_path("n8n/workflow_{$workflowNumber}.json");
            if (! file_exists($n8nWorkflowFile)) {
                return response()->json([
                    'success' => false,
                    'error' => "Workflow file not found: workflow_{$workflowNumber}.json",
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
    public function uploadWorkflowNewToN8n(int $workflowNumber)
    {
        try {
            // Load the new JavaScript version
            $jsFile = storage_path("app/n8n_debug/prepare_prompt/new/workflow_{$workflowNumber}_prepare_prompt.js");
            if (! file_exists($jsFile)) {
                return response()->json([
                    'success' => false,
                    'error' => "New JavaScript file not found for workflow_{$workflowNumber}",
                ], 404);
            }

            $newJavaScript = file_get_contents($jsFile);

            // Load the base workflow file
            $n8nWorkflowFile = base_path("n8n/workflow_{$workflowNumber}.json");
            if (! file_exists($n8nWorkflowFile)) {
                return response()->json([
                    'success' => false,
                    'error' => "Workflow file not found: workflow_{$workflowNumber}.json",
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

            // Update the "Prepare Prompt" node with the new JavaScript
            $found = false;
            foreach ($workflow['nodes'] as &$node) {
                if ($node['name'] === 'Prepare Prompt') {
                    $node['parameters']['jsCode'] = $newJavaScript;
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
     * Execute the JavaScript and return the output
     */
    public function executeJavaScript(Request $request, int $workflowNumber)
    {
        try {
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
            }

            // Load JavaScript from storage/app/n8n_debug/prepare_prompt/
            $jsFile = storage_path("app/n8n_debug/prepare_prompt/workflow_{$workflowNumber}_prepare_prompt.js");

            if (! file_exists($jsFile)) {
                return response()->json([
                    'success' => false,
                    'error' => "JavaScript file not found for workflow_{$workflowNumber}",
                ], 404);
            }

            $javascript = file_get_contents($jsFile);

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

            // Save output to storage/app/n8n_debug/output/
            $this->ensureDebugDirectory('output');
            $outputFile = storage_path("app/n8n_debug/output/workflow_{$workflowNumber}_output.json");
            file_put_contents($outputFile, json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return response()->json([
                'success' => true,
                'output' => $result,
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
     * Execute the new JavaScript version and return the output
     */
    public function executeJavaScriptNew(Request $request, int $workflowNumber)
    {
        try {
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
            }

            // Load JavaScript from storage/app/n8n_debug/prepare_prompt/new/
            $jsFile = storage_path("app/n8n_debug/prepare_prompt/new/workflow_{$workflowNumber}_prepare_prompt.js");

            if (! file_exists($jsFile)) {
                return response()->json([
                    'success' => false,
                    'error' => "New JavaScript file not found for workflow_{$workflowNumber}",
                ], 404);
            }

            $javascript = file_get_contents($jsFile);

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
                return response()->json([
                    'success' => false,
                    'error' => $output['error'],
                ], 400);
            }

            $result = $output['result'];

            // Save output to storage/app/n8n_debug/output/
            $this->ensureDebugDirectory('output');
            $outputFile = storage_path("app/n8n_debug/output/workflow_{$workflowNumber}_output_new.json");
            file_put_contents($outputFile, json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return response()->json([
                'success' => true,
                'output' => $result,
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
     * Convert JavaScript to be compatible with eval() execution
     * - Replace const/let with var
     * - Remove return statements
     * - Convert n8n workflow format to match expected output
     * - Fix escaped newlines in n8n code
     */
    private function normalizeJavaScript(string $code): string
    {
        // Fix escaped newlines in n8n workflow code (\\n should be actual newlines)
        // This happens when n8n stores code with literal \\n in strings
        $code = str_replace('\\"', '"', $code);

        // Replace const with var
        $code = preg_replace('/\bconst\s+/', 'var ', $code);

        // Replace let with var
        $code = preg_replace('/\blet\s+/', 'var ', $code);

        // Remove any return statements (they're invalid at the top level in eval())
        $code = preg_replace('/\breturn\s+/m', 'var result = ', $code);

        // n8n workflows use systemPrompt and userMessage, but we need system and messages
        // Add conversion code at the end
        $code .= "\n\n// Convert n8n format to debug format\n";
        $code .= "var system = typeof systemPrompt !== 'undefined' ? systemPrompt : null;\n";
        $code .= "var messages = typeof userMessage !== 'undefined' ? [\n";
        $code .= "  { role: 'user', content: userMessage }\n";
        $code .= "] : null;\n";

        return $code;
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
        ];
        file_put_contents($tempDataFile, json_encode($dataObject, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        $this->tempFilesToCleanup[] = $tempDataFile;

        // Debug: Temp files created successfully

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

// Mock \$input for accessing current node input (workflow 0 style)
const \$input = {
  first() {
    return {
      json: data.webhook || {}
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
     * Execute the actual n8n workflow for the old version
     */
    public function executeWorkflow(Request $request, int $workflowNumber)
    {
        try {
            // Get input data from request
            $inputData = $request->input('input');

            if ($inputData === null) {
                // Fall back to loading from storage
                $inputFile = storage_path("app/n8n_debug/input/workflow_{$workflowNumber}_input.json");

                if (! file_exists($inputFile)) {
                    return response()->json([
                        'success' => false,
                        'error' => "Input file not found for workflow_{$workflowNumber}",
                    ], 404);
                }

                $content = json_decode(file_get_contents($inputFile), true);

                if (! $content) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Failed to parse input file',
                    ], 400);
                }

                // Extract the body from the stored input
                // The stored input file structure is: [{ body: {...}, headers: {...}, ... }]
                // We need to extract just the body field which contains the actual payload
                if (is_array($content) && count($content) > 0 && is_array($content[0]) && isset($content[0]['body'])) {
                    $inputData = $content[0]['body'];
                } else {
                    return response()->json([
                        'success' => false,
                        'error' => 'Invalid input file format - expected array with body field',
                    ], 400);
                }
            }

            // Extract task description and user context from input
            $taskDescription = $inputData['task_description'] ?? null;
            $userContext = $inputData['user_context'] ?? null;

            if (! $taskDescription) {
                return response()->json([
                    'success' => false,
                    'error' => 'task_description is required in input data',
                ], 400);
            }

            // Use PromptFrameworkService to execute the workflow
            // For workflow_0 (pre-analysis), workflow_1 (analysis), or workflow_2 (generation)
            $promptService = new \App\Services\PromptFrameworkService;

            $resultData = match ($workflowNumber) {
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

            // Save output to storage
            $this->ensureDebugDirectory('output');
            $outputFile = storage_path("app/n8n_debug/output/workflow_{$workflowNumber}_workflow_output.json");
            file_put_contents($outputFile, json_encode($resultData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

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
    public function executeWorkflowNew(Request $request, int $workflowNumber)
    {
        try {
            // First, upload the new workflow to n8n to ensure it's up-to-date
            $n8nWorkflowFile = base_path("n8n/workflow_{$workflowNumber}.json");
            if (! file_exists($n8nWorkflowFile)) {
                return response()->json([
                    'success' => false,
                    'error' => "Workflow file not found: workflow_{$workflowNumber}.json",
                ], 404);
            }

            $workflowId = $this->getWorkflowId($workflowNumber);
            if (! $workflowId) {
                return response()->json([
                    'success' => false,
                    'error' => "Unknown workflow number: {$workflowNumber}",
                ], 400);
            }

            // Load the new JavaScript version
            $jsFile = storage_path("app/n8n_debug/prepare_prompt/new/workflow_{$workflowNumber}_prepare_prompt.js");
            if (! file_exists($jsFile)) {
                return response()->json([
                    'success' => false,
                    'error' => "New JavaScript file not found for workflow_{$workflowNumber}",
                ], 404);
            }

            $newJavaScript = file_get_contents($jsFile);

            // Load and update the workflow with the new JavaScript
            $workflow = json_decode(file_get_contents($n8nWorkflowFile), true);
            if (! $workflow) {
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid workflow JSON format',
                ], 400);
            }

            // Update the "Prepare Prompt" node with the new JavaScript
            $found = false;
            foreach ($workflow['nodes'] as &$node) {
                if ($node['name'] === 'Prepare Prompt') {
                    $node['parameters']['jsCode'] = $newJavaScript;
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

            $cleanWorkflow = [
                'name' => $workflow['name'] ?? 'workflow',
                'nodes' => $workflow['nodes'] ?? [],
                'connections' => $workflow['connections'] ?? [],
                'settings' => $workflow['settings'] ?? (object) [],
            ];

            // Upload the workflow to n8n first
            $n8nClient = new \App\Services\N8nClient;
            $uploadResult = $n8nClient->updateWorkflow($workflowId, $cleanWorkflow);

            if (! $uploadResult['success']) {
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to upload new workflow to n8n before execution',
                    'details' => $uploadResult,
                ], 400);
            }

            // Now execute the workflow by triggering its webhook
            // Get input data
            $inputData = $request->input('input');

            if ($inputData === null) {
                // Fall back to loading from storage
                $inputFile = storage_path("app/n8n_debug/input/workflow_{$workflowNumber}_input.json");

                if (! file_exists($inputFile)) {
                    return response()->json([
                        'success' => false,
                        'error' => "Input file not found for workflow_{$workflowNumber}",
                    ], 404);
                }

                $content = json_decode(file_get_contents($inputFile), true);

                if (! $content) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Failed to parse input file',
                    ], 400);
                }

                // Extract the body from the stored input
                // The stored input file structure is: [{ body: {...}, headers: {...}, ... }]
                // We need to extract just the body field which contains the actual payload
                if (is_array($content) && count($content) > 0 && is_array($content[0]) && isset($content[0]['body'])) {
                    $inputData = $content[0]['body'];
                } else {
                    return response()->json([
                        'success' => false,
                        'error' => 'Invalid input file format - expected array with body field',
                    ], 400);
                }
            }

            // Trigger the appropriate workflow webhook based on workflow number
            $webhookPath = match ($workflowNumber) {
                0 => '/webhook/api/n8n/webhook/pre-analysis',
                1 => '/webhook/api/n8n/webhook/analysis',
                2 => '/webhook/api/n8n/webhook/generate',
                default => throw new \InvalidArgumentException("Unknown workflow number: {$workflowNumber}"),
            };

            // The n8n workflow JavaScript expects the input wrapped in a 'body' field
            // because it does: const webhookData = $input.first().json.body || {};
            // So we need to send: { body: { task_description: "...", user_context: {...} } }
            $payload = ['body' => $inputData];

            $webhookResult = $n8nClient->triggerWebhook($webhookPath, $payload);

            if (! $webhookResult['success']) {
                // Log the webhook error but continue (webhook might not be exposed in debug environment)
                \Log::warning('N8n webhook call failed during new workflow execution', [
                    'workflowNumber' => $workflowNumber,
                    'error' => $webhookResult['error'],
                ]);

                // Return mock response for debugging purposes
                $resultData = [
                    'system' => 'Mock system prompt from new version (n8n webhook not available)',
                    'messages' => [
                        ['role' => 'user', 'content' => 'This is a mock response. n8n workflows may not be available in this debug environment.'],
                    ],
                ];
            } else {
                // Get the response data
                $resultData = $webhookResult['data'] ?? [];
            }

            // Save output to storage
            $this->ensureDebugDirectory('output');
            $outputFile = storage_path("app/n8n_debug/output/workflow_{$workflowNumber}_workflow_output_new.json");
            file_put_contents($outputFile, json_encode($resultData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

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
