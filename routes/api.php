<?php

use App\Events\AnalysisCompleted;
use App\Events\PromptOptimizationCompleted;
use App\Events\WorkflowFailed;
use App\Http\Controllers\Admin\AlertNotificationController;
use App\Http\Controllers\Admin\DomainAnalyticsController;
use App\Http\Controllers\Admin\ExperimentResultsController;
use App\Http\Controllers\Api\AnalyticsEventController;
use App\Http\Controllers\Api\PromptRatingController;
use App\Http\Controllers\Api\QuestionRatingController;
use App\Http\Controllers\Api\UserPreferenceController;
use App\Http\Controllers\MailgunWebhookController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\Test\AnalyticsTestController;
use App\Jobs\SendAlertEmail;
use App\Models\PromptRun;
use App\Services\AlertService;
use App\Services\FrameworkSelectionService;
use App\Services\QuestionAnalyticsService;
use App\Services\WorkflowAnalyticsService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;

// Analytics events ingestion (non-blocking, all users including guests)
Route::post('/analytics/events', [AnalyticsEventController::class, 'store'])
    ->middleware('throttle:100,1') // Generous limit for event batches
    ->name('analytics.events.store');

// Guest + authenticated routes
Route::post('/prompt-runs/{promptRun}/questions/{questionId}/rate', [QuestionRatingController::class, 'store'])
    ->name('api.questions.rate');

// Prompt rating
Route::post('/prompt-runs/{promptRun}/rate', [PromptRatingController::class, 'store'])
    ->middleware('auth:sanctum')
    ->name('api.prompt-runs.rate');

// User preferences (works for both authenticated users and guest visitors)
Route::patch('/user/preferences', [UserPreferenceController::class, 'update'])
    ->name('api.user.preferences.update');

// Experiment results (admin only)
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin/experiments/{experiment}/results', [ExperimentResultsController::class, 'show'])
        ->name('api.admin.experiments.results');
});

