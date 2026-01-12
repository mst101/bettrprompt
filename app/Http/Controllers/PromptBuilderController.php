<?php

namespace App\Http\Controllers;

use App\Http\Requests\AnswerQuestionRequest;
use App\Http\Requests\CreateChildFromAnswersRequest;
use App\Http\Requests\CreateChildFromTaskRequest;
use App\Http\Requests\CreateChildWithFrameworkRequest;
use App\Http\Requests\GeneratePromptRequest;
use App\Http\Requests\PromptBuilderAnalyseRequest;
use App\Http\Requests\SubmitPreAnalysisAnswersRequest;
use App\Http\Requests\UpdateOptimizedPromptRequest;
use App\Http\Requests\UpdatePreAnalysisAnswersRequest;
use App\Http\Resources\ClaudeModelResource;
use App\Http\Resources\PromptRunPageResource;
use App\Http\Resources\PromptRunResource;
use App\Jobs\ProcessAnalysis;
use App\Jobs\ProcessPreAnalysis;
use App\Jobs\ProcessPromptGeneration;
use App\Models\ClaudeModel;
use App\Models\Country;
use App\Models\Currency;
use App\Models\PromptRun;
use App\Models\Visitor;
use App\Services\DatabaseService;
use App\Services\GeolocationService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class PromptBuilderController extends Controller
{
    /**
     * Display the prompt builder landing page
     */
    public function index(Request $request)
    {
        $personalityData = $this->getPersonalityData($request);
        $locationData = $this->getLocationData($request);

        $personalityTypes = [
            'INTJ' => __('messages.personality_types.intj'),
            'INTP' => __('messages.personality_types.intp'),
            'ENTJ' => __('messages.personality_types.entj'),
            'ENTP' => __('messages.personality_types.entp'),
            'INFJ' => __('messages.personality_types.infj'),
            'INFP' => __('messages.personality_types.infp'),
            'ENFJ' => __('messages.personality_types.enfj'),
            'ENFP' => __('messages.personality_types.enfp'),
            'ISTJ' => __('messages.personality_types.istj'),
            'ISFJ' => __('messages.personality_types.isfj'),
            'ESTJ' => __('messages.personality_types.estj'),
            'ESFJ' => __('messages.personality_types.esfj'),
            'ISTP' => __('messages.personality_types.istp'),
            'ISFP' => __('messages.personality_types.isfp'),
            'ESTP' => __('messages.personality_types.estp'),
            'ESFP' => __('messages.personality_types.esfp'),
        ];

        $countries = Country::sortedByName()->map(fn ($country) => [
            'value' => $country->id,
            'label' => $country->name,
        ])->values();

        $currencies = Currency::where('active', true)->get()->map(fn ($currency) => [
            'value' => $currency->id,
            'label' => "$currency->symbol ($currency->id)",
        ])->values();

        $supportedLocales = config('app.supported_locales');
        $languageLabels = [
            'en-US' => 'English (US)',
            'en-GB' => 'English (UK)',
            'de-DE' => 'Deutsch',
            'fr-FR' => 'Français',
            'es-ES' => 'Español',
        ];
        $languages = collect($supportedLocales)
            ->map(fn ($locale) => [
                'value' => $locale,
                'label' => $languageLabels[$locale] ?? $locale,
            ])
            ->values();

        return Inertia::render('PromptBuilder/Index', [
            'visitorPersonalityType' => $personalityData['personality_type'],
            'visitorTraitPercentages' => $personalityData['trait_percentages'],
            'personalityTypes' => $personalityTypes,
            'locationData' => $locationData,
            'countries' => $countries,
            'currencies' => $currencies,
            'languages' => $languages,
            'locationPromptDismissed' => auth()->check()
                ? (bool) auth()->user()->location_prompt_dismissed
                : false,
        ]);
    }

    // ======== WORKFLOW 0: PRE-ANALYSIS ========

    /**
     * Workflow 0: Initiate pre-analysis of the task
     * Creates a prompt run and dispatches the pre-analysis job.
     * This is the entry point for the user's journey.
     */
    public function preAnalyse(PromptBuilderAnalyseRequest $request)
    {
        $validated = $request->validated();
        $userId = auth()->id();
        $visitorId = $this->getVisitorId($request);
        $personalityData = $this->getPersonalityData($request);

        $personalityType = $validated['personality_type'] ?? $personalityData['personality_type'];
        $traitPercentages = $validated['trait_percentages'] ?? $personalityData['trait_percentages'];

        // For logged-in users: Check if they have location data, if not look it up from IP
        if ($userId) {
            $user = auth()->user();
            if (
                ! $user->hasLocationData()
                && config('geoip.enabled')
                && ! $user->location_prompt_dismissed
            ) {
                try {
                    $geolocationService = new GeolocationService;
                    $locationData = $geolocationService->lookupIp($request->ip());

                    if ($locationData !== null) {
                        $user->update([
                            'country_code' => $locationData->countryCode,
                            'country_name' => $locationData->countryName,
                            'region' => $locationData->region,
                            'city' => $locationData->city,
                            'timezone' => $locationData->timezone,
                            'currency_code' => $locationData->currencyCode,
                            'latitude' => $locationData->latitude,
                            'longitude' => $locationData->longitude,
                            'language_code' => $locationData->languageCode,
                            'location_detected_at' => $locationData->detectedAt,
                            'location_manually_set' => false,
                            'language_manually_set' => false,
                        ]);

                        $user->updateProfileCompletion();

                        Log::info('Location detected from IP for existing user', [
                            'user_id' => $user->id,
                            'country' => $locationData->countryCode,
                            'ip' => $request->ip(),
                        ]);
                    }
                } catch (Exception $e) {
                    Log::error('Failed to lookup location for existing user', [
                        'user_id' => $user->id,
                        'ip' => $request->ip(),
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        // Create a prompt run with initial status
        // Pre-analysis will run asynchronously via ProcessPreAnalysis job
        try {
            $promptRun = DatabaseService::retryOnDeadlock(function () use (
                $userId,
                $visitorId,
                $validated,
                $personalityType,
                $traitPercentages
            ) {
                return PromptRun::create([
                    'user_id' => $userId,
                    'visitor_id' => $visitorId,
                    'personality_type' => $personalityType,
                    'trait_percentages' => $traitPercentages,
                    'task_description' => $validated['task_description'],
                    'workflow_stage' => '0_processing',
                ]);
            });

            // Dispatch the job to generate pre-analysis questions (Workflow 0)
            // The job will either show questions or proceed directly to main analysis
            ProcessPreAnalysis::dispatch($promptRun, $this->getJobDatabase($request));

            return redirect()->route('prompt-builder.show', [
                'country' => $request->route('country'),
                'promptRun' => $promptRun,
            ]);

        } catch (Exception $e) {
            Log::error('Failed to create prompt run', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', __('messages.prompt_builder.task_created_failed'));
        }
    }

    // ======== WORKFLOW 1: ANALYSIS ========

    /**
     * Workflow 1: Initiate analysis using pre-analysis answers
     * Transitions from workflow 0 to workflow 1 using the pre-analysis answers.
     * Dispatches the analysis job which determines framework and clarifying questions.
     */
    public function analyse(
        SubmitPreAnalysisAnswersRequest $request,
        string $locale,
        PromptRun|string $promptRun
    ) {
        $promptRun = $this->resolvePromptRun($request, $promptRun);
        $this->authorizePromptRun($promptRun, $request);

        // Validate workflow stage
        if ($promptRun->workflow_stage !== '0_completed') {
            return back()->with('error', __('messages.prompt_builder.invalid_workflow_stage'));
        }

        $validated = $request->validated();

        try {
            // Build structured pre_analysis_context from answers
            // Merges inferred values (from pre-analysis) with user answers
            $preAnalysisContext = $this->buildPreAnalysisContext(
                $promptRun->pre_analysis_questions,
                $validated['answers'],
                $promptRun
            );

            // Update PromptRun with answers and context, then dispatch workflow_1
            DatabaseService::retryOnDeadlock(function () use ($promptRun, $validated, $preAnalysisContext) {
                $promptRun->update([
                    'pre_analysis_answers' => $validated['answers'],
                    'pre_analysis_context' => $preAnalysisContext,
                    'workflow_stage' => '1_processing',
                ]);
            });

            // Dispatch workflow_1 (which will enhance + analyse)
            ProcessAnalysis::dispatch($promptRun, null, $this->getJobDatabase($request));

            return redirect()
                ->route('prompt-builder.show', $promptRun)
                ->with('success', __('messages.prompt_builder.analysing_task'));

        } catch (Exception $e) {
            Log::error('Failed to submit pre-analysis answers', [
                'prompt_run_id' => $promptRun->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', __('messages.prompt_builder.submit_answers_failed'));
        }
    }

    /**
     * Workflow 1: Update pre-analysis answers and re-analyse
     * Allows users to edit pre-analysis answers and re-run the analysis.
     * Used in view-edit mode for tweaking responses before clarifying questions.
     */
    public function updatePreAnalysisAnswers(
        UpdatePreAnalysisAnswersRequest $request,
        string $locale,
        PromptRun|string $promptRun
    ) {
        $promptRun = $this->resolvePromptRun($request, $promptRun);
        $this->authorizePromptRun($promptRun, $request);

        // Validate workflow stage - should have pre-analysis questions
        if (! $promptRun->pre_analysis_questions || empty($promptRun->pre_analysis_questions)) {
            return back()->with('error', __('messages.prompt_builder.no_quick_queries'));
        }

        $validated = $request->validated();

        try {
            // Build structured pre_analysis_context from updated answers
            // Merges inferred values (from pre-analysis) with user answers
            $preAnalysisContext = $this->buildPreAnalysisContext(
                $promptRun->pre_analysis_questions,
                $validated['answers'],
                $promptRun
            );

            // Update the prompt run with new answers and context, then dispatch workflow_1
            DatabaseService::retryOnDeadlock(function () use ($promptRun, $validated, $preAnalysisContext) {
                $promptRun->update([
                    'pre_analysis_answers' => $validated['answers'],
                    'pre_analysis_context' => $preAnalysisContext,
                    'workflow_stage' => '1_processing',
                ]);
            });

            // Dispatch workflow_1 again to re-analyse with new answers
            ProcessAnalysis::dispatch($promptRun, null, $this->getJobDatabase($request));

            return redirect()
                ->route('prompt-builder.show', $promptRun)
                ->with('success', __('messages.prompt_builder.updating_answers'));

        } catch (Exception $e) {
            Log::error('Failed to update quick queries', [
                'prompt_run_id' => $promptRun->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', __('messages.prompt_builder.update_answers_failed'));
        }
    }

    /**
     * Workflow 1: Display prompt run with clarifying questions
     * Shows the current state of the analysis, including current question to answer.
     * This is a view-only method; changes are made via answerQuestion/skipQuestion/goBackToPreviousQuestion.
     */
    public function show(Request $request, string $locale, PromptRun|string $promptRun)
    {
        $promptRun = $this->resolvePromptRun($request, $promptRun);
        $this->authorizePromptRun($promptRun, $request);

        // Load parent/children relationships (minimal data only via PromptRunRelationshipResource)
        // Full details are fetched on-demand via API
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
        // Guests always get 'advanced' UI complexity, authenticated users get their preference
        $visitorHasCompletedPrompts = false;
        $visitorHasAccount = false;
        $uiComplexity = 'advanced'; // default for guests

        if (auth()->check()) {
            $uiComplexity = auth()->user()->getUiComplexity();
        } else {
            $visitorId = $this->getVisitorId($request);
            if ($visitorId) {
                $visitor = Visitor::find($visitorId);
                if ($visitor) {
                    $visitorHasCompletedPrompts = $visitor->hasCompletedPrompts();
                    $visitorHasAccount = $visitor->user_id !== null;
                }
            }
        }

        // Fetch Claude models (admin users only)
        $claudeModels = [];
        if (auth()->check() && auth()->user()->is_admin) {
            $claudeModels = ClaudeModelResource::collection(
                ClaudeModel::active()->orderByDesc('release_date')->get()
            )->resolve();
        }

        return Inertia::render('PromptBuilder/Show', [
            'promptRun' => PromptRunPageResource::make($promptRun)->resolve(),
            'currentQuestion' => $currentQuestion,
            'currentQuestionAnswer' => $currentQuestionAnswer,
            'progress' => [
                'answered' => $currentQuestionIndex,
                'total' => count($promptRun->framework_questions ?? []),
            ],
            'visitorHasCompletedPrompts' => $visitorHasCompletedPrompts,
            'visitorHasAccount' => $visitorHasAccount,
            'uiComplexity' => $uiComplexity,
            'claudeModels' => $claudeModels,
        ]);
    }

    /**
     * Workflow 1: Answer a single clarifying question
     * Records the answer to the current question and advances to the next.
     * Used in one-at-a-time answering mode.
     */
    public function answerQuestion(
        AnswerQuestionRequest $request,
        string $locale,
        PromptRun|string $promptRun
    ) {
        $promptRun = $this->resolvePromptRun($request, $promptRun);
        $this->authorizePromptRun($promptRun, $request);

        $validated = $request->validated();

        $answers = $promptRun->recordClarifyingAnswer($validated['question_index'], $validated['answer']);

        return response()->json(['clarifying_answers' => $answers]);
    }

    /**
     * Workflow 1: Navigate back to the previous clarifying question
     * Moves backward through questions without losing answers.
     * Allows users to review and edit earlier responses.
     */
    public function goBackToPreviousQuestion(
        Request $request,
        string $locale,
        PromptRun|string $promptRun
    ) {
        $promptRun = $this->resolvePromptRun($request, $promptRun);
        $this->authorizePromptRun($promptRun, $request);

        // Validate that we're in the correct workflow stage
        if ($promptRun->workflow_stage !== '1_completed') {
            return back()->with('error', __('messages.prompt_builder.cannot_go_back'));
        }

        // Check if we can go back
        $currentIndex = $promptRun->current_question_index ?? 0;
        if ($currentIndex === 0) {
            return back()->with('error', __('messages.prompt_builder.already_first_question'));
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
                ]);
            });

            return redirect()
                ->route('prompt-builder.show', $promptRun);

        } catch (Exception $e) {
            Log::error('Failed to go back (PromptBuilder)', [
                'prompt_run_id' => $promptRun->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', __('messages.prompt_builder.go_back_failed'));
        }
    }

    // ======== WORKFLOW 2: GENERATION ========

    /**
     * Workflow 2: Generate the optimised prompt
     * Takes clarifying answers and dispatches the prompt generation job.
     * This is the action that creates the final personalised prompt.
     */
    public function generate(
        GeneratePromptRequest $request,
        string $locale,
        PromptRun|string $promptRun
    ) {
        $promptRun = $this->resolvePromptRun($request, $promptRun);
        $this->authorizePromptRun($promptRun, $request);

        $validated = $request->validated();

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
                    'workflow_stage' => '2_processing',
                ]);
            });

            // Dispatch the job to generate the prompt asynchronously
            ProcessPromptGeneration::dispatch($promptRun, $this->getJobDatabase($request));

            return response()->json([
                'success' => true,
                'message' => __('messages.prompt_builder.generating_optimised_prompt'),
            ]);

        } catch (Exception $e) {
            Log::error('Failed to dispatch prompt generation', [
                'prompt_run_id' => $promptRun->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => ['message' => __('messages.prompt_builder.prompt_generation_start_failed')],
            ], 500);
        }
    }

    /**
     * Workflow 2: Update the optimised prompt text
     * Allows users to manually edit the generated prompt after creation.
     * Only available for completed (2_completed) workflow runs.
     */
    public function updateOptimizedPrompt(
        UpdateOptimizedPromptRequest $request,
        string $locale,
        PromptRun|string $promptRun
    ) {
        $promptRun = $this->resolvePromptRun($request, $promptRun);
        $this->authorizePromptRun($promptRun, $request);

        // Validate that the prompt run is completed
        if ($promptRun->workflow_stage !== '2_completed') {
            return back()->with('error', __('messages.prompt_builder.can_only_edit_completed'));
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

            return back()->with('success', __('messages.prompt_builder.prompt_updated'));
        } catch (Exception $e) {
            Log::error('Failed to update optimised prompt (PromptBuilder)', [
                'prompt_run_id' => $promptRun->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', __('messages.prompt_builder.update_prompt_failed'));
        }
    }

    // ======== VARIATIONS & BRANCHING ========

    /**
     * Create a child prompt run from edited task description
     * Allows users to start a new variant with a different task description.
     * Branches from an existing prompt run, inheriting personality and other context.
     */
    public function createChild(
        CreateChildFromTaskRequest $request,
        string $locale,
        PromptRun|string $parentPromptRun
    ) {
        $parentPromptRun = $this->resolvePromptRun($request, $parentPromptRun);
        $this->authorizePromptRun($parentPromptRun, $request);

        // Check if unregistered visitor has already completed a prompt
        if (! auth()->check()) {
            $visitorId = $parentPromptRun->visitor_id ?? $this->getVisitorId($request);
            if ($visitorId) {
                $visitor = Visitor::find($visitorId);
                if ($visitor && $visitor->hasCompletedPrompts()) {
                    return back()->with('error', __('messages.prompt_builder.visitor_limit_reached'));
                }
            }
        }

        $validated = $request->validated();

        $user = auth()->user();
        $visitorId = $parentPromptRun->visitor_id ?? $this->getVisitorId($request);
        $personalityType = $user?->personality_type ?? $parentPromptRun->personality_type;
        $traitPercentages = $user?->trait_percentages ?? $parentPromptRun->trait_percentages;

        // Create child prompt run and dispatch async pre-analysis (Workflow 0)
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
                    'workflow_stage' => '0_processing',
                ]);
            });

            // Dispatch the job to generate pre-analysis questions (Workflow 0)
            // The job will either show questions or proceed directly to main analysis
            ProcessPreAnalysis::dispatch($childPromptRun, $this->getJobDatabase($request));

            return redirect()->route('prompt-builder.show', $childPromptRun);
        } catch (Exception $e) {
            Log::error('Failed to create child prompt run for prompt builder', [
                'parent_prompt_run_id' => $parentPromptRun->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()
                ->with('error', __('messages.prompt_builder.create_prompt_run_failed'));
        }
    }

    /**
     * Create a child prompt run from edited clarifying answers
     * Allows users to regenerate with different answers to the clarifying questions.
     * Branches from an existing prompt run, inheriting all pre-analysis and framework data.
     */
    public function createChildFromAnswers(
        CreateChildFromAnswersRequest $request,
        string $locale,
        PromptRun|string $parentPromptRun
    ) {
        $parentPromptRun = $this->resolvePromptRun($request, $parentPromptRun);
        $this->authorizePromptRun($parentPromptRun, $request);

        // Check if unregistered visitor has already completed a prompt
        if (! auth()->check()) {
            $visitorId = $parentPromptRun->visitor_id ?? $this->getVisitorId($request);
            if ($visitorId) {
                $visitor = Visitor::find($visitorId);
                if ($visitor && $visitor->hasCompletedPrompts()) {
                    return back()->with('error', __('messages.prompt_builder.visitor_limit_reached'));
                }
            }
        }

        if (empty($parentPromptRun->framework_questions)) {
            return back()->with('error', __('messages.prompt_builder.no_clarifying_questions'));
        }

        $validated = $request->validated();

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
                    'pre_analysis_questions' => $parentPromptRun->pre_analysis_questions,
                    'pre_analysis_answers' => $parentPromptRun->pre_analysis_answers,
                    'pre_analysis_context' => $parentPromptRun->pre_analysis_context,
                    'pre_analysis_reasoning' => $parentPromptRun->pre_analysis_reasoning,
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
                    'workflow_stage' => '2_processing',
                ]);
            });

            // Dispatch the job to generate the prompt asynchronously
            ProcessPromptGeneration::dispatch($childPromptRun, $this->getJobDatabase($request));

            return redirect()
                ->route('prompt-builder.show', $childPromptRun)
                ->with('success', __('messages.prompt_builder.generating_optimised_prompt'));
        } catch (Exception $e) {
            Log::error('Failed to create child prompt run for prompt builder', [
                'parent_prompt_run_id' => $parentPromptRun->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()
                ->with('error', __('messages.prompt_builder.create_prompt_run_failed'));
        }
    }

    /**
     * Create a child prompt run with a different framework
     * Allows users to re-analyse their task with a different prompt framework.
     * Keeps pre-analysis data but replaces the selected framework and its questions.
     */
    public function switchFramework(
        CreateChildWithFrameworkRequest $request,
        string $locale,
        PromptRun|string $promptRun
    ) {
        $promptRun = $this->resolvePromptRun($request, $promptRun);
        $this->authorizePromptRun($promptRun, $request);

        $validated = $request->validated();

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
                    'pre_analysis_questions' => $promptRun->pre_analysis_questions,
                    'pre_analysis_answers' => $promptRun->pre_analysis_answers,
                    'pre_analysis_context' => $promptRun->pre_analysis_context,
                    'pre_analysis_reasoning' => $promptRun->pre_analysis_reasoning,
                    'workflow_stage' => '1_processing',
                ]);
            });

            // Dispatch the job to analyse the task with the forced framework
            ProcessAnalysis::dispatch($childPromptRun, $validated['framework_code'],
                $this->getJobDatabase($request));

            // Redirect to the new prompt run's show page
            // Inertia will handle the redirect and fetch the full page data
            return redirect()
                ->route('prompt-builder.show', $childPromptRun)
                ->with('success', __('messages.prompt_builder.switching_framework'));
        } catch (Exception $e) {
            Log::error('Failed to switch framework (PromptBuilder)', [
                'prompt_run_id' => $promptRun->id,
                'framework_code' => $validated['framework_code'],
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()
                ->with('error', __('messages.prompt_builder.switch_framework_failed'));
        }
    }

    // ======== STATE MANAGEMENT ========

    /**
     * Retry a failed prompt run
     * Resets the failed workflow stage and re-dispatches the job.
     * Allows recovery from failures in any workflow stage.
     */
    public function retry(Request $request, string $locale, PromptRun|string $promptRun)
    {
        $promptRun = $this->resolvePromptRun($request, $promptRun);
        $this->authorizePromptRun($promptRun, $request);

        // Only allow retry for failed runs
        if (! $promptRun->isFailed()) {
            return back()->with('error', __('messages.prompt_builder.only_failed_runs_can_retry'));
        }

        try {
            // Determine which workflow failed and retry from there
            $failedWorkflow = $promptRun->getFailedWorkflow();

            if ($failedWorkflow === 0) {
                // Workflow 0 (pre-analysis) failed - retry from beginning
                Log::info('Retrying pre-analysis (workflow_0)', [
                    'prompt_run_id' => $promptRun->id,
                ]);

                DatabaseService::retryOnDeadlock(function () use ($promptRun) {
                    $promptRun->update([
                        'workflow_stage' => '0_processing',
                        'error_message' => null,
                        'completed_at' => null,
                    ]);
                });

                // Dispatch the job to run pre-analysis asynchronously
                ProcessPreAnalysis::dispatch($promptRun, $this->getJobDatabase($request));

                return redirect()
                    ->route('prompt-builder.show', $promptRun)
                    ->with('success', __('messages.prompt_builder.retrying_pre_analysis'));

            } elseif ($failedWorkflow === 1) {
                // Workflow 1 (analysis) failed - retry analysis
                Log::info('Retrying analysis (workflow_1)', [
                    'prompt_run_id' => $promptRun->id,
                ]);

                DatabaseService::retryOnDeadlock(function () use ($promptRun) {
                    $promptRun->update([
                        'workflow_stage' => '1_processing',
                        'error_message' => null,
                        'completed_at' => null,
                    ]);
                });

                // Dispatch the job to analyse the task asynchronously
                ProcessAnalysis::dispatch($promptRun, null, $this->getJobDatabase($request));

                return redirect()
                    ->route('prompt-builder.show', $promptRun)
                    ->with('success', __('messages.prompt_builder.retrying_analysis'));

            } elseif ($failedWorkflow === 2) {
                // Workflow 2 (generation) failed - retry generation
                Log::info('Retrying generation (workflow_2)', [
                    'prompt_run_id' => $promptRun->id,
                ]);

                DatabaseService::retryOnDeadlock(function () use ($promptRun) {
                    $promptRun->update([
                        'workflow_stage' => '2_processing',
                        'error_message' => null,
                    ]);
                });

                // Dispatch the job to generate the prompt asynchronously
                ProcessPromptGeneration::dispatch($promptRun, $this->getJobDatabase($request));

                return redirect()
                    ->route('prompt-builder.show', $promptRun)
                    ->with('success', __('messages.prompt_builder.retrying_prompt_generation'));
            }

            return back()->with('error', __('messages.prompt_builder.cannot_retry_from_stage'));

        } catch (Exception $e) {
            Log::error('Failed to retry (PromptBuilder)', [
                'prompt_run_id' => $promptRun->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', __('messages.prompt_builder.retry_failed'));
        }
    }

    /**
     * Delete a prompt run
     * Permanently removes a prompt run from the system.
     * Users can only delete their own prompt runs.
     */
    public function destroy(Request $request, string $locale, PromptRun|string $promptRun)
    {
        $promptRun = $this->resolvePromptRun($request, $promptRun);
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
                ->with('success', __('messages.prompt_builder.deleted_successfully'));
        } catch (Exception $e) {
            Log::error('Failed to delete prompt run (PromptBuilder)', [
                'prompt_run_id' => $promptRun->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', __('messages.prompt_builder.delete_failed'));
        }
    }

    // ======== RETRIEVAL & HISTORY ========

    /**
     * Display prompt builder history
     * Shows all completed or in-progress prompt runs for the authenticated user.
     * Supports sorting and pagination.
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
        $allowedSortColumns = ['created_at', 'personality_type', 'workflow_stage', 'task_description'];
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
            ->where(function ($query) {
                // Include prompt-builder runs that have at least completed workflow_0 (pre-analysis)
                // or workflow_1 (analysis)
                $query->whereNotNull('task_classification') // Completed workflow_1
                    ->orWhereNotNull('pre_analysis_questions'); // Completed workflow_0
            })
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
     * Get full details of a prompt run
     * API endpoint for fetching complete prompt run data.
     * Used for on-demand loading of related prompts and full details.
     */
    public function getFullDetails(
        Request $request,
        string $locale,
        PromptRun|string $promptRun
    ) {
        $promptRun = $this->resolvePromptRun($request, $promptRun);
        $this->authorizePromptRun($promptRun, $request);

        return response()->json([
            'promptRun' => PromptRunResource::make($promptRun)->resolve(),
        ]);
    }

    // ======== INTERNAL HELPERS ========

    /**
     * Determine which database to use for queue jobs based on request context
     */
    private function getJobDatabase(Request $request): ?string
    {
        // If X-Data-Collection-Test header is present, use bettrprompt_data_collection
        // This ensures queue jobs save to the same database as the HTTP request
        return $request->hasHeader('X-Data-Collection-Test')
            ? 'bettrprompt_data_collection'
            : null;
    }

    /**
     * Resolve prompt run when route model binding is unavailable.
     */
    private function resolvePromptRun(Request $request, PromptRun|string $promptRun): PromptRun
    {
        if ($promptRun instanceof PromptRun) {
            return $promptRun;
        }

        if (! is_numeric($promptRun)) {
            $routeValue = $request->route('promptRun');
            if ($routeValue instanceof PromptRun) {
                return $routeValue;
            }
            if (is_numeric($routeValue)) {
                $promptRun = $routeValue;
            }
        }

        return PromptRun::findOrFail($promptRun);
    }

    /**
     * Authorise that the current user/visitor can access this prompt run
     */
    protected function authorizePromptRun(PromptRun $promptRun, Request $request): void
    {
        if (! $promptRun->canBeAccessedBy(auth()->id(), $this->getVisitorId($request))) {
            abort(403);
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
     * Get location data for current user or visitor.
     */
    protected function getLocationData(Request $request): array
    {
        if (auth()->check()) {
            $user = auth()->user();

            return [
                'countryCode' => $user->country_code,
                'countryName' => $user->country_name,
                'region' => $user->region,
                'city' => $user->city,
                'timezone' => $user->timezone,
                'currencyCode' => $user->currency_code,
                'languageCode' => $user->language_code,
                'detectedAt' => $user->location_detected_at,
                'manuallySet' => $user->location_manually_set,
            ];
        }

        $visitorId = $this->getVisitorId($request);
        if ($visitorId) {
            $visitor = Visitor::find($visitorId);
            if ($visitor) {
                return [
                    'countryCode' => $visitor->country_code,
                    'countryName' => $visitor->country_name,
                    'region' => $visitor->region,
                    'city' => $visitor->city,
                    'timezone' => $visitor->timezone,
                    'currencyCode' => $visitor->currency_code,
                    'languageCode' => $visitor->language_code,
                    'detectedAt' => $visitor->location_detected_at,
                    'manuallySet' => false,
                ];
            }
        }

        return [
            'countryCode' => null,
            'countryName' => null,
            'region' => null,
            'city' => null,
            'timezone' => null,
            'currencyCode' => null,
            'languageCode' => null,
            'detectedAt' => null,
            'manuallySet' => false,
        ];
    }

    /**
     * Build structured pre_analysis_context from questions and answers
     * Merges inferred context with user-provided answers
     */
    protected function buildPreAnalysisContext(?array $questions, array $answers, ?PromptRun $promptRun = null): array
    {
        // Start with inferred values from pre-analysis (if available)
        $context = [];
        if ($promptRun && $promptRun->pre_analysis_context) {
            $context = is_array($promptRun->pre_analysis_context) ? $promptRun->pre_analysis_context : [];
        }

        if (! $questions) {
            return $context;
        }

        // Merge in user answers to asked questions
        foreach ($questions as $question) {
            $questionId = $question['id'] ?? null;
            $questionText = $question['question'] ?? null;
            $answer = $answers[$questionId] ?? null;
            $questionType = $question['type'] ?? null;

            if ($questionId) {
                $context[$questionId] = [
                    'question' => $questionText,
                    'answer' => $answer,
                ];

                if ($questionType === 'choice') {
                    $matchingOption = Arr::first(
                        $question['options'] ?? [],
                        fn ($option) => ($option['value'] ?? null) === $answer
                    );

                    if ($matchingOption && isset($matchingOption['label'])) {
                        $context[$questionId]['answer_label'] = $matchingOption['label'];
                    }
                }
            }
        }

        return $context;
    }
}
