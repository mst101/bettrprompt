<?php

namespace App\Jobs;

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
    public function handle(): void
    {
        try {
            Log::info('Processing analytics events', [
                'event_count' => count($this->events),
                'visitor_id' => $this->visitorId,
                'user_id' => $this->userId,
            ]);

            $eventsToInsert = [];

            foreach ($this->events as $event) {
                $enrichedEvent = $this->enrichEvent($event);

                if ($enrichedEvent) {
                    $eventsToInsert[] = $enrichedEvent;
                }
            }

            // Batch insert with upsert semantics (ignore duplicates)
            if (! empty($eventsToInsert)) {
                $this->batchInsertEvents($eventsToInsert);
            }

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
            'type' => $this->deriveEventType($event['name']),
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
     * Derive event type from event name
     */
    private function deriveEventType(string $name): string
    {
        return match (true) {
            str_contains($name, 'success') || str_contains($name, 'completed') => 'conversion',
            str_contains($name, 'exposure') => 'exposure',
            str_contains($name, 'failed') || str_contains($name, 'error') => 'error',
            str_contains($name, 'consent') => 'system',
            str_contains($name, 'session') => 'system',
            default => 'engagement',
        };
    }

    /**
     * Batch insert events with idempotency (upsert on event_id conflict)
     */
    private function batchInsertEvents(array $events): void
    {
        // Use insertOrIgnore to prevent duplicate event_ids
        // This achieves idempotency: if the same event_id is sent multiple times,
        // only the first insert succeeds
        DB::table('analytics_events')->insertOrIgnore($events);
    }
}
