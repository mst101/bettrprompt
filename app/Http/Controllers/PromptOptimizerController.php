<?php

namespace App\Http\Controllers;

use App\Http\Requests\AnswerQuestionRequest;
use App\Http\Requests\StorePromptRunRequest;
use App\Http\Resources\PromptRunResource;
use App\Models\PromptRun;
use App\Services\N8nClient;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PromptOptimizerController extends Controller
{
    public function __construct(
        protected N8nClient $n8nClient
    ) {
    }

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

        // Create the prompt run record using personality from user profile
        $promptRun = PromptRun::create([
            'user_id' => $user->id,
            'personality_type' => $user->personality_type,
            'trait_percentages' => $user->trait_percentages ?? null,
            'task_description' => $validated['task_description'],
            'status' => 'processing',
            'workflow_stage' => 'submitted',
        ]);

        // Prepare payload for framework selector
        $payload = [
            'prompt_run_id' => $promptRun->id,
            'personality_type' => $user->personality_type,
            'trait_percentages' => $user->trait_percentages ?? null,
            'task_description' => $validated['task_description'],
        ];

        try {
            // Store the request payload
            $promptRun->update([
                'n8n_request_payload' => $payload,
            ]);

            // Trigger framework selector workflow
            $response = $this->n8nClient->triggerWebhook(
                '/webhook/framework-selector',
                $payload
            );

            if ($response->successful()) {
                $responseData = $response->json();

                // Log the response for debugging
                \Log::info('Framework Selector Response', [
                    'prompt_run_id' => $promptRun->id,
                    'response' => $responseData,
                ]);

                // Update the prompt run with framework selection
                $promptRun->update([
                    'selected_framework' => $responseData['selected_framework'] ?? null,
                    'framework_reasoning' => $responseData['framework_reasoning'] ?? null,
                    'framework_questions' => $responseData['framework_questions'] ?? [],
                    'clarifying_answers' => [],
                    'workflow_stage' => 'framework_selected',
                    'n8n_response_payload' => $responseData,
                ]);

                // Refresh the model to ensure we have latest data
                $promptRun->refresh();

                // Log what was saved
                \Log::info('Saved Framework Selection', [
                    'prompt_run_id' => $promptRun->id,
                    'selected_framework' => $promptRun->selected_framework,
                    'questions_count' => count($promptRun->framework_questions ?? []),
                    'framework_questions' => $promptRun->framework_questions,
                ]);

                // Broadcast framework selected event
                event(new \App\Events\FrameworkSelected($promptRun));

                // Redirect to show page where questions will be displayed
                return redirect()
                    ->route('prompt-optimizer.show', $promptRun)
                    ->with('success', 'Framework selected! Please answer the following questions.');
            } else {
                // Handle n8n error - parse the error response
                $errorData = $response->json();
                $errorMessage = 'Framework selector workflow failed';

                // Extract detailed error message if available
                if (is_array($errorData) && isset($errorData[0])) {
                    $error = $errorData[0];
                    if (isset($error['error'])) {
                        $errorMessage = $error['error'];
                    }
                    // Store the full error details
                    $promptRun->update([
                        'status' => 'failed',
                        'workflow_stage' => 'failed',
                        'error_message' => $errorMessage,
                        'n8n_response_payload' => $error,
                    ]);
                } else {
                    $promptRun->update([
                        'status' => 'failed',
                        'workflow_stage' => 'failed',
                        'error_message' => $errorMessage.': '.$response->body(),
                    ]);
                }

                return back()->with('error', 'Failed to select framework. Please try again.');
            }
        } catch (\Throwable $e) {
            // Handle exception
            $promptRun->update([
                'status' => 'failed',
                'workflow_stage' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);

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

        // Get current answers and append new one
        $answers = $promptRun->clarifying_answers ?? [];
        $answers[] = $validated['answer'];

        // Log the answer submission
        \Log::info('Submitting Answer', [
            'prompt_run_id' => $promptRun->id,
            'current_answers_count' => count($promptRun->clarifying_answers ?? []),
            'new_answer' => $validated['answer'],
            'total_after' => count($answers),
        ]);

        // Update the prompt run
        $promptRun->update([
            'clarifying_answers' => $answers,
            'workflow_stage' => 'answering_questions',
        ]);

        // Refresh and log what was saved
        $promptRun->refresh();
        \Log::info('Saved Answer', [
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

        // Get current answers and append null for skipped question
        $answers = $promptRun->clarifying_answers ?? [];
        $answers[] = null;

        // Update the prompt run
        $promptRun->update([
            'clarifying_answers' => $answers,
            'workflow_stage' => 'answering_questions',
        ]);

        // Check if all questions have been processed
        if ($promptRun->hasAnsweredAllQuestions()) {
            // Trigger final prompt optimization
            return $this->triggerFinalOptimization($promptRun);
        }

        // More questions to process - redirect back to show page
        return redirect()
            ->route('prompt-optimizer.show', $promptRun)
            ->with('success', 'Question skipped. Next question:');
    }

    /**
     * Trigger the final prompt optimization workflow
     */
    protected function triggerFinalOptimization(PromptRun $promptRun)
    {
        // Update workflow stage
        $promptRun->update([
            'workflow_stage' => 'generating_prompt',
            'status' => 'processing',
        ]);

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

        try {
            // Trigger final prompt optimizer workflow
            $response = $this->n8nClient->triggerWebhook(
                '/webhook/final-prompt-optimizer',
                $payload
            );

            if ($response->successful()) {
                $responseData = $response->json();

                // Update the prompt run with the final optimized prompt
                $promptRun->update([
                    'optimized_prompt' => $responseData['optimized_prompt'] ?? null,
                    'workflow_stage' => 'completed',
                    'status' => 'completed',
                    'completed_at' => now(),
                ]);

                // Broadcast completion event
                event(new \App\Events\PromptOptimizationCompleted($promptRun));

                return redirect()
                    ->route('prompt-optimizer.show', $promptRun)
                    ->with('success', 'Your optimised prompt is ready!');
            } else {
                // Handle n8n error - parse the error response
                $errorData = $response->json();
                $errorMessage = 'Final optimization workflow failed';

                // Extract detailed error message if available
                if (is_array($errorData) && isset($errorData[0])) {
                    $error = $errorData[0];
                    if (isset($error['error'])) {
                        $errorMessage = $error['error'];
                    }
                    // Store the full error details
                    $promptRun->update([
                        'status' => 'failed',
                        'workflow_stage' => 'failed',
                        'error_message' => $errorMessage,
                        'n8n_response_payload' => $error,
                        'completed_at' => now(),
                    ]);
                } else {
                    $promptRun->update([
                        'status' => 'failed',
                        'workflow_stage' => 'failed',
                        'error_message' => $errorMessage.': '.$response->body(),
                        'completed_at' => now(),
                    ]);
                }

                return redirect()
                    ->route('prompt-optimizer.show', $promptRun)
                    ->with('error', 'Failed to generate optimised prompt. Please try again.');
            }
        } catch (\Throwable $e) {
            // Handle exception
            $promptRun->update([
                'status' => 'failed',
                'workflow_stage' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);

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
            // Framework selection failed - retry from the beginning
            $promptRun->update([
                'status' => 'processing',
                'workflow_stage' => 'submitted',
                'error_message' => null,
                'completed_at' => null,
            ]);

            // Prepare payload for framework selector
            $payload = [
                'prompt_run_id' => $promptRun->id,
                'personality_type' => $promptRun->personality_type,
                'trait_percentages' => $promptRun->trait_percentages,
                'task_description' => $promptRun->task_description,
            ];

            try {
                // Trigger framework selector workflow
                $response = $this->n8nClient->triggerWebhook(
                    '/webhook/framework-selector',
                    $payload
                );

                if ($response->successful()) {
                    $responseData = $response->json();

                    // Update the prompt run with framework selection
                    $promptRun->update([
                        'selected_framework' => $responseData['selected_framework'] ?? null,
                        'framework_reasoning' => $responseData['framework_reasoning'] ?? null,
                        'framework_questions' => $responseData['framework_questions'] ?? [],
                        'workflow_stage' => 'framework_selected',
                        'n8n_response_payload' => $responseData,
                    ]);

                    // Broadcast framework selected event
                    event(new \App\Events\FrameworkSelected($promptRun));

                    return redirect()
                        ->route('prompt-optimizer.show', $promptRun)
                        ->with('success', 'Framework selected! Please answer the following questions.');
                } else {
                    // Handle n8n error
                    $errorData = $response->json();
                    $errorMessage = 'Framework selector workflow failed';

                    if (is_array($errorData) && isset($errorData[0])) {
                        $error = $errorData[0];
                        if (isset($error['error'])) {
                            $errorMessage = $error['error'];
                        }
                        $promptRun->update([
                            'status' => 'failed',
                            'workflow_stage' => 'failed',
                            'error_message' => $errorMessage,
                            'n8n_response_payload' => $error,
                        ]);
                    } else {
                        $promptRun->update([
                            'status' => 'failed',
                            'workflow_stage' => 'failed',
                            'error_message' => $errorMessage.': '.$response->body(),
                        ]);
                    }

                    return redirect()
                        ->route('prompt-optimizer.show', $promptRun)
                        ->with('error', 'Retry failed. '.$errorMessage);
                }
            } catch (\Throwable $e) {
                $promptRun->update([
                    'status' => 'failed',
                    'workflow_stage' => 'failed',
                    'error_message' => $e->getMessage(),
                ]);

                return redirect()
                    ->route('prompt-optimizer.show', $promptRun)
                    ->with('error', 'An error occurred whilst retrying.');
            }
        } elseif ($workflowStage === 'generating_prompt') {
            // Final optimization failed - retry that step
            return $this->triggerFinalOptimization($promptRun);
        }

        return back()->with('error', 'Cannot retry from this stage.');
    }

    /**
     * Display history of prompt runs
     */
    public function history()
    {
        $promptRuns = PromptRun::where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->paginate(6);

        return Inertia::render('PromptOptimizer/History', [
            'promptRuns' => inertiaPaginated($promptRuns, PromptRunResource::class),
        ]);
    }
}
