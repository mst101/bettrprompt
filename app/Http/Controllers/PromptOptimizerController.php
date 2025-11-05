<?php

namespace App\Http\Controllers;

use App\Models\PromptRun;
use App\Services\N8nClient;
use Illuminate\Http\Request;
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
        $personalityTypes = [
            'INTJ-A' => 'Architect (Assertive)',
            'INTJ-T' => 'Architect (Turbulent)',
            'INTP-A' => 'Logician (Assertive)',
            'INTP-T' => 'Logician (Turbulent)',
            'ENTJ-A' => 'Commander (Assertive)',
            'ENTJ-T' => 'Commander (Turbulent)',
            'ENTP-A' => 'Debater (Assertive)',
            'ENTP-T' => 'Debater (Turbulent)',
            'INFJ-A' => 'Advocate (Assertive)',
            'INFJ-T' => 'Advocate (Turbulent)',
            'INFP-A' => 'Mediator (Assertive)',
            'INFP-T' => 'Mediator (Turbulent)',
            'ENFJ-A' => 'Protagonist (Assertive)',
            'ENFJ-T' => 'Protagonist (Turbulent)',
            'ENFP-A' => 'Campaigner (Assertive)',
            'ENFP-T' => 'Campaigner (Turbulent)',
            'ISTJ-A' => 'Logistician (Assertive)',
            'ISTJ-T' => 'Logistician (Turbulent)',
            'ISFJ-A' => 'Defender (Assertive)',
            'ISFJ-T' => 'Defender (Turbulent)',
            'ESTJ-A' => 'Executive (Assertive)',
            'ESTJ-T' => 'Executive (Turbulent)',
            'ESFJ-A' => 'Consul (Assertive)',
            'ESFJ-T' => 'Consul (Turbulent)',
            'ISTP-A' => 'Virtuoso (Assertive)',
            'ISTP-T' => 'Virtuoso (Turbulent)',
            'ISFP-A' => 'Adventurer (Assertive)',
            'ISFP-T' => 'Adventurer (Turbulent)',
            'ESTP-A' => 'Entrepreneur (Assertive)',
            'ESTP-T' => 'Entrepreneur (Turbulent)',
            'ESFP-A' => 'Entertainer (Assertive)',
            'ESFP-T' => 'Entertainer (Turbulent)',
        ];

        return Inertia::render('PromptOptimizer/Index', [
            'personalityTypes' => $personalityTypes,
        ]);
    }

    /**
     * Store a new prompt optimization request
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'personality_type' => 'required|string|max:6',
            'trait_percentages' => 'nullable|array',
            'task_description' => 'required|string|min:10',
        ]);

        // Create the prompt run record
        $promptRun = PromptRun::create([
            'user_id' => auth()->id(),
            'personality_type' => $validated['personality_type'],
            'trait_percentages' => $validated['trait_percentages'] ?? null,
            'task_description' => $validated['task_description'],
            'status' => 'processing',
            'workflow_stage' => 'submitted',
        ]);

        // Prepare payload for framework selector
        $payload = [
            'prompt_run_id' => $promptRun->id,
            'personality_type' => $validated['personality_type'],
            'trait_percentages' => $validated['trait_percentages'] ?? null,
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
                // Handle n8n error
                $promptRun->update([
                    'status' => 'failed',
                    'workflow_stage' => 'failed',
                    'error_message' => 'Framework selector workflow failed: '.$response->body(),
                    'completed_at' => now(),
                ]);

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
            'promptRun' => $promptRun,
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
    public function answerQuestion(Request $request, PromptRun $promptRun)
    {
        // Authorise that the user can update this prompt run
        if ($promptRun->user_id !== auth()->id()) {
            abort(403);
        }

        // Validate that we're in the correct workflow stage
        if ($promptRun->workflow_stage !== 'framework_selected' && $promptRun->workflow_stage !== 'answering_questions') {
            return back()->with('error', 'Cannot answer questions at this stage.');
        }

        $validated = $request->validate([
            'answer' => 'required|string|max:1000',
        ]);

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
                // Handle n8n error
                $promptRun->update([
                    'status' => 'failed',
                    'workflow_stage' => 'failed',
                    'error_message' => 'Final optimization workflow failed: '.$response->body(),
                    'completed_at' => now(),
                ]);

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
     * Display history of prompt runs
     */
    public function history()
    {
        $promptRuns = PromptRun::where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return Inertia::render('PromptOptimizer/History', [
            'promptRuns' => $promptRuns,
        ]);
    }
}
