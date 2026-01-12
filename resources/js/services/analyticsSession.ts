import { useCookieConsent } from '@/Composables/features/useCookieConsent';

const SESSION_STORAGE_KEY = 'analytics_session_id';

/**
 * Service for managing analytics session IDs
 * Session IDs are only created after analytics consent is granted
 */
export class AnalyticsSessionService {
    private sessionId: string | null = null;

    constructor() {
        // Initialize session ID from sessionStorage if it exists
        if (typeof window !== 'undefined') {
            this.sessionId = this.getStoredSessionId();
        }
    }

    /**
     * Get or create a session ID
     * Only creates a new session ID if analytics consent is granted
     */
    getSessionId(): string | null {
        const { hasConsentFor } = useCookieConsent();

        // Don't create or return session ID without analytics consent
        if (!hasConsentFor('analytics')) {
            return null;
        }

        // Return existing session ID
        if (this.sessionId) {
            return this.sessionId;
        }

        // Create new session ID
        this.sessionId = crypto.randomUUID();
        this.storeSessionId(this.sessionId);

        return this.sessionId;
    }

    /**
     * Get the current session ID without creating one
     */
    getCurrentSessionId(): string | null {
        return this.sessionId;
    }

    /**
     * Clear the session ID (e.g., on consent revocation)
     */
    clearSessionId(): void {
        this.sessionId = null;
        if (typeof window !== 'undefined') {
            sessionStorage.removeItem(SESSION_STORAGE_KEY);
        }
    }

    /**
     * Retrieve session ID from sessionStorage
     */
    private getStoredSessionId(): string | null {
        try {
            return sessionStorage.getItem(SESSION_STORAGE_KEY);
        } catch (e) {
            console.warn(
                'Failed to read analytics session ID from sessionStorage:',
                e,
            );
            return null;
        }
    }

    /**
     * Store session ID in sessionStorage
     */
    private storeSessionId(sessionId: string): void {
        try {
            sessionStorage.setItem(SESSION_STORAGE_KEY, sessionId);
        } catch (e) {
            console.warn(
                'Failed to store analytics session ID in sessionStorage:',
                e,
            );
        }
    }
}

// Export singleton instance
export const analyticsSessionService = new AnalyticsSessionService();
