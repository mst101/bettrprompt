<?php

namespace App\Jobs;

use App\Models\AnalyticsEvent;
use App\Models\AnalyticsSession;
use App\Models\PromptRun;
use App\Services\ConversionAttributionService;
use App\Services\FrameworkSelectionService;
use App\Services\FunnelProcessingService;
use App\Services\PromptQualityService;
use App\Services\QuestionAnalyticsService;
use App\Services\SessionProcessorService;
use App\Services\WorkflowAnalyticsService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessAnalyticsEvents implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @param  array  $events  Raw event data from client
     * @param  string|null  $visitorId  Server-derived visitor ID
     * @param  int|null  $userId  Authenticated user ID
     * @param  string|null  $sessionId  Analytics session ID from header
     * @param  array  $pageContext  Page context (user agent, device type, etc.)
     */
    public function __construct(
        private array $events,
        private ?string $visitorId = null,
        private ?int $userId = null,
        private ?string $sessionId = null,
        private array $pageContext = [],
    ) {}

    /**
     * Execute the job.
     */
    public function handle(
        ConversionAttributionService $attributionService,
        SessionProcessorService $sessionService,
        FrameworkSelectionService $frameworkService,
        QuestionAnalyticsService $questionService,
        WorkflowAnalyticsService $workflowService,
        PromptQualityService $qualityService,
        FunnelProcessingService $funnelService,
    ): void {
        try {
            Log::info('Processing analytics events', [
                'event_count' => count($this->events),
                'visitor_id' => $this->visitorId,
                'user_id' => $this->userId,
            ]);

            $eventsToInsert = [];
            $insertedEventIds = [];

            foreach ($this->events as $event) {
                $enrichedEvent = $this->enrichEvent($event);

                if ($enrichedEvent) {
                    $eventsToInsert[] = $enrichedEvent;
                    $insertedEventIds[] = $enrichedEvent['event_id'];
                }
            }

            // Ensure the analytics session exists before inserting events
            // This prevents foreign key constraint violations
            if (! empty($eventsToInsert) && $this->sessionId) {
                $this->ensureSessionExists($eventsToInsert);
            }

            // Batch insert with upsert semantics (ignore duplicates)
            if (! empty($eventsToInsert)) {
                $this->batchInsertEvents($eventsToInsert);
            }

            // Process attribution for conversion events
            $this->processAttribution($insertedEventIds, $attributionService);

            // Process session data
            $this->processSessions($eventsToInsert, $sessionService);

            // Process funnel progression
            $this->processFunnels($eventsToInsert, $funnelService);

            // Process domain-specific analytics
            $this->processDomainAnalytics(
                $eventsToInsert,
                $frameworkService,
                $questionService,
                $workflowService,
                $qualityService,
            );

            Log::info('Analytics events processed successfully', [
                'inserted_count' => count($eventsToInsert),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to process analytics events', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Process attribution for newly inserted events
     */
    private function processAttribution(
        array $eventIds,
        ConversionAttributionService $attributionService,
    ): void {
        if (empty($eventIds)) {
            return;
        }

        // Load events and process attributions
        $events = AnalyticsEvent::whereIn('event_id', $eventIds)->get();

        $experimentsToUpdate = collect();

        foreach ($events as $event) {
            // Attribute conversion events to experiments
            if ($event->type === 'conversion') {
                $attributionService->attributeConversion($event);

                // Track which experiments were involved for aggregates update
                $event->eventExperiments()->pluck('experiment_id')
                    ->each(fn ($id) => $experimentsToUpdate->push($id));
            }
        }

        // Dispatch aggregates update for affected experiments
        foreach ($experimentsToUpdate->unique() as $experimentId) {
            UpdateExperimentAggregates::dispatch($experimentId);
        }
    }

    /**
     * Process session data from events
     */
    private function processSessions(
        array $enrichedEvents,
        SessionProcessorService $sessionService,
    ): void {
        if (empty($enrichedEvents)) {
            return;
        }

        // Group by session_id
        $bySession = collect($enrichedEvents)->groupBy('session_id');

        foreach ($bySession as $sessionId => $events) {
            if ($sessionId) {
                $sessionService->processSessionEvents($events->toArray());
            }
        }
    }

    /**
     * Ensure the analytics session exists before inserting events.
     * Creates a minimal session record from the session ID and first event data.
     */
    private function ensureSessionExists(array $enrichedEvents): void
    {
        try {
            // Check if session already exists
            if (AnalyticsSession::where('id', $this->sessionId)->exists()) {
                return;
            }

            $firstEvent = reset($enrichedEvents);
            if (! $firstEvent) {
                return;
            }

            $startedAt = Carbon::parse($firstEvent['occurred_at']);

            // Build the session data
            $sessionData = [
                'id' => $this->sessionId,
                'started_at' => $startedAt,
                'ended_at' => $startedAt,
                'duration_seconds' => 0,
                'entry_page' => $firstEvent['page_path'] ?? null,
                'exit_page' => $firstEvent['page_path'] ?? null,
                'page_count' => 0,
                'event_count' => 0,
                'device_type' => $firstEvent['device_type'] ?? null,
                'country_code' => $firstEvent['country_code'] ?? null,
                'referrer' => $firstEvent['referrer'] ?? null,
                'is_bounce' => true,
                'converted' => false,
            ];

            // Only include user_id and visitor_id if they exist to avoid foreign key violations
            if ($this->userId) {
                $sessionData['user_id'] = $this->userId;
            }

            if ($this->visitorId) {
                // Check if visitor exists before including it
                $visitorExists = DB::table('visitors')
                    ->where('id', $this->visitorId)
                    ->exists();

                if ($visitorExists) {
                    $sessionData['visitor_id'] = $this->visitorId;
                }
            }

            // Create a minimal session record
            AnalyticsSession::create($sessionData);

            Log::info('Created analytics session', [
                'session_id' => $this->sessionId,
                'visitor_id' => $this->visitorId,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create analytics session', [
                'session_id' => $this->sessionId,
                'error' => $e->getMessage(),
            ]);
            // Don't rethrow - the ProcessSessions will handle updating the session
        }
    }

    /**
     * Enrich a raw event with server-derived context.
     *
     * @return array|null The enriched event, or null if validation fails
     */
    private function enrichEvent(array $event): ?array
    {
        // Validate required fields
        if (empty($event['event_id']) || empty($event['name'])) {
            Log::warning('Skipping invalid event', ['event' => $event]);

            return null;
        }

        // Convert occurred_at_ms to datetime
        $occurredAt = Carbon::createFromTimestampMs($event['occurred_at_ms'] ?? now()->getTimestampMs());

        return [
            'event_id' => $event['event_id'],
            'name' => $event['name'],
            'type' => AnalyticsEvent::deriveType($event['name']),
            'properties' => $event['properties'] ?? null,
            'visitor_id' => $this->visitorId,
            'user_id' => $this->userId,
            'session_id' => $this->sessionId,
            'source' => 'client',
            'page_path' => $this->pageContext['page_path'] ?? null,
            'referrer' => $this->pageContext['referrer'] ?? null,
            'device_type' => $this->pageContext['device_type'] ?? null,
            'browser' => null, // Could be parsed from user agent if needed
            'os' => null, // Could be parsed from user agent if needed
            'country_code' => $this->pageContext['country_code'] ?? null,
            'prompt_run_id' => $event['properties']['prompt_run_id'] ?? null,
            'occurred_at' => $occurredAt,
            'received_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Process domain-specific analytics from events
     *
     * Maps generic events to domain-specific analytics services
     */
    private function processDomainAnalytics(
        array $enrichedEvents,
        FrameworkSelectionService $frameworkService,
        QuestionAnalyticsService $questionService,
        WorkflowAnalyticsService $workflowService,
        PromptQualityService $qualityService,
    ): void {
        if (empty($enrichedEvents) || ! $this->visitorId) {
            return;
        }

        // Group events by prompt_run_id for efficient processing
        $eventsByPromptRun = collect($enrichedEvents)->groupBy('prompt_run_id');

        foreach ($eventsByPromptRun as $promptRunId => $events) {
            if (! $promptRunId) {
                continue;
            }

            $promptRun = PromptRun::find($promptRunId);
            if (! $promptRun) {
                continue;
            }

            foreach ($events as $event) {
                $this->processDomainEvent(
                    $event,
                    $promptRun,
                    $frameworkService,
                    $questionService,
                    $workflowService,
                    $qualityService,
                );
            }
        }
    }

    /**
     * Process a single domain event
     */
    private function processDomainEvent(
        array $event,
        PromptRun $promptRun,
        FrameworkSelectionService $frameworkService,
        QuestionAnalyticsService $questionService,
        WorkflowAnalyticsService $workflowService,
        PromptQualityService $qualityService,
    ): void {
        try {
            $eventName = $event['name'] ?? '';
            $properties = $event['properties'] ?? [];

            // Framework selection events
            if ($eventName === 'framework_recommended') {
                $frameworkService->recordSelection(
                    promptRun: $promptRun,
                    visitorId: $this->visitorId,
                    userId: $this->userId,
                    recommendedFramework: $properties['framework'] ?? 'unknown',
                    chosenFramework: $properties['framework'] ?? 'unknown',
                    recommendationScores: $properties['scores'] ?? [],
                    taskCategory: $properties['task_category'] ?? null,
                    personalityType: $properties['personality_type'] ?? null,
                );
            } elseif ($eventName === 'framework_selected') {
                // User chose a different framework than recommended
                $selection = $promptRun->frameworkSelections()
                    ->latest()
                    ->first();

                if ($selection && $selection->recommended_framework !== $properties['framework']) {
                    $selection->update(['chosen_framework' => $properties['framework']]);
                }
            }

            // Question presentation events
            elseif ($eventName === 'question_presented') {
                $questionService->recordPresentation(
                    promptRun: $promptRun,
                    visitorId: $this->visitorId,
                    userId: $this->userId,
                    questionId: $properties['question_id'] ?? 'unknown',
                    questionCategory: $properties['question_category'] ?? 'unknown',
                    personalityVariant: $properties['personality_variant'] ?? null,
                    displayOrder: $properties['display_order'] ?? 0,
                    wasRequired: $properties['was_required'] ?? false,
                );
            }

            // Question response events
            elseif ($eventName === 'question_answered') {
                // Find the latest question analytic for this question
                $analytic = $promptRun->questionAnalytics()
                    ->where('question_id', $properties['question_id'] ?? 'unknown')
                    ->latest()
                    ->first();

                if ($analytic) {
                    $questionService->recordResponse(
                        analytic: $analytic,
                        responseLength: strlen($properties['response'] ?? ''),
                        timeToAnswerMs: $properties['time_to_answer_ms'] ?? null,
                    );
                }
            }

            // Question skip events
            elseif ($eventName === 'question_skipped') {
                $analytic = $promptRun->questionAnalytics()
                    ->where('question_id', $properties['question_id'] ?? 'unknown')
                    ->latest()
                    ->first();

                if ($analytic) {
                    $questionService->recordSkip(
                        analytic: $analytic,
                        timeBeforeSkipMs: $properties['time_before_skip_ms'] ?? null,
                    );
                }
            }

            // Workflow events
            elseif ($eventName === 'workflow_started') {
                $workflowService->recordStart(
                    promptRun: $promptRun,
                    workflowStage: $properties['workflow_stage'] ?? 0,
                    workflowVersion: $properties['workflow_version'] ?? null,
                );
            } elseif ($eventName === 'workflow_completed') {
                $analytic = $promptRun->workflowAnalytics()
                    ->where('workflow_stage', $properties['workflow_stage'] ?? 0)
                    ->where('status', 'processing')
                    ->latest()
                    ->first();

                if ($analytic) {
                    $workflowService->recordCompletion(
                        analytic: $analytic,
                        inputTokens: $properties['input_tokens'] ?? null,
                        outputTokens: $properties['output_tokens'] ?? null,
                        estimatedCostUsd: $properties['estimated_cost_usd'] ?? null,
                        modelUsed: $properties['model_used'] ?? null,
                    );
                }
            } elseif ($eventName === 'workflow_failed') {
                $analytic = $promptRun->workflowAnalytics()
                    ->where('workflow_stage', $properties['workflow_stage'] ?? 0)
                    ->where('status', 'processing')
                    ->latest()
                    ->first();

                if ($analytic) {
                    $workflowService->recordFailure(
                        analytic: $analytic,
                        errorCode: $properties['error_code'] ?? 'UNKNOWN',
                        errorMessage: $properties['error_message'] ?? 'Unknown error',
                        inputTokens: $properties['input_tokens'] ?? null,
                        outputTokens: $properties['output_tokens'] ?? null,
                    );
                }
            } elseif ($eventName === 'workflow_timeout') {
                $analytic = $promptRun->workflowAnalytics()
                    ->where('workflow_stage', $properties['workflow_stage'] ?? 0)
                    ->where('status', 'processing')
                    ->latest()
                    ->first();

                if ($analytic) {
                    $workflowService->recordTimeout(analytic: $analytic);
                }
            }

            // Prompt quality events
            elseif ($eventName === 'prompt_rated') {
                $qualityService->recordMetrics(
                    promptRun: $promptRun,
                    userRating: $properties['rating'] ?? null,
                    taskCategory: $properties['task_category'] ?? null,
                    frameworkUsed: $properties['framework_used'] ?? null,
                    personalityType: $properties['personality_type'] ?? null,
                );
            } elseif ($eventName === 'prompt_copied') {
                $qualityService->recordMetrics(
                    promptRun: $promptRun,
                    wasCopied: true,
                    copyCount: ($properties['copy_count'] ?? 1),
                );
            } elseif ($eventName === 'prompt_edited') {
                $qualityService->recordMetrics(
                    promptRun: $promptRun,
                    wasEdited: true,
                    editPercentage: $properties['edit_percentage'] ?? null,
                    promptLength: $properties['original_length'] ?? null,
                );
            } elseif ($eventName === 'prompt_completed') {
                $qualityService->recordMetrics(
                    promptRun: $promptRun,
                    timeToCompleteMs: $properties['time_to_complete_ms'] ?? null,
                );
            }
        } catch (\Exception $e) {
            Log::error('Failed to process domain event', [
                'event_name' => $event['name'] ?? 'unknown',
                'prompt_run_id' => $promptRun->id,
                'error' => $e->getMessage(),
            ]);
            // Don't rethrow - continue processing other events
        }
    }

    /**
     * Process funnel progression from enriched events
     */
    private function processFunnels(
        array $enrichedEvents,
        FunnelProcessingService $funnelService,
    ): void {
        if (empty($enrichedEvents) || ! $this->visitorId) {
            return;
        }

        foreach ($enrichedEvents as $event) {
            try {
                // Build event data for funnel service (includes tier for subscription events)
                $eventData = $event['properties'] ?? [];

                $funnelService->processEvent(
                    visitorId: $this->visitorId,
                    eventName: $event['name'],
                    eventData: $eventData,
                );
            } catch (\Exception $e) {
                Log::error('Failed to process funnel event', [
                    'event_name' => $event['name'] ?? 'unknown',
                    'visitor_id' => $this->visitorId,
                    'error' => $e->getMessage(),
                ]);
                // Don't rethrow - continue processing other events
            }
        }
    }

    /**
     * Batch insert events with idempotency (upsert on event_id conflict)
     */
    private function batchInsertEvents(array $events): void
    {
        $prepared = array_map(function (array $event) {
            $event['properties'] = $this->jsonEncodeProperties($event['properties'] ?? null);

            return $event;
        }, $events);

        // Use insertOrIgnore to prevent duplicate event_ids
        // This achieves idempotency: if the same event_id is sent multiple times,
        // only the first insert succeeds
        DB::table('analytics_events')->insertOrIgnore($prepared);
    }

    private function jsonEncodeProperties(mixed $properties): ?string
    {
        if ($properties === null) {
            return null;
        }

        if (is_string($properties)) {
            return $properties;
        }

        return json_encode($properties, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
