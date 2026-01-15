<?php

namespace App\Services;

use App\Models\AnalyticsSession;
use App\Models\Visitor;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SessionProcessorService
{
    /**
     * Session timeout: if more than 30 minutes without activity, it's a new session
     * Note: We use analytics_session_id from header, so timeout isn't strictly needed,
     * but we use it to close abandoned sessions
     */
    private const SESSION_TIMEOUT_MINUTES = 30;

    /**
     * Process events to build or update analytics_sessions
     *
     * A session encompasses:
     * - All events with the same session_id
     * - From the same visitor_id
     * - With timing information
     * - Entry/exit page tracking
     * - Bounce detection (single page visit)
     */
    public function processSessionEvents(array $events, array $pageContext = []): void
    {
        // Group events by session_id and visitor_id
        $sessionGroups = collect($events)->groupBy(function ($event) {
            return $event['session_id'] ?? 'no_session';
        });

        foreach ($sessionGroups as $sessionId => $sessionEvents) {
            if ($sessionId === 'no_session') {
                continue; // Skip events without session ID
            }

            $this->processSession($sessionId, $sessionEvents->all(), $pageContext);
        }
    }

    /**
     * Process a single session's worth of events
     */
    private function processSession(string $sessionId, array $events, array $pageContext = []): void
    {
        if (empty($events)) {
            return;
        }

        // Sort events by timestamp
        usort($events, function ($a, $b) {
            $timeA = strtotime($a['occurred_at']);
            $timeB = strtotime($b['occurred_at']);

            return $timeA <=> $timeB;
        });

        $firstEvent = reset($events);
        $lastEvent = end($events);

        $visitorId = $firstEvent['visitor_id'];
        $userId = $firstEvent['user_id'] ?? null;
        $startedAt = Carbon::parse($firstEvent['occurred_at']);
        $endedAt = Carbon::parse($lastEvent['occurred_at']);
        // Ensure duration is never negative and cast to int (column is unsignedInteger)
        $duration = max(0, (int) abs($endedAt->diffInSeconds($startedAt)));

        // Load visitor for fallback utm data (if not in current request)
        $visitor = $visitorId ? Visitor::find($visitorId) : null;

        // Find entry and exit pages
        $entryPage = null;
        $exitPage = null;
        $pageCount = 0;

        foreach ($events as $event) {
            if ($event['name'] === 'page_view') {
                $pagePath = $event['properties']['path'] ?? $event['page_path'] ?? null;
                $pagePath = $this->extractPath($pagePath);
                if ($entryPage === null) {
                    $entryPage = $pagePath;
                }
                $exitPage = $pagePath;
                $pageCount++;
            }
        }

        // Detect bounce: single page view
        $isBounce = $pageCount <= 1;

        // Check for conversions in this session
        $conversions = collect($events)->filter(fn ($e) => $e['type'] === 'conversion');
        $hasConverted = $conversions->isNotEmpty();
        $conversionType = $this->determineConversionType($conversions);

        // Count prompts
        $promptsStarted = collect($events)->filter(fn ($e) => $e['name'] === 'prompt_started')->count();
        $promptsCompleted = collect($events)
            ->filter(fn ($e) => $e['name'] === 'prompt_completed')
            ->count();

        // Find or create session
        $session = AnalyticsSession::firstOrNew(
            ['id' => $sessionId],
        );

        $session->fill([
            'visitor_id' => $visitorId,
            'user_id' => $userId,
            'started_at' => $startedAt,
            'ended_at' => $endedAt,
            'duration_seconds' => $duration,
            'entry_page' => $entryPage,
            'exit_page' => $exitPage,
            'page_count' => $pageCount,
            'event_count' => count($events),
            'is_bounce' => $isBounce,
            'converted' => $hasConverted,
            'conversion_type' => $conversionType,
            'prompts_started' => $promptsStarted,
            'prompts_completed' => $promptsCompleted,
            // Attribution from current session (utm params from current visit, not visitor's original ones)
            'utm_source' => $pageContext['utm_source'] ?? null,
            'utm_medium' => $pageContext['utm_medium'] ?? null,
            'utm_campaign' => $pageContext['utm_campaign'] ?? null,
            'referrer' => $firstEvent['referrer'] ?? null,
            'device_type' => $firstEvent['device_type'] ?? null,
        ]);

        try {
            $session->save();

            Log::info('Session processed', [
                'session_id' => $sessionId,
                'visitor_id' => $visitorId,
                'visitor_found' => $visitor ? true : false,
                'page_count' => $pageCount,
                'converted' => $hasConverted,
                'utm_source' => $session->utm_source,
                'utm_medium' => $session->utm_medium,
                'utm_campaign' => $session->utm_campaign,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to process session', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Determine conversion type from conversion events
     */
    private function determineConversionType($conversions): ?string
    {
        // Priority: subscription > registration > other
        foreach ($conversions as $event) {
            if (str_contains($event['name'], 'subscription')) {
                return 'subscribed_'.($event['properties']['tier'] ?? 'unknown');
            }
        }

        foreach ($conversions as $event) {
            if (str_contains($event['name'], 'registration')) {
                return 'registered';
            }
        }

        return $conversions->first()?->name ?? null;
    }

    /**
     * Extract the path component from a URL if possible.
     */
    private function extractPath(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        $path = parse_url($value, PHP_URL_PATH);

        return is_string($path) && $path !== '' ? $path : $value;
    }
}
