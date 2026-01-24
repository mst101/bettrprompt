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

        $firstEvent = array_first($events);
        $lastEvent = array_last($events);

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

        foreach ($events as $event) {
            if ($event['name'] === 'page_view') {
                $pagePath = $event['properties']['path'] ?? $event['page_path'] ?? null;
                $pagePath = $this->extractPath($pagePath);
                if ($entryPage === null) {
                    $entryPage = $pagePath;
                }
                $exitPage = $pagePath;
            }
        }

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
            // Attribution from visitor's current utm (updated on each visit when utm params present)
            'utm_source' => $visitor?->current_utm_source,
            'utm_medium' => $visitor?->current_utm_medium,
            'utm_campaign' => $visitor?->current_utm_campaign,
            'utm_term' => $visitor?->current_utm_term,
            'utm_content' => $visitor?->current_utm_content,
            'referrer' => $visitor?->referrer,
            'device_type' => $firstEvent['device_type'] ?? null,
        ]);

        try {
            $session->save();

            Log::info('Session processed', [
                'session_id' => $sessionId,
                'visitor_id' => $visitorId,
                'visitor_found' => $visitor ? true : false,
                'is_bounce' => $session->isBounce(),
                'converted' => $session->isConverted(),
                'utm_source' => $session->utm_source,
                'utm_medium' => $session->utm_medium,
                'utm_campaign' => $session->utm_campaign,
                'utm_term' => $session->utm_term,
                'utm_content' => $session->utm_content,
                'referrer' => $session->referrer,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to process session', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Extract the path component from a URL if possible.
     */
    private function extractPath(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        try {
            $uri = new \PHP\URI\URI($value);

            return $uri->path && $uri->path !== '' ? $uri->path : $value;
        } catch (\Exception) {
            return $value;
        }
    }
}
