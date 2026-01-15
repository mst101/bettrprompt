import { useCookieConsent } from '@/Composables/features/useCookieConsent';
import { isAnalyticsBlockedPath } from '@/Utils/analyticsGuard';
import { analyticsSessionService } from './analyticsSession';

export interface AnalyticsEvent {
    event_id?: string; // Generated client-side
    name: string;
    occurred_at_ms?: number;
    properties?: Record<string, unknown>;
}

interface QueuedEvent extends AnalyticsEvent {
    event_id: string;
    occurred_at_ms: number;
}

/**
 * Service for tracking analytics events
 * Batches events and sends them to the backend with consent gating
 */
export class AnalyticsService {
    private eventQueue: QueuedEvent[] = [];
    private batchTimeout: number | null = null;
    private readonly BATCH_SIZE = 10;
    private readonly BATCH_DELAY_MS = 5000; // 5 seconds
    private readonly API_ENDPOINT = '/api/analytics/events';

    /**
     * Track an analytics event
     * Event is queued and sent in a batch
     * Only sends if analytics consent is granted
     */
    track(event: AnalyticsEvent): void {
        const { hasConsentFor } = useCookieConsent();

        // Don't track without analytics consent
        if (!hasConsentFor('analytics')) {
            return;
        }

        if (
            typeof window !== 'undefined' &&
            isAnalyticsBlockedPath(window.location.pathname)
        ) {
            return;
        }

        // Enrich event with defaults
        const queuedEvent: QueuedEvent = {
            ...event,
            event_id: event.event_id || crypto.randomUUID(),
            occurred_at_ms: event.occurred_at_ms || Date.now(),
        };

        this.eventQueue.push(queuedEvent);

        // Send batch if we've reached the batch size
        if (this.eventQueue.length >= this.BATCH_SIZE) {
            this.flushBatch();
        } else {
            // Otherwise, schedule a flush
            this.scheduleBatchFlush();
        }
    }

    /**
     * Flush any queued events immediately
     */
    async flushBatch(): Promise<void> {
        if (this.batchTimeout !== null) {
            clearTimeout(this.batchTimeout);
            this.batchTimeout = null;
        }

        if (this.eventQueue.length === 0) {
            return;
        }

        const events = [...this.eventQueue];
        this.eventQueue = [];

        try {
            const sessionId = analyticsSessionService.getSessionId();

            // Build headers
            const headers: Record<string, string> = {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            };

            if (sessionId) {
                headers['X-Analytics-Session-Id'] = sessionId;
            }

            // Send to backend
            const response = await fetch(this.API_ENDPOINT, {
                method: 'POST',
                headers,
                body: JSON.stringify({ events }),
                credentials: 'same-origin',
            });

            if (!response.ok) {
                console.warn(
                    `Analytics batch failed with status ${response.status}`,
                );
                // Optionally re-queue events on failure
            }
        } catch (error) {
            console.warn('Failed to send analytics batch:', error);
            // Optionally re-queue events on failure
        }
    }

    /**
     * Schedule a batch flush if one isn't already scheduled
     */
    private scheduleBatchFlush(): void {
        if (this.batchTimeout !== null) {
            return; // Already scheduled
        }

        this.batchTimeout = window.setTimeout(() => {
            this.batchTimeout = null;
            this.flushBatch();
        }, this.BATCH_DELAY_MS);
    }

    /**
     * Flush events when page is unloading (optional, improves reliability)
     * Uses sendBeacon API which doesn't support custom headers,
     * so session ID is not included in unload events
     */
    enablePageUnloadFlushing(): void {
        if (typeof window === 'undefined') return;

        // Flush on page unload/close
        window.addEventListener('beforeunload', () => {
            const events = this.eventQueue;

            if (events.length === 0) return;

            const data = JSON.stringify({ events });

            if (navigator.sendBeacon) {
                navigator.sendBeacon(this.API_ENDPOINT, data);
            }
        });
    }
}

// Export singleton instance
export const analyticsService = new AnalyticsService();

// Enable page unload flushing
if (typeof window !== 'undefined') {
    analyticsService.enablePageUnloadFlushing();
}
