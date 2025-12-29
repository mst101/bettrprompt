<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReferenceDocumentsController extends Controller
{
    /**
     * Show the workflow index page
     */
    public function workflowIndex()
    {
        return Inertia::render('Workflow/Index');
    }

    /**
     * Show the reference documents management page
     */
    public function index()
    {
        $documents = $this->getDocumentsList();

        return Inertia::render('Workflow/Docs', [
            'documents' => $documents,
        ]);
    }

    /**
     * Get list of all documents with metadata
     */
    public function list()
    {
        return response()->json([
            'success' => true,
            'documents' => $this->getDocumentsList(),
        ]);
    }

    /**
     * Get content of a specific document
     */
    public function show($type, $filename)
    {
        try {
            $path = $this->getDocumentPath($type, $filename);

            if (! file_exists($path)) {
                return response()->json([
                    'success' => false,
                    'error' => "Document not found: $filename",
                ], 404);
            }

            $content = file_get_contents($path);

            return response()->json([
                'success' => true,
                'type' => $type,
                'filename' => $filename,
                'content' => $content,
                'usedIn' => $this->getWorkflowsUsingDocument($filename),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Save document changes and embed into workflows
     */
    public function update(Request $request, $type, $filename)
    {
        try {
            $request->validate([
                'content' => 'required|string',
            ]);

            $path = $this->getDocumentPath($type, $filename);

            // Ensure directory exists
            $directory = dirname($path);
            if (! is_dir($directory)) {
                mkdir($directory, 0755, true);
            }

            // Save the Markdown file
            file_put_contents($path, $request->input('content'));
            chmod($path, 0644);

            // Embed into relevant workflows
            $this->embedDocumentIntoWorkflows($type, $filename, $request->input('content'));

            return response()->json([
                'success' => true,
                'message' => "Document '$filename' saved successfully and embedded into workflows",
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get the file path for a document
     *
     * @throws Exception
     */
    private function getDocumentPath($type, $filename)
    {
        $baseDir = resource_path('reference_documents');

        if ($type === 'core') {
            return "$baseDir/$filename";
        } elseif ($type === 'framework') {
            return "$baseDir/framework_templates/$filename";
        }

        throw new Exception("Invalid document type: $type");
    }

    /**
     * Get list of all documents with metadata
     */
    private function getDocumentsList()
    {
        $baseDir = resource_path('reference_documents');
        $documents = [
            'core' => [],
            'framework' => [],
        ];

        // Core documents
        $coreFiles = [
            'framework_taxonomy.md',
            'personality_calibration.md',
            'question_bank.md',
        ];

        foreach ($coreFiles as $file) {
            $path = "$baseDir/$file";
            if (file_exists($path)) {
                $documents['core'][] = [
                    'type' => 'core',
                    'filename' => $file,
                    'displayName' => str_replace('.md', '', str_replace('_', ' ', ucfirst($file))),
                    'size' => filesize($path),
                    'lastModified' => filemtime($path),
                    'usedIn' => $this->getWorkflowsUsingDocument($file),
                ];
            }
        }

        // Framework template files
        $frameworkDir = "$baseDir/framework_templates";
        if (is_dir($frameworkDir)) {
            $files = glob("$frameworkDir/*.md");
            foreach ($files as $path) {
                $file = basename($path);
                $documents['framework'][] = [
                    'type' => 'framework',
                    'filename' => $file,
                    'displayName' => str_replace('.md', '', str_replace('_', ' ', $file)),
                    'size' => filesize($path),
                    'lastModified' => filemtime($path),
                    'usedIn' => $this->getWorkflowsUsingDocument($file),
                ];
            }
        }

        // Sort framework templates alphabetically
        usort($documents['framework'], function ($a, $b) {
            return strcmp($a['filename'], $b['filename']);
        });

        return $documents;
    }

    /**
     * Determine which workflows use a given document
     */
    private function getWorkflowsUsingDocument($filename)
    {
        str_replace('.md', '', $filename);
        $usedIn = [];

        // Framework templates are used in workflow_2
        if (str_contains(resource_path('reference_documents/framework_templates'), $filename) ||
            is_file(resource_path("reference_documents/framework_templates/$filename"))) {
            $usedIn[] = 'workflow_2';
        }

        // Core documents
        if ($filename === 'framework_taxonomy.md') {
            $usedIn[] = 'workflow_1';
        } elseif ($filename === 'personality_calibration.md') {
            $usedIn[] = 'workflow_1';
            $usedIn[] = 'workflow_2';
        } elseif ($filename === 'question_bank.md') {
            $usedIn[] = 'workflow_1';
        }

        return array_unique($usedIn);
    }

    /**
     * Embed a document into the relevant workflow JSON files
     *
     * @throws Exception
     */
    private function embedDocumentIntoWorkflows($type, $filename, $content)
    {
        // Determine which workflows to update
        $workflows = [];

        if ($type === 'core') {
            if ($filename === 'framework_taxonomy.md') {
                $workflows[1] = ['framework_taxonomy' => $content];
            } elseif ($filename === 'personality_calibration.md') {
                $workflows[1] = ['personality_calibration' => $content];
                $workflows[2] = ['personality_calibration_full' => $content];
            } elseif ($filename === 'question_bank.md') {
                $workflows[1] = ['question_bank' => $content];
            }
        } elseif ($type === 'framework') {
            // Framework templates go into workflow_2's framework_templates
            // This will be handled specially in updateFrameworkTemplates()
            $workflows[2] = ['framework_templates' => true]; // Marker that we need to update framework_templates
        }

        // Update the workflow JSON files
        foreach ($workflows as $workflowNumber => $updates) {
            $this->updateWorkflowJSON($workflowNumber, $updates, $type, $filename);
        }
    }

    /**
     * Update the "Load Reference Documents" node in a workflow JSON file
     *
     * @throws Exception
     */
    private function updateWorkflowJSON($workflowNumber, $updates, $type, $filename)
    {
        $workflowFile = base_path("n8n/workflow_$workflowNumber.json");

        if (! file_exists($workflowFile)) {
            throw new Exception("Workflow file not found: workflow_$workflowNumber.json");
        }

        $workflow = json_decode(file_get_contents($workflowFile), true);

        if (! isset($workflow['nodes'])) {
            throw new Exception('Invalid workflow format: nodes array not found');
        }

        // Find the "Load Reference Documents" node
        $found = false;
        foreach ($workflow['nodes'] as &$node) {
            if ($node['name'] === 'Load Reference Documents' && isset($node['parameters']['assignments'])) {
                // Update assignments
                $assignments = &$node['parameters']['assignments']['assignments'];

                foreach ($updates as $key => $content) {
                    if ($type === 'framework' && $key === 'framework_templates') {
                        // Special handling for framework templates
                        $this->updateFrameworkTemplates($assignments, $filename, $content);
                    } else {
                        // Regular core documents
                        $this->updateAssignment($assignments, $key, $content);
                    }
                }

                $found = true;
                break;
            }
        }

        if (! $found) {
            throw new Exception("Load Reference Documents node not found in workflow_$workflowNumber");
        }

        // Write the updated workflow back to file
        file_put_contents(
            $workflowFile,
            json_encode($workflow, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }

    /**
     * Update a single assignment in the workflow node
     */
    private function updateAssignment(&$assignments, $key, $content)
    {
        // Find or create the assignment
        $found = false;
        foreach ($assignments as &$assignment) {
            if ($assignment['id'] === $key && $assignment['name'] === $key) {
                $assignment['value'] = json_encode(['content' => $content]);
                $found = true;
                break;
            }
        }

        if (! $found) {
            // Create new assignment
            $assignments[] = [
                'id' => $key,
                'name' => $key,
                'value' => json_encode(['content' => $content]),
                'type' => 'object',
            ];
        }
    }

    /**
     * Update framework templates in the workflow node
     */
    private function updateFrameworkTemplates(&$assignments, $filename, $content)
    {
        // Find the framework_templates assignment
        $found = false;
        foreach ($assignments as &$assignment) {
            if ($assignment['id'] === 'framework_templates' && $assignment['name'] === 'framework_templates') {
                // Decode existing templates
                $templates = json_decode($assignment['value'], true) ?? [];

                // Update or add this template
                $templateName = str_replace('.md', '', $filename);
                $templates[$templateName] = $content;

                // Re-encode and save
                $assignment['value'] = json_encode($templates);
                $found = true;
                break;
            }
        }

        if (! $found) {
            // Create new framework_templates assignment with all templates
            $templates = $this->getAllFrameworkTemplates();
            $templateName = str_replace('.md', '', $filename);
            $templates[$templateName] = $content;

            $assignments[] = [
                'id' => 'framework_templates',
                'name' => 'framework_templates',
                'value' => json_encode($templates),
                'type' => 'object',
            ];
        }
    }

    /**
     * Get all framework templates as a key-value array
     */
    private function getAllFrameworkTemplates()
    {
        $templates = [];
        $frameworkDir = resource_path('reference_documents/framework_templates');

        if (is_dir($frameworkDir)) {
            $files = glob("$frameworkDir/*.md");
            foreach ($files as $path) {
                $filename = basename($path);
                $templateName = str_replace('.md', '', $filename);
                $templates[$templateName] = file_get_contents($path);
            }
        }

        return $templates;
    }
}
