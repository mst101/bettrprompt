<?php

namespace App\Http\Controllers;

use App\Http\Requests\PromptBuilderAnalyseRequest;
use App\Models\Visitor;
use App\Services\PromptFrameworkService;
use Illuminate\Http\Request;
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

        $result = $this->promptService->analyseTask(
            $validated['task_description'],
            $validated['personality_type'] ?? null,
            $validated['trait_percentages'] ?? null
        );

        return response()->json($result);
    }

    /**
     * Step 2: Generate the optimised prompt
     */
    public function generate(Request $request)
    {
        $validated = $request->validate([
            'task_classification' => 'required|array',
            'selected_framework' => 'required|array',
            'alternative_frameworks' => 'array',
            'personality_tier' => 'required|string|in:full,partial,none',
            'personality_adjustments_preview' => 'array',
            'original_task_description' => 'required|string',
            'personality_type' => 'nullable|string',
            'trait_percentages' => 'nullable|array',
            'question_answers' => 'required|array',
        ]);

        $result = $this->promptService->generatePrompt(
            $validated['task_classification'],
            $validated['selected_framework'],
            $validated['alternative_frameworks'] ?? [],
            $validated['personality_tier'],
            $validated['personality_adjustments_preview'] ?? [],
            $validated['original_task_description'],
            $validated['personality_type'] ?? null,
            $validated['trait_percentages'] ?? null,
            $validated['question_answers']
        );

        return response()->json($result);
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
}