Route::post('/n8n/webhook', function (Request $request) {
    try {
        // Note: This webhook only handles Workflows 1 (analysis) and 2 (generation).
        // Workflow 0 (pre-analysis) is handled synchronously by ProcessPreAnalysis job
        // because it's fast and the user is already waiting for pre-analysis questions.
        // See ProcessPreAnalysis.php for the synchronous implementation.

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
            'pre_analysis_api_usage' => 'nullable|array',
            'pre_analysis_api_usage.input_tokens' => 'nullable|integer',
            'pre_analysis_api_usage.output_tokens' => 'nullable|integer',
            'pre_analysis_api_usage.cost' => 'nullable|numeric',
            'pre_analysis_api_usage.model' => 'nullable|string',
            'analysis_api_usage' => 'nullable|array',
            'analysis_api_usage.input_tokens' => 'nullable|integer',
            'analysis_api_usage.output_tokens' => 'nullable|integer',
            'analysis_api_usage.cost' => 'nullable|numeric',
            'analysis_api_usage.model' => 'nullable|string',
            'generation_api_usage' => 'nullable|array',
            'generation_api_usage.input_tokens' => 'nullable|integer',
            'generation_api_usage.output_tokens' => 'nullable|integer',
            'generation_api_usage.cost' => 'nullable|numeric',
            'generation_api_usage.model' => 'nullable|string',
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

        // Cache request values
        $promptRunId = $request->input('prompt_run_id');
        $workflowStage = $request->input('workflow_stage');

        Log::info('Processing N8n webhook', [
            'prompt_run_id' => $promptRunId,
            'workflow_stage' => $workflowStage,
        ]);

        // Find prompt run
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
            // Batch all updates together
            $updateData = $request->only([
                'workflow_stage',
                'selected_framework',
                'framework_questions',
                'optimized_prompt',
                'error_message',
                'error_context',
                'retry_count',
                'pre_analysis_api_usage',
                'analysis_api_usage',
                'generation_api_usage',
            ]);

            // Add timestamps based on workflow stage
            if ($workflowStage === '2_completed') {
                $updateData['completed_at'] = now();
            } elseif ($workflowStage && str_ends_with($workflowStage, '_failed')) {
                $updateData['last_error_at'] = now();
            }

            $promptRun->update($updateData);

            // Refresh the model to ensure in-memory attributes match the database
            $promptRun->refresh();

            // Record analytics for workflow completion/failure
            $workflowAnalyticsService = app(WorkflowAnalyticsService::class);
            $frameworkSelectionService = app(FrameworkSelectionService::class);

            // Broadcast events: 1_completed, 2_completed, _failed
            if ($workflowStage === '1_completed') {
                try {
                    // Record workflow 1 completion
                    $stage = 1;
                    $analytic = $promptRun->workflowAnalytics()
                        ->where('workflow_stage', $stage)
                        ->where('status', 'processing')
                        ->latest()
                        ->first();

                    if ($analytic) {
                        $apiUsage = $promptRun->analysis_api_usage ?? [];
                        $workflowAnalyticsService->recordCompletion(
                            analytic: $analytic,
                            inputTokens: $apiUsage['input_tokens'] ?? null,
                            outputTokens: $apiUsage['output_tokens'] ?? null,
                            estimatedCostUsd: $apiUsage['cost'] ?? null,
                            modelUsed: $apiUsage['model'] ?? null,
                        );
                    }

                    // Record framework selection when analysis completes
                    if ($promptRun->selected_framework) {
                        $frameworkSelectionService->recordSelection(
                            promptRun: $promptRun,
                            visitorId: $promptRun->visitor_id,
                            userId: $promptRun->user_id,
                            recommendedFramework: $promptRun->selected_framework['code'],
                            chosenFramework: $promptRun->selected_framework['code'],
                            recommendationScores: $promptRun->alternative_frameworks ?? [],
                            taskCategory: $promptRun->task_classification['primary_category'] ?? null,
                            personalityType: $promptRun->personality_type,
                        );
                    }

                    // Record question presentations when analysis completes
                    if (! empty($promptRun->framework_questions)) {
                        $questionService = app(QuestionAnalyticsService::class);
                        foreach ($promptRun->framework_questions as $index => $question) {
                            $questionService->recordPresentation(
                                promptRun: $promptRun,
                                visitorId: $promptRun->visitor_id,
                                userId: $promptRun->user_id,
                                questionId: $question['id'] ?? "Q$index",
                                questionCategory: $question['category'] ?? 'framework',
                                personalityVariant: $question['personality_variant'] ?? null,
                                displayOrder: $index + 1,
                                wasRequired: $question['required'] ?? true,
                            );
                        }
                    }

                    event(new AnalysisCompleted($promptRun));
                } catch (Exception $e) {
                    Log::error('Failed to process analysis completion', [
                        'prompt_run_id' => $promptRun->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            } elseif ($workflowStage === '2_completed') {
                try {
                    // Record workflow 2 completion
                    $stage = 2;
                    $analytic = $promptRun->workflowAnalytics()
                        ->where('workflow_stage', $stage)
                        ->where('status', 'processing')
                        ->latest()
                        ->first();

                    if ($analytic) {
                        $apiUsage = $promptRun->generation_api_usage ?? [];
                        $workflowAnalyticsService->recordCompletion(
                            analytic: $analytic,
                            inputTokens: $apiUsage['input_tokens'] ?? null,
                            outputTokens: $apiUsage['output_tokens'] ?? null,
                            estimatedCostUsd: $apiUsage['cost'] ?? null,
                            modelUsed: $apiUsage['model'] ?? null,
                        );
                    }

                    event(new PromptOptimizationCompleted($promptRun));
                } catch (Exception $e) {
                    Log::error('Failed to process prompt optimization completion', [
                        'prompt_run_id' => $promptRun->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            } elseif ($workflowStage && str_ends_with($workflowStage, '_failed')) {
                try {
                    // Record workflow failure
                    $stage = (int) substr($workflowStage, 0, 1);
                    $analytic = $promptRun->workflowAnalytics()
                        ->where('workflow_stage', $stage)
                        ->where('status', 'processing')
                        ->latest()
                        ->first();

                    if ($analytic) {
                        $workflowAnalyticsService->recordFailure(
                            analytic: $analytic,
                            errorCode: $request->input('error_context.error_type') ?? 'UNKNOWN_ERROR',
                            errorMessage: $request->input('error_message') ?? 'Unknown error occurred',
                        );
                    }

                    event(new WorkflowFailed($promptRun));

                    // Trigger workflow failure alert
                    $alertService = app(AlertService::class);
                    $alertHistory = $alertService->triggerWorkflowAlert(
                        workflowStage: $stage,
                        status: 'failed',
                        errorCode: $request->input('error_message') ?? 'WORKFLOW_FAILED',
                        errorMessage: $request->input('error_message'),
                    );

                    // Dispatch email notifications
                    if ($alertHistory) {
                        foreach ($alertHistory->notifications->where('type', 'email') as $notification) {
                            SendAlertEmail::dispatch($notification);
                        }
                    }
                } catch (Exception $e) {
                    Log::error('Failed to process workflow failure', [
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

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }

    } catch (QueryException $e) {
        Log::error('Database error processing N8n webhook', [
            'error' => $e->getMessage(),
            'payload' => $request->all(),
        ]);

        return response()->json([
            'success' => false,
            'error' => __('messages.api.database_error'),
        ], 500);

    } catch (Exception $e) {
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

// Mailgun webhooks
Route::prefix('webhooks/mailgun')->middleware(['mailgun.signature', 'throttle:60,1'])->group(function () {
    Route::post('/events', [MailgunWebhookController::class, 'handleEvent']);
    Route::post('/inbound', [MailgunWebhookController::class, 'handleInbound']);
});

// Stripe webhooks (handled by Laravel Cashier)
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handleWebhook'])
    ->name('stripe.webhook');

// Domain analytics API (admin only)
Route::middleware(['auth', 'admin'])->group(function () {
    Route::prefix('admin/domain-analytics')->group(function () {
        Route::get('/frameworks', [DomainAnalyticsController::class, 'getFrameworkAnalytics'])
            ->name('api.admin.domain-analytics.frameworks');
        Route::get('/questions', [DomainAnalyticsController::class, 'getQuestionAnalytics'])
            ->name('api.admin.domain-analytics.questions');
        Route::get('/workflows', [DomainAnalyticsController::class, 'getWorkflowAnalytics'])
            ->name('api.admin.domain-analytics.workflows');
        Route::get('/funnels', [DomainAnalyticsController::class, 'getFunnelAnalytics'])
            ->name('api.admin.domain-analytics.funnels');
    });

    // Alert notifications API
    Route::prefix('admin/alert-notifications')->group(function () {
        Route::get('/pending', [AlertNotificationController::class, 'getPending'])
            ->name('api.admin.alert-notifications.pending');
        Route::post('/{notificationId}/acknowledge', [AlertNotificationController::class, 'acknowledge'])
            ->name('api.admin.alert-notifications.acknowledge');
    });

    // Alerts API
    Route::prefix('admin/alerts')->group(function () {
        Route::get('/', [AlertNotificationController::class, 'getAlerts'])
            ->name('api.admin.alerts.index');
        Route::post('/{alertId}/acknowledge', [AlertNotificationController::class, 'acknowledgeAlert'])
            ->name('api.admin.alerts.acknowledge');
    });
});

// Test-only endpoints for E2E testing
if (config('app.env') === 'e2e') {
    Route::prefix('test')->middleware(function ($request, $next) {
        // Security: Verify X-Test-Auth header
        if ($request->header('X-Test-Auth') !== 'playwright-e2e-tests') {
            abort(403, 'Unauthorized test endpoint access');
        }

        return $next($request);
    })->group(function () {
        // Analytics & Rating Data Access
        Route::get('/question-analytics/{promptRunId}', [AnalyticsTestController::class, 'getQuestionAnalytics']);
        Route::get('/analytics-events', [AnalyticsTestController::class, 'getAnalyticsEvents']);

        // Visitor & Prompt Run Creation
        Route::post('/create-visitor-prompt-run', [AnalyticsTestController::class, 'createVisitorPromptRun']);
        Route::post('/create-visitor-with-completed-prompt', [AnalyticsTestController::class, 'createVisitorWithCompletedPrompt']);
        Route::post('/create-visitor-with-completed-prompt-for-edit', [AnalyticsTestController::class, 'createVisitorWithCompletedPromptForEdit']);
        Route::post('/create-visitor-prompt-run-2-completed', [AnalyticsTestController::class, 'createVisitorPromptRun2Completed']);

        // Mock Scenario Management
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
