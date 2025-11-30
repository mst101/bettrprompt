<?php

namespace App\Http\Controllers;

use App\Http\Requests\PromptBuilderAnalyseRequest;
use App\Http\Requests\UpdateOptimizedPromptRequest;
use App\Http\Resources\PromptRunResource;
use App\Jobs\ProcessPromptGeneration;
use App\Jobs\ProcessTaskAnalysis;
use App\Models\PromptRun;
use App\Models\Visitor;
use App\Services\DatabaseService;
use App\Services\PromptFrameworkService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class PromptBuilderController extends Controller
{
    public function __construct(
        private PromptFrameworkService $promptService
    ) {}

    /**
     * Show the prompt builder page
     */
    public function index(Request $request)
    {
        $personalityData = $this->getPersonalityData($request);

        $personalityTypes = [
            'INTJ' => 'Architect',
            'INTP' => 'Logician',
            'ENTJ' => 'Commander',
            'ENTP' => 'Debater',
            'INFJ' => 'Advocate',
            'INFP' => 'Mediator',
            'ENFJ' => 'Protagonist',
            'ENFP' => 'Campaigner',
            'ISTJ' => 'Logistician',
            'ISFJ' => 'Defender',
            'ESTJ' => 'Executive',
            'ESFJ' => 'Consul',
            'ISTP' => 'Virtuoso',
            'ISFP' => 'Adventurer',
            'ESTP' => 'Entrepreneur',
            'ESFP' => 'Entertainer',
        ];

        return Inertia::render('PromptBuilder/Index', [
            'visitorPersonalityType' => $personalityData['personality_type'],
            'visitorTraitPercentages' => $personalityData['trait_percentages'],
            'personalityTypes' => $personalityTypes,
        ]);
    }

    /**
     * Step 1: Analyse task and get clarifying questions
     */
    public function analyse(PromptBuilderAnalyseRequest $request)
    {
        $validated = $request->validated();
        $userId = auth()->id();
        $visitorId = $this->getVisitorId($request);
        $personalityData = $this->getPersonalityData($request);

        // Create a prompt run with initial status
        try {
            $promptRun = DatabaseService::retryOnDeadlock(function () use (
                $userId,
                $visitorId,
                $validated,
                $personalityData
            ) {
                return PromptRun::create([
                    'user_id' => $userId,
                    'visitor_id' => $visitorId,
                    'personality_type' => $validated['personality_type'] ?? $personalityData['personality_type'],
                    'trait_percentages' => $validated['trait_percentages'] ?? $personalityData['trait_percentages'],
                    'task_description' => $validated['task_description'],
                    'status' => 'processing',
                    'workflow_stage' => 'submitted',
                ]);
            });

            // Dispatch the job to analyse the task asynchronously
            ProcessTaskAnalysis::dispatch($promptRun);

            return redirect()->route('prompt-builder.show', $promptRun);

        } catch (\Exception $e) {
            Log::error('Failed to create prompt run', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Failed to create task. Please try again.');
        }
    }

    /**
     * Authorise that the current user/visitor can access this prompt run
     */
    protected function authorizePromptRun(PromptRun $promptRun, Request $request): void
    {
        // Check if authenticated user owns this prompt run
        if (auth()->check() && $promptRun->user_id === auth()->id()) {
            return;
        }

        // Check if visitor owns this prompt run
        $visitorId = $this->getVisitorId($request);
        if ($visitorId && $promptRun->visitor_id === $visitorId) {
            return;
        }

        // No match - unauthorised
        abort(403);
    }

    /**
     * Step 2: Display prompt run with clarifying questions
     */
    public function show(Request $request, PromptRun $promptRun)
    {
        $this->authorizePromptRun($promptRun, $request);

        $promptRun->load(['parent', 'children']);

        $currentQuestionIndex = $promptRun->current_question_index ?? 0;

        // Get current question
        $currentQuestion = null;
        if ($promptRun->framework_questions && isset($promptRun->framework_questions[$currentQuestionIndex])) {
            $question = $promptRun->framework_questions[$currentQuestionIndex];
            $currentQuestion = is_array($question) ? ($question['question'] ?? null) : $question;
        }

        // Get the current question's answer (if it exists from going back/forward)
        $answers = $promptRun->clarifying_answers ?? [];
        $currentQuestionAnswer = $answers[$currentQuestionIndex] ?? null;

        // Check if visitor has already completed a prompt (for client-side modal)
        $visitorHasCompletedPrompts = false;
        if (! auth()->check()) {
            $visitorId = $this->getVisitorId($request);
            if ($visitorId) {
                $visitor = Visitor::find($visitorId);
                $visitorHasCompletedPrompts = $visitor?->hasCompletedPrompts() ?? false;
            }
        }

        return Inertia::render('PromptBuilder/Show', [
            'promptRun' => PromptRunResource::make($promptRun)->resolve(),
            'currentQuestion' => $currentQuestion,
            'currentQuestionAnswer' => $currentQuestionAnswer,
            'progress' => [
                'answered' => $currentQuestionIndex,
                'total' => count($promptRun->framework_questions ?? []),
            ],
            'visitorHasCompletedPrompts' => $visitorHasCompletedPrompts,
        ]);
    }

    /**
     * Step 3: Generate the optimised prompt
     */
    public function generate(Request $request, PromptRun $promptRun)
    {
        $this->authorizePromptRun($promptRun, $request);

        $validated = $request->validate([
            'question_answers' => 'required|array',
        ]);

        try {
            $answers = array_values(
                array_map(
                    fn ($answer) => ($answer === '' || $answer === null) ? null : $answer,
                    $validated['question_answers']
                )
            );

            // Update the prompt run with answers and set status to processing
            DatabaseService::retryOnDeadlock(function () use ($promptRun, $answers) {
                $promptRun->update([
                    'clarifying_answers' => $answers,
                    'status' => 'processing',
                    'workflow_stage' => 'generating_prompt',
                ]);
            });

            // Dispatch the job to generate the prompt asynchronously
            ProcessPromptGeneration::dispatch($promptRun);

            return response()->json([
                'success' => true,
                'message' => 'Generating your optimised prompt...',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to dispatch prompt generation', [
                'prompt_run_id' => $promptRun->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => ['message' => 'Failed to start prompt generation. Please try again.'],
            ], 500);
        }
    }

    /**
     * Submit an answer to a clarifying question (one-at-a-time mode)
     */
    public function answerQuestion(Request $request, PromptRun $promptRun)
    {
        $this->authorizePromptRun($promptRun, $request);

        $validated = $request->validate([
            'question_index' => 'required|integer|min:0',
            'answer' => 'nullable|string',
        ]);

        $answers = $this->saveClarifyingAnswer($promptRun, $validated['question_index'], $validated['answer']);

        return response()->json(['clarifying_answers' => $answers]);
    }

    /**
     * Skip a clarifying question
     */
    public function skipQuestion(Request $request, PromptRun $promptRun)
    {
        $this->authorizePromptRun($promptRun, $request);

        $validated = $request->validate([
            'question_index' => 'required|integer|min:0',
        ]);

        $answers = $this->saveClarifyingAnswer($promptRun, $validated['question_index'], null);

        return response()->json(['clarifying_answers' => $answers]);
    }

    /**
     * Go back to the previous question (keeps the answer for editing)
     */
    public function goBackToPreviousQuestion(Request $request, PromptRun $promptRun)
    {
        $this->authorizePromptRun($promptRun, $request);

        // Validate that we're in the correct workflow stage
        if ($promptRun->workflow_stage !== 'analysis_complete' && $promptRun->workflow_stage !== 'answering_questions') {
            return back()->with('error', 'Cannot go back at this stage.');
        }

        // Check if we can go back
        $currentIndex = $promptRun->current_question_index ?? 0;
        if ($currentIndex === 0) {
            return back()->with('error', 'Already at first question.');
        }

        try {
            // Just decrement the index - don't remove any answers
            $newIndex = $currentIndex - 1;

            Log::info('Going back to previous question (PromptBuilder)', [
                'prompt_run_id' => $promptRun->id,
                'from_index' => $currentIndex,
                'to_index' => $newIndex,
            ]);

            // Update the prompt run
            DatabaseService::retryOnDeadlock(function () use ($promptRun, $newIndex) {
                $promptRun->update([
                    'current_question_index' => $newIndex,
                    'workflow_stage' => $newIndex === 0 ? 'analysis_complete' : 'answering_questions',
                ]);
            });

            return redirect()
                ->route('prompt-builder.show', $promptRun);

        } catch (\Exception $e) {
            Log::error('Failed to go back (PromptBuilder)', [
                'prompt_run_id' => $promptRun->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to go back. Please try again.');
        }
    }

    /**
     * Check if all questions have been answered
     */
    protected function hasAnsweredAllQuestions(PromptRun $promptRun): bool
    {
        $totalQuestions = count($promptRun->framework_questions ?? []);
        $answers = Arr::wrap($promptRun->clarifying_answers ?? []);
        $answers = array_values($answers);

        if ($totalQuestions === 0) {
            return true;
        }

        if (count($answers) < $totalQuestions) {
            return false;
        }

        // Consider answered if every slot is non-null
        foreach ($answers as $answer) {
            if ($answer === null) {
                return false;
            }
        }

        return true;
    }

    /**
     * Create a child prompt run from edited task description
     */
    public function createChild(Request $request, PromptRun $parentPromptRun)
    {
        $this->authorizePromptRun($parentPromptRun, $request);

        // Check if unregistered visitor has already completed a prompt
        if (! auth()->check()) {
            $visitorId = $parentPromptRun->visitor_id ?? $this->getVisitorId($request);
            if ($visitorId) {
                $visitor = Visitor::find($visitorId);
                if ($visitor && $visitor->hasCompletedPrompts()) {
                    return back()->with('error', 'You\'ve already created an optimised prompt as a visitor. Please create a free account to continue.');
                }
            }
        }

        $validated = $request->validate([
            'task_description' => 'required|string',
        ]);

        $user = auth()->user();
        $visitorId = $parentPromptRun->visitor_id ?? $this->getVisitorId($request);
        $personalityType = $user?->personality_type ?? $parentPromptRun->personality_type;
        $traitPercentages = $user?->trait_percentages ?? $parentPromptRun->trait_percentages;

        try {
            $childPromptRun = DatabaseService::retryOnDeadlock(function () use (
                $user,
                $visitorId,
                $parentPromptRun,
                $validated,
                $personalityType,
                $traitPercentages
            ) {
                return PromptRun::create([
                    'visitor_id' => $visitorId,
                    'user_id' => $user?->id,
                    'parent_id' => $parentPromptRun->id,
                    'personality_type' => $personalityType,
                    'trait_percentages' => $traitPercentages,
                    'task_description' => $validated['task_description'],
                    'status' => 'processing',
                    'workflow_stage' => 'submitted',
                ]);
            });

            // Dispatch the job to analyse the task asynchronously
            ProcessTaskAnalysis::dispatch($childPromptRun);

            return redirect()
                ->route('prompt-builder.show', $childPromptRun)
                ->with('success', 'Analysing your updated task...');
        } catch (\Exception $e) {
            Log::error('Failed to create child prompt run for prompt builder', [
                'parent_prompt_run_id' => $parentPromptRun->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()
                ->with('error', 'An error occurred whilst creating the new prompt run. Please try again.');
        }
    }

    /**
     * Create a child prompt run from edited clarifying answers
     */
    public function createChildFromAnswers(Request $request, PromptRun $parentPromptRun)
    {
        $this->authorizePromptRun($parentPromptRun, $request);

        // Check if unregistered visitor has already completed a prompt
        if (! auth()->check()) {
            $visitorId = $parentPromptRun->visitor_id ?? $this->getVisitorId($request);
            if ($visitorId) {
                $visitor = Visitor::find($visitorId);
                if ($visitor && $visitor->hasCompletedPrompts()) {
                    return back()->with('error', 'You\'ve already created an optimised prompt as a visitor. Please create a free account to continue.');
                }
            }
        }

        if (empty($parentPromptRun->framework_questions)) {
            return back()->with('error', 'Parent prompt run does not have clarifying questions.');
        }

        $validated = $request->validate([
            'clarifying_answers' => 'required|array',
        ]);

        $clarifyingAnswers = array_values(
            array_map(
                fn ($answer) => ($answer === '' || $answer === null) ? null : $answer,
                $validated['clarifying_answers'],
            )
        );

        $user = auth()->user();
        $visitorId = $parentPromptRun->visitor_id ?? $this->getVisitorId($request);

        try {
            $childPromptRun = DatabaseService::retryOnDeadlock(function () use (
                $user,
                $visitorId,
                $parentPromptRun,
                $clarifyingAnswers
            ) {
                return PromptRun::create([
                    'visitor_id' => $visitorId,
                    'user_id' => $user?->id,
                    'parent_id' => $parentPromptRun->id,
                    'personality_type' => $user?->personality_type ?? $parentPromptRun->personality_type,
                    'trait_percentages' => $user?->trait_percentages ?? $parentPromptRun->trait_percentages,
                    'task_description' => $parentPromptRun->task_description,
                    'task_classification' => $parentPromptRun->task_classification,
                    'cognitive_requirements' => $parentPromptRun->cognitive_requirements,
                    'selected_framework' => $parentPromptRun->selected_framework,
                    'alternative_frameworks' => $parentPromptRun->alternative_frameworks,
                    'personality_tier' => $parentPromptRun->personality_tier,
                    'task_trait_alignment' => $parentPromptRun->task_trait_alignment,
                    'personality_adjustments_preview' => $parentPromptRun->personality_adjustments_preview,
                    'question_rationale' => $parentPromptRun->question_rationale,
                    'framework_questions' => $parentPromptRun->framework_questions,
                    'clarifying_answers' => $clarifyingAnswers,
                    'status' => 'processing',
                    'workflow_stage' => 'generating_prompt',
                ]);
            });

            // Dispatch the job to generate the prompt asynchronously
            ProcessPromptGeneration::dispatch($childPromptRun);

            return redirect()
                ->route('prompt-builder.show', $childPromptRun)
                ->with('success', 'Generating your optimised prompt with edited answers...');
        } catch (\Exception $e) {
            Log::error('Failed to create child prompt run for prompt builder', [
                'parent_prompt_run_id' => $parentPromptRun->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()
                ->with('error', 'An error occurred whilst creating the new prompt run. Please try again.');
        }
    }

    /**
     * Retry a failed prompt run
     */
    public function retry(Request $request, PromptRun $promptRun)
    {
        $this->authorizePromptRun($promptRun, $request);

        // Only allow retry for failed runs
        if ($promptRun->status !== 'failed') {
            return back()->with('error', 'Only failed runs can be retried.');
        }

        $workflowStage = $promptRun->workflow_stage;

        try {
            // Determine which stage failed and retry from there
            if ($workflowStage === 'failed' || $workflowStage === 'submitted') {
                // Analysis failed - retry from beginning
                Log::info('Retrying analysis (PromptBuilder)', [
                    'prompt_run_id' => $promptRun->id,
                ]);

                DatabaseService::retryOnDeadlock(function () use ($promptRun) {
                    $promptRun->update([
                        'status' => 'processing',
                        'workflow_stage' => 'submitted',
                        'error_message' => null,
                        'completed_at' => null,
                    ]);
                });

                // Dispatch the job to analyse the task asynchronously
                ProcessTaskAnalysis::dispatch($promptRun);

                return redirect()
                    ->route('prompt-builder.show', $promptRun)
                    ->with('success', 'Retrying analysis...');

            } elseif ($workflowStage === 'generating_prompt') {
                // Generation failed - retry generation
                Log::info('Retrying generation (PromptBuilder)', [
                    'prompt_run_id' => $promptRun->id,
                ]);

                DatabaseService::retryOnDeadlock(function () use ($promptRun) {
                    $promptRun->update([
                        'status' => 'processing',
                        'workflow_stage' => 'generating_prompt',
                        'error_message' => null,
                    ]);
                });

                // Dispatch the job to generate the prompt asynchronously
                ProcessPromptGeneration::dispatch($promptRun);

                return redirect()
                    ->route('prompt-builder.show', $promptRun)
                    ->with('success', 'Retrying prompt generation...');
            }

            return back()->with('error', 'Cannot retry from this stage.');

        } catch (\Exception $e) {
            Log::error('Failed to retry (PromptBuilder)', [
                'prompt_run_id' => $promptRun->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'An error occurred whilst retrying. Please try again.');
        }
    }

    /**
     * Get the current visitor ID from cookie
     */
    protected function getVisitorId(Request $request): ?string
    {
        return $request->cookie('visitor_id');
    }

    /**
     * Get personality data for current user or visitor
     */
    protected function getPersonalityData(Request $request): array
    {
        // Authenticated users take priority
        if (auth()->check()) {
            $user = auth()->user();

            return [
                'personality_type' => $user->personality_type,
                'trait_percentages' => $user->trait_percentages,
            ];
        }

        // Fall back to visitor personality
        $visitorId = $this->getVisitorId($request);
        if ($visitorId) {
            $visitor = Visitor::find($visitorId);
            if ($visitor) {
                return [
                    'personality_type' => $visitor->personality_type,
                    'trait_percentages' => $visitor->trait_percentages,
                ];
            }
        }

        return [
            'personality_type' => null,
            'trait_percentages' => null,
        ];
    }

    /**
     * Normalise and persist a single clarifying answer
     */
    protected function saveClarifyingAnswer(PromptRun $promptRun, int $questionIndex, $answer): array
    {
        $questions = $promptRun->framework_questions ?? [];
        $questionCount = count($questions);

        if ($questionCount === 0) {
            return [];
        }

        $index = max(0, min($questionIndex, $questionCount - 1));
        $answers = Arr::wrap($promptRun->clarifying_answers ?? []);
        $answers = array_values($answers);

        // Pad answers to match question count
        for ($i = 0; $i < $questionCount; $i++) {
            if (! array_key_exists($i, $answers)) {
                $answers[$i] = null;
            }
        }

        $answers[$index] = $answer === null || $answer === '' ? null : $answer;
        $answers = array_values($answers);

        // After answering/skipping a question, move to the next one
        $nextIndex = min($index + 1, $questionCount);

        DatabaseService::retryOnDeadlock(function () use ($promptRun, $answers, $nextIndex) {
            $promptRun->update([
                'clarifying_answers' => $answers,
                'current_question_index' => $nextIndex,
                'workflow_stage' => 'answering_questions',
            ]);
        });

        return $answers;
    }

    /**
     * Display prompt builder history
     */
    public function history(Request $request)
    {
        // Get sorting parameters
        $sortBy = $request->query('sort_by', 'created_at');
        $sortDirection = $request->query('sort_direction', 'desc');

        // Get per-page parameter (default 6, allowed: 1-100)
        $perPage = $request->query('per_page', 6);
        $perPage = is_numeric($perPage) ? (int) $perPage : 6;
        $perPage = max(1, min(100, $perPage));

        // Validate sort column
        $allowedSortColumns = ['created_at', 'personality_type', 'status', 'task_description'];
        if (! in_array($sortBy, $allowedSortColumns)) {
            $sortBy = 'created_at';
        }

        // Validate sort direction
        if (! in_array($sortDirection, ['asc', 'desc'])) {
            $sortDirection = 'desc';
        }

        // Get prompt runs for current user, only those from prompt builder (with task_classification)
        $user = auth()->user();

        // Find visitor record linked to this user (if they converted from visitor)
        $visitor = Visitor::where('user_id', $user->id)->first();

        $promptRuns = PromptRun::where(function ($query) use ($user, $visitor) {
            $query->where('user_id', $user->id);
            if ($visitor) {
                // Include prompts created when they were a visitor
                $query->orWhere('visitor_id', $visitor->id);
            }
        })
            ->whereNotNull('task_classification') // Only prompt-builder runs
            ->orderBy($sortBy, $sortDirection)
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('PromptBuilder/History', [
            'promptRuns' => inertiaPaginated($promptRuns, PromptRunResource::class),
            'filters' => [
                'sort_by' => $sortBy,
                'sort_direction' => $sortDirection,
                'per_page' => $perPage,
            ],
        ]);
    }

    /**
     * Update the optimised prompt text
     */
    public function updateOptimizedPrompt(UpdateOptimizedPromptRequest $request, PromptRun $promptRun)
    {
        $this->authorizePromptRun($promptRun, $request);

        // Validate that the prompt run is completed
        if ($promptRun->workflow_stage !== 'completed') {
            return back()->with('error', 'Can only edit completed prompt runs.');
        }

        $validated = $request->validated();

        try {
            DatabaseService::retryOnDeadlock(function () use ($promptRun, $validated) {
                $promptRun->update([
                    'optimized_prompt' => $validated['optimized_prompt'],
                ]);
            });

            Log::info('Updated optimised prompt (PromptBuilder)', [
                'prompt_run_id' => $promptRun->id,
            ]);

            return back()->with('success', 'Prompt updated successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to update optimised prompt (PromptBuilder)', [
                'prompt_run_id' => $promptRun->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to update prompt. Please try again.');
        }
    }

    /**
     * Delete a prompt run
     */
    public function destroy(Request $request, PromptRun $promptRun)
    {
        $this->authorizePromptRun($promptRun, $request);

        try {
            DatabaseService::retryOnDeadlock(function () use ($promptRun) {
                $promptRun->delete();
            });

            Log::info('Deleted prompt run (PromptBuilder)', [
                'prompt_run_id' => $promptRun->id,
            ]);

            return redirect()
                ->route('prompt-builder.history')
                ->with('success', 'Prompt run deleted successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to delete prompt run (PromptBuilder)', [
                'prompt_run_id' => $promptRun->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to delete prompt run. Please try again.');
        }
    }

    /**
     * Create a child prompt run with a different framework
     */
    public function switchFramework(Request $request, PromptRun $promptRun)
    {
        $this->authorizePromptRun($promptRun, $request);

        $validated = $request->validate([
            'framework_code' => 'required|string',
        ]);

        $user = auth()->user();
        $visitorId = $promptRun->visitor_id ?? $this->getVisitorId($request);
        $personalityType = $user?->personality_type ?? $promptRun->personality_type;
        $traitPercentages = $user?->trait_percentages ?? $promptRun->trait_percentages;

        try {
            $childPromptRun = DatabaseService::retryOnDeadlock(function () use (
                $user,
                $visitorId,
                $promptRun,
                $personalityType,
                $traitPercentages
            ) {
                return PromptRun::create([
                    'visitor_id' => $visitorId,
                    'user_id' => $user?->id,
                    'parent_id' => $promptRun->id,
                    'personality_type' => $personalityType,
                    'trait_percentages' => $traitPercentages,
                    'task_description' => $promptRun->task_description,
                    'status' => 'processing',
                    'workflow_stage' => 'submitted',
                ]);
            });

            // Dispatch the job to analyse the task with the forced framework
            ProcessTaskAnalysis::dispatch($childPromptRun, $validated['framework_code']);

            return redirect()
                ->route('prompt-builder.show', $childPromptRun)
                ->with('success', 'Re-analysing with selected framework...');
        } catch (\Exception $e) {
            Log::error('Failed to switch framework (PromptBuilder)', [
                'prompt_run_id' => $promptRun->id,
                'framework_code' => $validated['framework_code'],
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()
                ->with('error', 'An error occurred whilst switching frameworks. Please try again.');
        }
    }
}
