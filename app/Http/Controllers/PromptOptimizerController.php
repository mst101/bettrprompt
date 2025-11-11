<?php

namespace App\Http\Controllers;

use App\Http\Requests\AnswerQuestionRequest;
use App\Http\Requests\StorePromptRunRequest;
use App\Http\Resources\PromptRunResource;
use App\Models\PromptRun;
use App\Services\DatabaseService;
use App\Services\N8nClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class PromptOptimizerController extends Controller
{
    public function __construct(
        protected N8nClient $n8nClient
    ) {}

    /**
     * Display the prompt optimizer form
     */
    public function index()
    {
        return Inertia::render('PromptOptimizer/Index');
    }

    /**
     * Store a new prompt optimization request
     */
    public function store(StorePromptRunRequest $request)
    {
        $validated = $request->validated();
        $user = auth()->user();

        try {
            // Create the prompt run record using personality from user profile
            $promptRun = DatabaseService::retryOnDeadlock(function () use ($user, $validated) {
                return PromptRun::create([
                    'user_id' => $user->id,
                    'personality_type' => $user->personality_type,
                    'trait_percentages' => $user->trait_percentages ?? null,
                    'task_description' => $validated['task_description'],
                    'status' => 'processing',
                    'workflow_stage' => 'submitted',
                ]);
            });

            // Prepare payload for framework selector
            $payload = [
                'prompt_run_id' => $promptRun->id,
                'personality_type' => $user->personality_type,
                'trait_percentages' => $user->trait_percentages ?? null,
                'task_description' => $validated['task_description'],
            ];

            // Store the request payload
            DatabaseService::retryOnDeadlock(function () use ($promptRun, $payload) {
                $promptRun->update([
                    'n8n_request_payload' => $payload,
                ]);
            });

            // Trigger framework selector workflow
            $response = $this->n8nClient->triggerWebhook(
                '/webhook/framework-selector',
                $payload
            );

            if ($response['success']) {
                $responseData = $response['data'];

                // Log the response for debugging
                Log::info('Framework Selector Response', [
                    'prompt_run_id' => $promptRun->id,
                    'response' => $responseData,
                ]);

                // Update the prompt run with framework selection
                DatabaseService::retryOnDeadlock(function () use ($promptRun, $responseData) {
                    $promptRun->update([
                        'selected_framework' => $responseData['selected_framework'] ?? null,
                        'framework_reasoning' => $responseData['framework_reasoning'] ?? null,
                        'framework_questions' => $responseData['framework_questions'] ?? [],
                        'clarifying_answers' => [],
                        'workflow_stage' => 'framework_selected',
                        'n8n_response_payload' => $responseData,
                    ]);
                });

                // Refresh the model to ensure we have latest data
                $promptRun->refresh();

                // Log what was saved
                Log::info('Saved Framework Selection', [
                    'prompt_run_id' => $promptRun->id,
                    'selected_framework' => $promptRun->selected_framework,
                    'questions_count' => count($promptRun->framework_questions ?? []),
                    'framework_questions' => $promptRun->framework_questions,
                ]);

                // Broadcast framework selected event
                try {
                    event(new \App\Events\FrameworkSelected($promptRun));
                } catch (\Exception $e) {
                    Log::error('Failed to broadcast FrameworkSelected event', [
                        'prompt_run_id' => $promptRun->id,
                        'error' => $e->getMessage(),
                    ]);
                }

                // Redirect to show page where questions will be displayed
                return redirect()
                    ->route('prompt-optimizer.show', $promptRun)
                    ->with('success', 'Framework selected! Please answer the following questions.');
            } else {
                // Handle n8n error
                $errorMessage = $response['error'] ?? 'Framework selector workflow failed';
                $errorPayload = $response['payload'] ?? null;

                Log::error('Framework selector workflow failed', [
                    'prompt_run_id' => $promptRun->id,
                    'error' => $errorMessage,
                    'payload' => $errorPayload,
                ]);

                // Store the error details
                DatabaseService::retryOnDeadlock(function () use ($promptRun, $errorMessage, $errorPayload) {
                    $promptRun->update([
                        'status' => 'failed',
                        'workflow_stage' => 'failed',
                        'error_message' => $errorMessage,
                        'n8n_response_payload' => $errorPayload,
                    ]);
                });

                return back()->with('error', 'Failed to select framework. Please try again.');
            }
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Database error in prompt run creation', [
                'user_id' => $user->id,
                'task_description' => $validated['task_description'],
                'error' => $e->getMessage(),
            ]);

            // Attempt to mark as failed (only if promptRun was created)
            if (isset($promptRun)) {
                try {
                    DatabaseService::retryOnDeadlock(function () use ($promptRun) {
                        $promptRun->update([
                            'status' => 'failed',
                            'workflow_stage' => 'failed',
                            'error_message' => 'Database error occurred',
                            'completed_at' => now(),
                        ]);
                    });
                } catch (\Exception $updateError) {
                    Log::error('Failed to mark prompt run as failed', [
                        'prompt_run_id' => $promptRun->id,
                        'error' => $updateError->getMessage(),
                    ]);
                }
            }

            return back()->with('error', 'A database error occurred. Please try again.');

        } catch (\Throwable $e) {
            Log::error('Unexpected error in prompt run creation', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Attempt to mark as failed (only if promptRun was created)
            if (isset($promptRun)) {
                try {
                    DatabaseService::retryOnDeadlock(function () use ($promptRun, $e) {
                        $promptRun->update([
                            'status' => 'failed',
                            'workflow_stage' => 'failed',
                            'error_message' => $e->getMessage(),
                            'completed_at' => now(),
                        ]);
                    });
                } catch (\Exception $updateError) {
                    Log::error('Failed to mark prompt run as failed', [
                        'prompt_run_id' => $promptRun->id ?? 'unknown',
                        'error' => $updateError->getMessage(),
                    ]);
                }
            }

            return back()->with('error', 'An error occurred whilst processing your request.');
        }
    }

    /**
     * Display the optimised prompt result
     */
    public function show(PromptRun $promptRun)
    {
        // Authorise that the user can view this prompt run
        if ($promptRun->user_id !== auth()->id()) {
            abort(403);
        }

        // Load parent and children relationships
        $promptRun->load(['parent', 'children']);

        //        dd(PromptRunResource::make($promptRun)->resolve());
        return Inertia::render('PromptOptimizer/Show', [
            'promptRun' => PromptRunResource::make($promptRun)->resolve(),
            'currentQuestion' => $promptRun->getCurrentQuestion(),
            'progress' => [
                'answered' => $promptRun->getAnsweredQuestionsCount(),
                'total' => $promptRun->getTotalQuestionsCount(),
            ],
        ]);
    }

    /**
     * Submit an answer to a clarifying question
     */
    public function answerQuestion(AnswerQuestionRequest $request, PromptRun $promptRun)
    {
        // Validate that we're in the correct workflow stage
        if ($promptRun->workflow_stage !== 'framework_selected' && $promptRun->workflow_stage !== 'answering_questions') {
            return back()->with('error', 'Cannot answer questions at this stage.');
        }

        $validated = $request->validated();

        try {
            // Get current answers and append new one
            $answers = $promptRun->clarifying_answers ?? [];
            $answers[] = $validated['answer'];

            // Log the answer submission
            Log::info('Submitting Answer', [
                'prompt_run_id' => $promptRun->id,
                'current_answers_count' => count($promptRun->clarifying_answers ?? []),
                'new_answer' => $validated['answer'],
                'total_after' => count($answers),
            ]);

            // Update the prompt run with retry logic
            DatabaseService::retryOnDeadlock(function () use ($promptRun, $answers) {
                $promptRun->update([
                    'clarifying_answers' => $answers,
                    'workflow_stage' => 'answering_questions',
                ]);
            });

            // Refresh and log what was saved
            $promptRun->refresh();
            Log::info('Saved Answer', [
                'prompt_run_id' => $promptRun->id,
                'saved_answers_count' => count($promptRun->clarifying_answers ?? []),
                'all_answers' => $promptRun->clarifying_answers,
            ]);

            // Check if all questions have been answered
            if ($promptRun->hasAnsweredAllQuestions()) {
                // Trigger final prompt optimization
                return $this->triggerFinalOptimization($promptRun);
            }

            // More questions to answer - redirect back to show page
            return redirect()
                ->route('prompt-optimizer.show', $promptRun)
                ->with('success', 'Answer saved. Next question:');

        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Database error saving answer', [
                'prompt_run_id' => $promptRun->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to save answer. Please try again.');

        } catch (\Throwable $e) {
            Log::error('Unexpected error saving answer', [
                'prompt_run_id' => $promptRun->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'An error occurred. Please try again.');
        }
    }

    /**
     * Skip a clarifying question
     */
    public function skipQuestion(PromptRun $promptRun)
    {
        // Authorise that the user can update this prompt run
        if ($promptRun->user_id !== auth()->id()) {
            abort(403);
        }

        // Validate that we're in the correct workflow stage
        if ($promptRun->workflow_stage !== 'framework_selected' && $promptRun->workflow_stage !== 'answering_questions') {
            return back()->with('error', 'Cannot skip questions at this stage.');
        }

        try {
            // Get current answers and append null for skipped question
            $answers = $promptRun->clarifying_answers ?? [];
            $answers[] = null;

            Log::info('Skipping question', [
                'prompt_run_id' => $promptRun->id,
                'question_number' => count($answers),
            ]);

            // Update the prompt run with retry logic
            DatabaseService::retryOnDeadlock(function () use ($promptRun, $answers) {
                $promptRun->update([
                    'clarifying_answers' => $answers,
                    'workflow_stage' => 'answering_questions',
                ]);
            });

            // Refresh the model
            $promptRun->refresh();

            // Check if all questions have been processed
            if ($promptRun->hasAnsweredAllQuestions()) {
                // Trigger final prompt optimization
                return $this->triggerFinalOptimization($promptRun);
            }

            // More questions to process - redirect back to show page
            return redirect()
                ->route('prompt-optimizer.show', $promptRun)
                ->with('success', 'Question skipped. Next question:');

        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Database error skipping question', [
                'prompt_run_id' => $promptRun->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to skip question. Please try again.');

        } catch (\Throwable $e) {
            Log::error('Unexpected error skipping question', [
                'prompt_run_id' => $promptRun->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'An error occurred. Please try again.');
        }
    }

    /**
     * Trigger the final prompt optimization workflow
     */
    protected function triggerFinalOptimization(PromptRun $promptRun)
    {
        try {
            // Update workflow stage with retry logic
            DatabaseService::retryOnDeadlock(function () use ($promptRun) {
                $promptRun->update([
                    'workflow_stage' => 'generating_prompt',
                    'status' => 'processing',
                ]);
            });

            // Prepare payload for final optimization
            $payload = [
                'prompt_run_id' => $promptRun->id,
                'personality_type' => $promptRun->personality_type,
                'trait_percentages' => $promptRun->trait_percentages,
                'task_description' => $promptRun->task_description,
                'selected_framework' => $promptRun->selected_framework,
                'framework_reasoning' => $promptRun->framework_reasoning,
                'framework_questions' => $promptRun->framework_questions,
                'clarifying_answers' => $promptRun->clarifying_answers,
            ];

            Log::info('Triggering final prompt optimization', [
                'prompt_run_id' => $promptRun->id,
                'selected_framework' => $promptRun->selected_framework,
            ]);

            // Trigger final prompt optimizer workflow
            $response = $this->n8nClient->triggerWebhook(
                '/webhook/final-prompt-optimizer',
                $payload
            );

            if ($response['success']) {
                $responseData = $response['data'];

                Log::info('Final prompt optimization completed', [
                    'prompt_run_id' => $promptRun->id,
                ]);

                // Update the prompt run with the final optimized prompt
                DatabaseService::retryOnDeadlock(function () use ($promptRun, $responseData) {
                    $promptRun->update([
                        'optimized_prompt' => $responseData['optimized_prompt'] ?? null,
                        'workflow_stage' => 'completed',
                        'status' => 'completed',
                        'completed_at' => now(),
                    ]);
                });

                // Broadcast completion event
                try {
                    event(new \App\Events\PromptOptimizationCompleted($promptRun));
                } catch (\Exception $e) {
                    Log::error('Failed to broadcast PromptOptimizationCompleted event', [
                        'prompt_run_id' => $promptRun->id,
                        'error' => $e->getMessage(),
                    ]);
                }

                return redirect()
                    ->route('prompt-optimizer.show', $promptRun)
                    ->with('success', 'Your optimised prompt is ready!');
            } else {
                // Handle n8n error
                $errorMessage = $response['error'] ?? 'Final optimization workflow failed';
                $errorPayload = $response['payload'] ?? null;

                Log::error('Final prompt optimization failed', [
                    'prompt_run_id' => $promptRun->id,
                    'error' => $errorMessage,
                    'payload' => $errorPayload,
                ]);

                // Store the error details
                DatabaseService::retryOnDeadlock(function () use ($promptRun, $errorMessage, $errorPayload) {
                    $promptRun->update([
                        'status' => 'failed',
                        'workflow_stage' => 'failed',
                        'error_message' => $errorMessage,
                        'n8n_response_payload' => $errorPayload,
                        'completed_at' => now(),
                    ]);
                });

                return redirect()
                    ->route('prompt-optimizer.show', $promptRun)
                    ->with('error', 'Failed to generate optimised prompt. Please try again.');
            }
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Database error in final prompt optimization', [
                'prompt_run_id' => $promptRun->id,
                'error' => $e->getMessage(),
            ]);

            // Attempt to mark as failed
            try {
                DatabaseService::retryOnDeadlock(function () use ($promptRun) {
                    $promptRun->update([
                        'status' => 'failed',
                        'workflow_stage' => 'failed',
                        'error_message' => 'Database error occurred',
                        'completed_at' => now(),
                    ]);
                });
            } catch (\Exception $updateError) {
                Log::error('Failed to mark prompt run as failed', [
                    'prompt_run_id' => $promptRun->id,
                    'error' => $updateError->getMessage(),
                ]);
            }

            return redirect()
                ->route('prompt-optimizer.show', $promptRun)
                ->with('error', 'A database error occurred. Please try again.');

        } catch (\Throwable $e) {
            Log::error('Unexpected error in final prompt optimization', [
                'prompt_run_id' => $promptRun->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Attempt to mark as failed
            try {
                DatabaseService::retryOnDeadlock(function () use ($promptRun, $e) {
                    $promptRun->update([
                        'status' => 'failed',
                        'workflow_stage' => 'failed',
                        'error_message' => $e->getMessage(),
                        'completed_at' => now(),
                    ]);
                });
            } catch (\Exception $updateError) {
                Log::error('Failed to mark prompt run as failed', [
                    'prompt_run_id' => $promptRun->id,
                    'error' => $updateError->getMessage(),
                ]);
            }

            return redirect()
                ->route('prompt-optimizer.show', $promptRun)
                ->with('error', 'An error occurred whilst generating your prompt.');
        }
    }

    /**
     * Retry a failed prompt run
     */
    public function retry(PromptRun $promptRun)
    {
        // Authorise that the user can retry this prompt run
        if ($promptRun->user_id !== auth()->id()) {
            abort(403);
        }

        // Only allow retry for failed runs
        if ($promptRun->status !== 'failed') {
            return back()->with('error', 'Only failed runs can be retried.');
        }

        // Determine which stage failed and retry from there
        $workflowStage = $promptRun->workflow_stage;

        if ($workflowStage === 'failed' || $workflowStage === 'submitted') {
            try {
                Log::info('Retrying framework selection', [
                    'prompt_run_id' => $promptRun->id,
                ]);

                // Framework selection failed - retry from the beginning
                DatabaseService::retryOnDeadlock(function () use ($promptRun) {
                    $promptRun->update([
                        'status' => 'processing',
                        'workflow_stage' => 'submitted',
                        'error_message' => null,
                        'completed_at' => null,
                    ]);
                });

                // Prepare payload for framework selector
                $payload = [
                    'prompt_run_id' => $promptRun->id,
                    'personality_type' => $promptRun->personality_type,
                    'trait_percentages' => $promptRun->trait_percentages,
                    'task_description' => $promptRun->task_description,
                ];

                // Trigger framework selector workflow
                $response = $this->n8nClient->triggerWebhook(
                    '/webhook/framework-selector',
                    $payload
                );

                if ($response['success']) {
                    $responseData = $response['data'];

                    // Update the prompt run with framework selection
                    DatabaseService::retryOnDeadlock(function () use ($promptRun, $responseData) {
                        $promptRun->update([
                            'selected_framework' => $responseData['selected_framework'] ?? null,
                            'framework_reasoning' => $responseData['framework_reasoning'] ?? null,
                            'framework_questions' => $responseData['framework_questions'] ?? [],
                            'workflow_stage' => 'framework_selected',
                            'n8n_response_payload' => $responseData,
                        ]);
                    });

                    // Broadcast framework selected event
                    try {
                        event(new \App\Events\FrameworkSelected($promptRun));
                    } catch (\Exception $e) {
                        Log::error('Failed to broadcast FrameworkSelected event on retry', [
                            'prompt_run_id' => $promptRun->id,
                            'error' => $e->getMessage(),
                        ]);
                    }

                    return redirect()
                        ->route('prompt-optimizer.show', $promptRun)
                        ->with('success', 'Framework selected! Please answer the following questions.');
                } else {
                    // Handle n8n error
                    $errorMessage = $response['error'] ?? 'Framework selector workflow failed';
                    $errorPayload = $response['payload'] ?? null;

                    Log::error('Framework selector retry failed', [
                        'prompt_run_id' => $promptRun->id,
                        'error' => $errorMessage,
                    ]);

                    DatabaseService::retryOnDeadlock(function () use ($promptRun, $errorMessage, $errorPayload) {
                        $promptRun->update([
                            'status' => 'failed',
                            'workflow_stage' => 'failed',
                            'error_message' => $errorMessage,
                            'n8n_response_payload' => $errorPayload,
                        ]);
                    });

                    return redirect()
                        ->route('prompt-optimizer.show', $promptRun)
                        ->with('error', 'Retry failed. '.$errorMessage);
                }
            } catch (\Illuminate\Database\QueryException $e) {
                Log::error('Database error during retry', [
                    'prompt_run_id' => $promptRun->id,
                    'error' => $e->getMessage(),
                ]);

                // Attempt to mark as failed
                try {
                    DatabaseService::retryOnDeadlock(function () use ($promptRun) {
                        $promptRun->update([
                            'status' => 'failed',
                            'workflow_stage' => 'failed',
                            'error_message' => 'Database error occurred during retry',
                        ]);
                    });
                } catch (\Exception $updateError) {
                    Log::error('Failed to mark prompt run as failed during retry', [
                        'prompt_run_id' => $promptRun->id,
                        'error' => $updateError->getMessage(),
                    ]);
                }

                return redirect()
                    ->route('prompt-optimizer.show', $promptRun)
                    ->with('error', 'A database error occurred whilst retrying.');

            } catch (\Throwable $e) {
                Log::error('Unexpected error during retry', [
                    'prompt_run_id' => $promptRun->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                // Attempt to mark as failed
                try {
                    DatabaseService::retryOnDeadlock(function () use ($promptRun, $e) {
                        $promptRun->update([
                            'status' => 'failed',
                            'workflow_stage' => 'failed',
                            'error_message' => $e->getMessage(),
                        ]);
                    });
                } catch (\Exception $updateError) {
                    Log::error('Failed to mark prompt run as failed during retry', [
                        'prompt_run_id' => $promptRun->id,
                        'error' => $updateError->getMessage(),
                    ]);
                }

                return redirect()
                    ->route('prompt-optimizer.show', $promptRun)
                    ->with('error', 'An error occurred whilst retrying.');
            }
        } elseif ($workflowStage === 'generating_prompt') {
            // Final optimization failed - retry that step
            Log::info('Retrying final optimization', [
                'prompt_run_id' => $promptRun->id,
            ]);

            return $this->triggerFinalOptimization($promptRun);
        }

        return back()->with('error', 'Cannot retry from this stage.');
    }

    /**
     * Display history of prompt runs
     */
    public function history(Request $request)
    {
        // Get sorting parameters
        $sortBy = $request->query('sort_by', 'created_at');
        $sortDirection = $request->query('sort_direction', 'desc');

        // Get per-page parameter (default 10, allowed: 1-100)
        $perPage = $request->query('per_page', 10);
        $perPage = is_numeric($perPage) ? (int) $perPage : 10;
        $perPage = max(1, min(100, $perPage)); // Clamp between 1 and 100

        // Validate sort column
        $allowedSortColumns = ['created_at', 'personality_type', 'status', 'task_description', 'selected_framework'];
        if (! in_array($sortBy, $allowedSortColumns)) {
            $sortBy = 'created_at';
        }

        // Validate sort direction
        if (! in_array($sortDirection, ['asc', 'desc'])) {
            $sortDirection = 'desc';
        }

        $promptRuns = PromptRun::where('user_id', auth()->id())
            ->orderBy($sortBy, $sortDirection)
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('PromptOptimizer/History', [
            'promptRuns' => inertiaPaginated($promptRuns, PromptRunResource::class),
            'filters' => [
                'sort_by' => $sortBy,
                'sort_direction' => $sortDirection,
                'per_page' => $perPage,
            ],
        ]);
    }

    /**
     * Create a child prompt run with a new task description
     */
    public function createChild(Request $request, PromptRun $parentPromptRun)
    {
        // Authorise that the user can create a child of this prompt run
        if ($parentPromptRun->user_id !== auth()->id()) {
            abort(403);
        }

        $validated = $request->validate([
            'task_description' => 'required|string|max:5000',
        ]);

        $user = auth()->user();

        try {
            // Create the child prompt run record
            $childPromptRun = DatabaseService::retryOnDeadlock(function () use ($user, $parentPromptRun, $validated) {
                return PromptRun::create([
                    'user_id' => $user->id,
                    'parent_id' => $parentPromptRun->id,
                    'personality_type' => $user->personality_type,
                    'trait_percentages' => $user->trait_percentages ?? null,
                    'task_description' => $validated['task_description'],
                    'status' => 'processing',
                    'workflow_stage' => 'submitted',
                ]);
            });

            // Prepare payload for framework selector
            $payload = [
                'prompt_run_id' => $childPromptRun->id,
                'personality_type' => $user->personality_type,
                'trait_percentages' => $user->trait_percentages ?? null,
                'task_description' => $validated['task_description'],
            ];

            // Store the request payload
            DatabaseService::retryOnDeadlock(function () use ($childPromptRun, $payload) {
                $childPromptRun->update([
                    'n8n_request_payload' => $payload,
                ]);
            });

            // Trigger framework selector workflow
            $response = $this->n8nClient->triggerWebhook(
                '/webhook/framework-selector',
                $payload
            );

            if ($response['success']) {
                $responseData = $response['data'];

                // Update the prompt run with framework selection
                DatabaseService::retryOnDeadlock(function () use ($childPromptRun, $responseData) {
                    $childPromptRun->update([
                        'selected_framework' => $responseData['selected_framework'] ?? null,
                        'framework_reasoning' => $responseData['framework_reasoning'] ?? null,
                        'framework_questions' => $responseData['framework_questions'] ?? [],
                        'clarifying_answers' => [],
                        'workflow_stage' => 'framework_selected',
                        'n8n_response_payload' => $responseData,
                    ]);
                });

                // Refresh the model to ensure we have latest data
                $childPromptRun->refresh();

                return redirect()
                    ->route('prompt-optimizer.show', ['promptRun' => $childPromptRun->id])
                    ->with('success', 'New prompt optimisation created successfully.');
            } else {
                // Handle error response
                DatabaseService::retryOnDeadlock(function () use ($childPromptRun, $response) {
                    $childPromptRun->update([
                        'status' => 'failed',
                        'error_message' => $response['message'] ?? 'Framework selection failed',
                        'n8n_response_payload' => $response['data'] ?? null,
                    ]);
                });

                return back()
                    ->with('error', 'Failed to select framework. Please try again.');
            }
        } catch (\Exception $e) {
            Log::error('Failed to create child prompt run', [
                'parent_prompt_run_id' => $parentPromptRun->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()
                ->with('error', 'An error occurred whilst creating the new prompt optimisation. Please try again.');
        }
    }

    /**
     * Create a child prompt run from edited clarifying answers
     */
    public function createChildFromAnswers(Request $request, PromptRun $parentPromptRun)
    {
        // Authorise that the user can create a child of this prompt run
        if ($parentPromptRun->user_id !== auth()->id()) {
            abort(403);
        }

        // Validate that parent has framework questions
        if (! $parentPromptRun->framework_questions || empty($parentPromptRun->framework_questions)) {
            return back()->with('error', 'Parent prompt run does not have framework questions.');
        }

        $validated = $request->validate([
            'clarifying_answers' => 'required|array',
            'clarifying_answers.*' => 'nullable|string|max:5000',
        ]);

        // Convert empty strings to null for consistency
        $clarifyingAnswers = array_map(
            fn ($answer) => ($answer === '' || $answer === null) ? null : $answer,
            $validated['clarifying_answers']
        );

        $user = auth()->user();

        try {
            // Create the child prompt run record with same framework but new answers
            $childPromptRun = DatabaseService::retryOnDeadlock(function () use ($user, $parentPromptRun, $clarifyingAnswers) {
                return PromptRun::create([
                    'user_id' => $user->id,
                    'parent_id' => $parentPromptRun->id,
                    'personality_type' => $user->personality_type,
                    'trait_percentages' => $user->trait_percentages ?? null,
                    'task_description' => $parentPromptRun->task_description,
                    'selected_framework' => $parentPromptRun->selected_framework,
                    'framework_reasoning' => $parentPromptRun->framework_reasoning,
                    'framework_questions' => $parentPromptRun->framework_questions,
                    'clarifying_answers' => $clarifyingAnswers,
                    'status' => 'processing',
                    'workflow_stage' => 'generating_prompt',
                ]);
            });

            Log::info('Created child prompt run from edited answers', [
                'parent_id' => $parentPromptRun->id,
                'child_id' => $childPromptRun->id,
            ]);

            // Trigger final optimization directly with edited answers
            $payload = [
                'prompt_run_id' => $childPromptRun->id,
                'personality_type' => $user->personality_type,
                'trait_percentages' => $user->trait_percentages ?? null,
                'task_description' => $parentPromptRun->task_description,
                'selected_framework' => $parentPromptRun->selected_framework,
                'framework_reasoning' => $parentPromptRun->framework_reasoning,
                'framework_questions' => $parentPromptRun->framework_questions,
                'clarifying_answers' => $clarifyingAnswers,
            ];

            // Store the request payload
            DatabaseService::retryOnDeadlock(function () use ($childPromptRun, $payload) {
                $childPromptRun->update([
                    'n8n_request_payload' => $payload,
                ]);
            });

            // Trigger final prompt optimizer workflow
            $response = $this->n8nClient->triggerWebhook(
                '/webhook/final-prompt-optimizer',
                $payload
            );

            if ($response['success']) {
                $responseData = $response['data'];

                // Update the prompt run with the optimized prompt
                DatabaseService::retryOnDeadlock(function () use ($childPromptRun, $responseData) {
                    $childPromptRun->update([
                        'optimized_prompt' => $responseData['optimized_prompt'] ?? null,
                        'workflow_stage' => 'completed',
                        'status' => 'completed',
                        'completed_at' => now(),
                        'n8n_response_payload' => $responseData,
                    ]);
                });

                // Refresh the model
                $childPromptRun->refresh();

                return redirect()
                    ->route('prompt-optimizer.show', ['promptRun' => $childPromptRun->id])
                    ->with('success', 'New prompt optimisation created with edited answers.');
            } else {
                // Handle error response
                DatabaseService::retryOnDeadlock(function () use ($childPromptRun, $response) {
                    $childPromptRun->update([
                        'status' => 'failed',
                        'workflow_stage' => 'failed',
                        'error_message' => $response['message'] ?? 'Final optimization failed',
                        'n8n_response_payload' => $response['data'] ?? null,
                    ]);
                });

                return back()
                    ->with('error', 'Failed to generate optimised prompt. Please try again.');
            }
        } catch (\Exception $e) {
            Log::error('Failed to create child from edited answers', [
                'parent_prompt_run_id' => $parentPromptRun->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()
                ->with('error', 'An error occurred whilst creating the new prompt optimisation. Please try again.');
        }
    }

    /**
     * Update the optimised prompt text
     */
    public function updateOptimizedPrompt(Request $request, PromptRun $promptRun)
    {
        // Authorise that the user can update this prompt run
        if ($promptRun->user_id !== auth()->id()) {
            abort(403);
        }

        // Validate that the prompt run is completed
        if ($promptRun->workflow_stage !== 'completed') {
            return back()->with('error', 'Can only edit completed prompt runs.');
        }

        $validated = $request->validate([
            'optimized_prompt' => 'required|string|max:50000',
        ]);

        try {
            DatabaseService::retryOnDeadlock(function () use ($promptRun, $validated) {
                $promptRun->update([
                    'optimized_prompt' => $validated['optimized_prompt'],
                ]);
            });

            Log::info('Updated optimised prompt', [
                'prompt_run_id' => $promptRun->id,
            ]);

            return back()->with('success', 'Prompt updated successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to update optimised prompt', [
                'prompt_run_id' => $promptRun->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to update prompt. Please try again.');
        }
    }
}
