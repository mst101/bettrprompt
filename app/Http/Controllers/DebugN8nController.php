<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class DebugN8nController extends Controller
{
    /**
     * Display the workflow debug page
     */
    public function show(int $workflowNumber)
    {
        $input = null;
        $javascript = null;
        $output = null;

        // Try to load input from n8n directory first, fall back to storage/app/debug
        $n8nInputFile = base_path("n8n/workflow_{$workflowNumber}_input.json");
        $debugInputFile = storage_path("app/debug/workflow_{$workflowNumber}_input.json");

        if (file_exists($n8nInputFile)) {
            $content = json_decode(file_get_contents($n8nInputFile), true);
            // Handle array format from n8n (wrap in body if needed)
            if (is_array($content) && isset($content[0]['body'])) {
                $input = $content[0];
            } else {
                $input = $content;
            }
        } elseif (file_exists($debugInputFile)) {
            $input = json_decode(file_get_contents($debugInputFile), true);
        }

        // Try to load JavaScript from actual n8n workflow file
        $n8nWorkflowFile = base_path("n8n/workflow_{$workflowNumber}_analysis.json");
        if (file_exists($n8nWorkflowFile)) {
            $workflow = json_decode(file_get_contents($n8nWorkflowFile), true);
            // Extract the "Prepare Prompt" node JavaScript
            if (isset($workflow['nodes'])) {
                foreach ($workflow['nodes'] as $node) {
                    if ($node['name'] === 'Prepare Prompt' && isset($node['parameters']['jsCode'])) {
                        $javascript = $node['parameters']['jsCode'];
                        break;
                    }
                }
            }
        }

        // Fall back to storage/app/debug for JavaScript
        $debugJsFile = storage_path("app/debug/workflow_{$workflowNumber}_prepare_prompt.js");
        if (! $javascript && file_exists($debugJsFile)) {
            $javascript = file_get_contents($debugJsFile);
        }

        // Load output from storage
        $outputFile = storage_path("app/debug/workflow_{$workflowNumber}_output.json");
        if (file_exists($outputFile)) {
            $output = json_decode(file_get_contents($outputFile), true);
        }

        return Inertia::render('Debug/WorkflowDebug', [
            'workflow_number' => $workflowNumber,
            'input' => $input,
            'javascript' => $javascript,
            'output' => $output,
        ]);
    }

    /**
     * Save webhook input data
     */
    public function saveInput(Request $request, int $workflowNumber)
    {
        $debugDir = storage_path('app/debug');

        // Create debug directory if it doesn't exist
        if (! is_dir($debugDir)) {
            mkdir($debugDir, 0755, true);
        }

        // Save input as JSON
        $inputFile = "{$debugDir}/workflow_{$workflowNumber}_input.json";
        file_put_contents($inputFile, json_encode($request->all(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return response()->json([
            'success' => true,
            'message' => "Input saved to workflow_{$workflowNumber}_input.json",
            'file' => $inputFile,
        ]);
    }

    /**
     * Save JavaScript code
     */
    public function saveJavaScript(Request $request, int $workflowNumber)
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $debugDir = storage_path('app/debug');

        // Create debug directory if it doesn't exist
        if (! is_dir($debugDir)) {
            mkdir($debugDir, 0755, true);
        }

        // Save JavaScript code
        $jsFile = "{$debugDir}/workflow_{$workflowNumber}_prepare_prompt.js";
        file_put_contents($jsFile, $request->input('code'));

        return response()->json([
            'success' => true,
            'message' => "JavaScript saved to workflow_{$workflowNumber}_prepare_prompt.js",
            'file' => $jsFile,
        ]);
    }

    /**
     * Execute the JavaScript and return the output
     */
    public function executeJavaScript(Request $request, int $workflowNumber)
    {
        // Try to load input from n8n directory first, fall back to storage/app/debug
        $n8nInputFile = base_path("n8n/workflow_{$workflowNumber}_input.json");
        $debugInputFile = storage_path("app/debug/workflow_{$workflowNumber}_input.json");

        $inputData = null;
        if (file_exists($n8nInputFile)) {
            $content = json_decode(file_get_contents($n8nInputFile), true);
            // Handle array format from n8n
            if (is_array($content) && isset($content[0]['body'])) {
                $inputData = $content[0];
            } else {
                $inputData = $content;
            }
        } elseif (file_exists($debugInputFile)) {
            $inputData = json_decode(file_get_contents($debugInputFile), true);
        }

        if ($inputData === null) {
            return response()->json([
                'success' => false,
                'error' => "Input file not found for workflow_{$workflowNumber}",
            ], 404);
        }

        // Try to load JavaScript from storage/app/debug first (user-provided takes priority)
        $javascript = null;
        $debugJsFile = storage_path("app/debug/workflow_{$workflowNumber}_prepare_prompt.js");
        if (file_exists($debugJsFile)) {
            $javascript = file_get_contents($debugJsFile);
        }

        // Fall back to actual n8n workflow file if no debug file exists
        if (! $javascript) {
            $n8nWorkflowFile = base_path("n8n/workflow_{$workflowNumber}_analysis.json");
            if (file_exists($n8nWorkflowFile)) {
                $workflow = json_decode(file_get_contents($n8nWorkflowFile), true);
                if (isset($workflow['nodes'])) {
                    foreach ($workflow['nodes'] as $node) {
                        if ($node['name'] === 'Prepare Prompt' && isset($node['parameters']['jsCode'])) {
                            $javascript = $node['parameters']['jsCode'];
                            break;
                        }
                    }
                }
            }
        }

        if (! $javascript) {
            return response()->json([
                'success' => false,
                'error' => "JavaScript file not found for workflow_{$workflowNumber}",
            ], 404);
        }

        try {
            // Convert JavaScript to use 'var' and remove return statements
            $javascript = $this->normalizeJavaScript($javascript);

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

            // Save output
            $debugDir = storage_path('app/debug');
            $outputFile = "{$debugDir}/workflow_{$workflowNumber}_output.json";
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
            if (isset($this->tempFilesToCleanup) && is_array($this->tempFilesToCleanup)) {
                foreach ($this->tempFilesToCleanup as $file) {
                    if (file_exists($file)) {
                        unlink($file);
                    }
                }
            }
        }
    }

    /**
     * Load reference documents from resources/reference_documents/
     */
    private function loadReferenceDocuments(): array
    {
        $referenceDocsPath = resource_path('reference_documents');

        $referenceData = [
            'framework_taxonomy_doc' => null,
            'personality_calibration_doc' => null,
            'question_bank_doc' => null,
        ];

        // Load framework taxonomy
        $frameworkFile = "{$referenceDocsPath}/framework_taxonomy.md";
        if (file_exists($frameworkFile)) {
            $referenceData['framework_taxonomy_doc'] = [
                'content' => file_get_contents($frameworkFile),
            ];
        }

        // Load personality calibration
        $personalityFile = "{$referenceDocsPath}/personality_calibration.md";
        if (file_exists($personalityFile)) {
            $referenceData['personality_calibration_doc'] = [
                'content' => file_get_contents($personalityFile),
            ];
        }

        // Load question bank
        $questionBankFile = "{$referenceDocsPath}/question_bank.md";
        if (file_exists($questionBankFile)) {
            $referenceData['question_bank_doc'] = [
                'content' => file_get_contents($questionBankFile),
            ];
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
        $debugDir = storage_path('app/debug');
        if (! is_dir($debugDir)) {
            mkdir($debugDir, 0755, true);
        }

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
        // Using concatenation instead of string replacement to avoid escaping issues
        $nodeScript = "// Mock n8n environment
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
const data = JSON.parse(fs.readFileSync(".json_encode($tempDataFile).", 'utf8'));

// Store node data
\$._nodeData = {
  'Webhook Trigger': data.webhook,
  'Load Reference Documents': data.reference
};

// Execute the workflow code
try {
  // Load and execute the user's code from file
  const userCode = fs.readFileSync(".json_encode($tempJsFile).", 'utf8');
  eval(userCode);

  // Capture the results from global scope (don't use 'result' as that's created by the return conversion)
  const systemValue = typeof system !== 'undefined' ? system : null;
  const messagesValue = typeof messages !== 'undefined' ? messages : null;

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
}";

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

            $output = shell_exec("node {$tempFile} 2>&1");

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
}
