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

                // Update the prompt run with framework selection
                $promptRun->update([
                    'selected_framework' => $responseData['selected_framework'] ?? null,
                    'framework_reasoning' => $responseData['framework_reasoning'] ?? null,
                    'framework_questions' => $responseData['framework_questions'] ?? [],
                    'clarifying_answers' => [],
                    'workflow_stage' => 'framework_selected',
                    'n8n_response_payload' => $responseData,
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
