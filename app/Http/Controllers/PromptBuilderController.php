<?php

namespace App\Http\Controllers;

use App\Http\Requests\PromptBuilderAnalyseRequest;
use App\Http\Resources\PromptRunResource;
use App\Models\PromptRun;
use App\Models\Visitor;
use App\Services\DatabaseService;
use App\Services\PromptFrameworkService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class PromptBuilderController extends Controller
{
    public function __construct(
        private PromptFrameworkService $promptService
    ) {}

    /**
     * Show the prompt builder page
     */
    //    public function index(): Response
    //    {
    //        return Inertia::render('PromptBuilder/Index');
    //    }

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

        $result = $this->promptService->analyseTask(
            $validated['task_description'],
            $validated['personality_type'] ?? $personalityData['personality_type'],
            $validated['trait_percentages'] ?? $personalityData['trait_percentages']
        );

        // Check if the analysis was successful
        if (! $result['success']) {
            return back()->with('error', $result['error']['message'] ?? 'Failed to analyse task');
        }

        // Create a prompt run with the analysis data
        try {
            $promptRun = DatabaseService::retryOnDeadlock(function () use (
                $userId,
                $visitorId,
                $validated,
                $personalityData,
                $result
            ) {
                return PromptRun::create([
                    'user_id' => $userId,
                    'visitor_id' => $visitorId,
                    'personality_type' => $validated['personality_type'] ?? $personalityData['personality_type'],
                    'trait_percentages' => $validated['trait_percentages'] ?? $personalityData['trait_percentages'],
                    'task_description' => $validated['task_description'],
                    'status' => 'pending',
                    'workflow_stage' => 'analysis_complete',
                    // Prompt Builder specific fields from analysis result
                    'task_classification' => $result['data']['task_classification'] ?? null,
                    'selected_framework_details' => $result['data']['selected_framework'] ?? null,
                    'alternative_frameworks' => $result['data']['alternative_frameworks'] ?? [],
                    'personality_tier' => $result['data']['personality_tier'] ?? 'none',
                    'personality_adjustments_preview' => $result['data']['personality_adjustments_preview'] ?? [],
                    'question_rationale' => $result['data']['question_rationale'] ?? null,
                    'framework_questions' => $result['data']['clarifying_questions'] ?? [],
                ]);
            });

            return redirect()->route('prompt-builder.show', $promptRun);

        } catch (\Exception $e) {
            Log::error('Failed to create prompt run', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Failed to save analysis. Please try again.');
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

        return Inertia::render('PromptBuilder/Show', [
            'promptRun' => new PromptRunResource($promptRun),
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
            // Update the prompt run with answers
            DatabaseService::retryOnDeadlock(function () use ($promptRun, $validated) {
                $promptRun->update([
                    'clarifying_answers' => $validated['question_answers'],
                    'workflow_stage' => 'generating_prompt',
                ]);
            });

            // Call the generation workflow
            $result = $this->promptService->generatePrompt(
                $promptRun->task_classification,
                $promptRun->selected_framework_details,
                $promptRun->alternative_frameworks ?? [],
                $promptRun->personality_tier,
                $promptRun->personality_adjustments_preview ?? [],
                $promptRun->task_description,
                $promptRun->personality_type,
                $promptRun->trait_percentages,
                $validated['question_answers']
            );

            // Check if generation was successful
            if (! $result['success']) {
                DatabaseService::retryOnDeadlock(function () use ($promptRun, $result) {
                    $promptRun->update([
                        'status' => 'failed',
                        'error_message' => $result['error']['message'] ?? 'Generation failed',
                    ]);
                });

                return response()->json($result, 500);
            }

            // Update the prompt run with the generated result
            DatabaseService::retryOnDeadlock(function () use ($promptRun, $result) {
                $promptRun->update([
                    'optimized_prompt' => $result['data']['optimised_prompt'] ?? null,
                    'generation_metadata' => $result['data']['metadata'] ?? null,
                    'status' => 'completed',
                    'workflow_stage' => 'completed',
                    'completed_at' => now(),
                ]);
            });

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Failed to generate prompt', [
                'prompt_run_id' => $promptRun->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => ['message' => 'Failed to generate prompt. Please try again.'],
            ], 500);
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
}
