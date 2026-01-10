<?php

use App\Events\AnalysisCompleted;
use App\Events\PromptOptimizationCompleted;
use App\Events\WorkflowFailed;
use App\Http\Controllers\MailgunWebhookController;
use App\Models\PromptRun;
use App\Models\Visitor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;

Route::post('/n8n/webhook', function (Request $request) {
    try {
        // Verify secret
        $secret = $request->header('X-N8N-SECRET');

        if (! $secret || $secret !== config('services.n8n.webhook_secret')) {
            Log::warning('Invalid N8n webhook secret received', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return response()->json(['error' => __('messages.api.unauthorized')], 403);
        }

        // Validate payload structure
        $validator = Validator::make($request->all(), [
            'prompt_run_id' => 'required|integer|exists:prompt_runs,id',
            'workflow_stage' => 'nullable|string|in:0_processing,0_completed,0_failed,1_processing,1_completed,1_failed,2_processing,2_completed,2_failed',
            'selected_framework' => 'nullable|array',
            'selected_framework.name' => 'required_with:selected_framework|string',
            'selected_framework.code' => 'required_with:selected_framework|string',
            'selected_framework.components' => 'required_with:selected_framework|array',
            'selected_framework.rationale' => 'nullable|string',
            'framework_questions' => 'nullable|array',
            'framework_questions.*' => 'string',
            'optimized_prompt' => 'nullable|string',
            'error_message' => 'nullable|string|max:1000',
            'error_context' => 'nullable|array',
            'error_context.error_type' => 'nullable|string',
            'error_context.failed_node' => 'nullable|string',
            'error_context.execution_id' => 'nullable|string',
            'error_context.timestamp' => 'nullable|string',
            'retry_count' => 'nullable|integer|min:0|max:10',
        ]);

        if ($validator->fails()) {
            Log::error('Invalid N8n webhook payload', [
                'errors' => $validator->errors()->toArray(),
                'payload' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'error' => __('messages.api.invalid_payload'),
                'details' => $validator->errors(),
            ], 422);
        }

        // Log incoming webhook
        Log::info('Processing N8n webhook', [
            'prompt_run_id' => $request->input('prompt_run_id'),
            'workflow_stage' => $request->input('workflow_stage'),
            'status' => $request->input('status'),
        ]);

        // Find prompt run
        $promptRunId = $request->input('prompt_run_id');
        $promptRun = PromptRun::find($promptRunId);

        if (! $promptRun) {
            Log::error('Prompt run not found for N8n webhook', [
                'prompt_run_id' => $promptRunId,
            ]);

            return response()->json([
                'success' => false,
                'error' => __('messages.api.prompt_run_not_found'),
            ], 404);
        }

        // Update prompt run in transaction
        DB::beginTransaction();

        try {
            $promptRun->update($request->only([
                'workflow_stage',
                'selected_framework',
                'framework_questions',
                'optimized_prompt',
                'error_message',
                'error_context',
                'retry_count',
            ]));

            // Track error timestamp for failures
            if ($request->input('workflow_stage') && str_ends_with($request->input('workflow_stage'), '_failed')) {
                $promptRun->update(['last_error_at' => now()]);
            }

            // Mark as completed if finished
            if ($request->input('workflow_stage') === '2_completed') {
                $promptRun->update(['completed_at' => now()]);
            }

            // Broadcast events if needed
            if ($request->input('workflow_stage') === '1_completed') {
                try {
                    event(new AnalysisCompleted($promptRun));
                } catch (\Exception $e) {
                    Log::error('Failed to broadcast AnalysisCompleted event', [
                        'prompt_run_id' => $promptRun->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            if ($request->input('workflow_stage') === '2_completed') {
                try {
                    event(new PromptOptimizationCompleted($promptRun));
                } catch (\Exception $e) {
                    Log::error('Failed to broadcast PromptOptimizationCompleted event', [
                        'prompt_run_id' => $promptRun->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Broadcast failure event if workflow failed
            if ($request->input('workflow_stage') && str_ends_with($request->input('workflow_stage'), '_failed')) {
                try {
                    event(new WorkflowFailed($promptRun));
                } catch (\Exception $e) {
                    Log::error('Failed to broadcast WorkflowFailed event', [
                        'prompt_run_id' => $promptRun->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            DB::commit();

            Log::info('N8n webhook processed successfully', [
                'prompt_run_id' => $promptRun->id,
            ]);

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

    } catch (\Illuminate\Database\QueryException $e) {
        Log::error('Database error processing N8n webhook', [
            'error' => $e->getMessage(),
            'payload' => $request->all(),
        ]);

        return response()->json([
            'success' => false,
            'error' => __('messages.api.database_error'),
        ], 500);

    } catch (\Exception $e) {
        Log::error('Unexpected error processing N8n webhook', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'payload' => $request->all(),
        ]);

        return response()->json([
            'success' => false,
            'error' => __('messages.api.internal_server_error'),
        ], 500);
    }
})->middleware('throttle:60,1');

Route::post('/restore-visitor', function (Request $request) {
    $validator = Validator::make($request->all(), [
        'visitor_id' => ['required', 'uuid', 'exists:visitors,id'],
    ]);

    if ($validator->fails()) {
        return response()->json(['restored' => false], 400);
    }

    $visitor = Visitor::find($request->input('visitor_id'));

    if ($visitor) {
        $cookie = cookie(
            'visitor_id',
            $visitor->id,
            1051200, // 2 years in minutes
            '/',
            null,
            true, // secure
            true, // httpOnly
            false,
            'lax' // sameSite
        );

        return response()->json(['restored' => true])->withCookie($cookie);
    }

    return response()->json(['restored' => false], 404);
})->middleware('throttle:10,1');

// Mailgun webhooks
Route::prefix('webhooks/mailgun')->middleware(['mailgun.signature', 'throttle:60,1'])->group(function () {
    Route::post('/events', [MailgunWebhookController::class, 'handleEvent']);
    Route::post('/inbound', [MailgunWebhookController::class, 'handleInbound']);
});

// Stripe webhooks (handled by Laravel Cashier)
Route::post('/stripe/webhook', [\App\Http\Controllers\StripeWebhookController::class, 'handleWebhook'])
    ->name('cashier.webhook');

// Test-only endpoints for E2E testing
if (config('app.env') === 'e2e') {
    Route::prefix('test')->group(function () {
        Route::post('set-mock-scenario', function (Request $request) {
            $scenario = $request->input('scenario', 'success');
            $scenarioFile = storage_path('app/test_mock_scenario.txt');

            // Ensure storage directory exists
            @mkdir(dirname($scenarioFile), 0755, true);

            // Write scenario to file
            file_put_contents($scenarioFile, $scenario);

            Log::info('Test mock scenario set', ['scenario' => $scenario, 'file' => $scenarioFile]);

            return response()->json(['scenario' => $scenario]);
        });

        Route::post('clear-mock-scenario', function () {
            $scenarioFile = storage_path('app/test_mock_scenario.txt');
            if (file_exists($scenarioFile)) {
                @unlink($scenarioFile);
            }

            Log::info('Test mock scenario cleared');

            return response()->json(['cleared' => true]);
        });

    });
}
