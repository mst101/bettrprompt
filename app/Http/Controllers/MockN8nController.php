<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Mock n8n webhook endpoints for E2E testing
 * These endpoints simulate n8n responses without requiring n8n to be running
 */
class MockN8nController extends Controller
{
    /**
     * Mock pre-analysis webhook (workflow_0_pre_analysis)
     * Returns mock response indicating no clarification needed
     */
    public function preAnalysis(Request $request): JsonResponse
    {
        $taskDescription = $request->input('task_description', '');

        // For E2E tests, always skip pre-analysis questions
        // This allows tests to proceed directly to the main workflow
        return response()->json([
            'success' => true,
            'data' => [
                'needs_clarification' => false,
                'reasoning' => 'Mock: Proceeding directly to analysis for E2E testing.',
            ],
        ]);
    }

    /**
     * Mock analysis webhook (workflow_1)
     * Returns mock framework selection and questions
     */
    public function analyse(Request $request): JsonResponse
    {
        // This would be called by the ProcessAnalysis job
        // For now, we don't mock this as the job runs asynchronously
        // and E2E tests check for loading states, not completion

        return response()->json([
            'success' => true,
            'message' => 'Mock: Analysis queued for processing',
        ]);
    }

    /**
     * Mock prompt optimisation webhook (workflow_2)
     * Returns mock optimised prompt
     */
    public function optimisePrompt(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Mock: Prompt optimisation queued for processing',
        ]);
    }
}
