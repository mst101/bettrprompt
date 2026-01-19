import { analyticsService } from '@/services/analytics';
import type { App } from 'vue';

interface ErrorTrackerOptions {
    debounceMs?: number;
    maxStackTraceLength?: number;
    enableConsole?: boolean;
}

/**
 * Global error tracking plugin for analytics
 * Captures uncaught JavaScript errors and tracks them via analytics service
 *
 * Prevents error flooding via debouncing similar errors
 * Filters out known non-errors and intentional errors
 */
export function createErrorTrackingPlugin(options: ErrorTrackerOptions = {}) {
    const {
        debounceMs = 5000,
        maxStackTraceLength = 2000,
        enableConsole = true,
    } = options;

    const recentErrors = new Map<string, number>();

    /**
     * Generate a hash for error deduplication
     * Errors with same type + message are considered duplicates
     */
    function hashError(type: string, message: string): string {
        // Simple hash: combine type and message for deduplication
        return `${type}:${message}`;
    }

    /**
     * Check if this error was recently tracked
     * Returns true if we should skip tracking (debouncing)
     */
    function shouldSkipError(hash: string): boolean {
        const lastTrackedTime = recentErrors.get(hash);

        if (!lastTrackedTime) {
            recentErrors.set(hash, Date.now());
            return false;
        }

        const timeSinceLastTrack = Date.now() - lastTrackedTime;

        if (timeSinceLastTrack > debounceMs) {
            recentErrors.set(hash, Date.now());
            return false;
        }

        return true;
    }

    /**
     * Determine if error should be tracked
     * Filters out known non-errors and expected failures
     */
    function shouldTrackError(
        type: string,
        message: string,
        filename?: string,
    ): boolean {
        // Filter out NotFound and ChunkLoadError (expected, not real errors)
        if (
            type === 'ChunkLoadError' ||
            message.includes('Failed to load chunk') ||
            type === 'NotFound'
        ) {
            return false;
        }

        // Filter out AbortError (user cancelled operation)
        if (type === 'AbortError') {
            return false;
        }

        // Filter out network errors (handled separately by network error handlers)
        if (
            type === 'NetworkError' ||
            message.includes('NetworkError') ||
            message.includes('Failed to fetch')
        ) {
            // Only track if it's in an unexpected context
            // (User intentionally aborts requests frequently)
            return false;
        }

        // Filter out ResizeObserver errors (usually benign, happens in many browsers)
        if (
            message.includes('ResizeObserver') ||
            filename?.includes('ResizeObserver')
        ) {
            return false;
        }

        // Track everything else
        return true;
    }

    /**
     * Extract stack trace and truncate if needed
     */
    function extractStackTrace(stack?: string): string | undefined {
        if (!stack) {
            return undefined;
        }

        // Truncate stack trace if too long
        if (stack.length > maxStackTraceLength) {
            return stack.substring(0, maxStackTraceLength) + '... [truncated]';
        }

        return stack;
    }

    /**
     * Track error via analytics
     */
    function trackError(type: string, message: string, stack?: string): void {
        // Only track if consent is granted (analytics service handles this)
        analyticsService.track({
            name: 'client_error',
            properties: {
                error_type: type,
                message: message,
                stack: extractStackTrace(stack),
            },
        });

        if (enableConsole) {
            console.error('[Analytics] Tracked error:', {
                type,
                message,
                hasStack: !!stack,
            });
        }
    }

    return {
        install(app: App) {
            /**
             * Vue error handler
             * Catches errors in Vue components
             */
            app.config.errorHandler = (err, instance, info) => {
                const type = (err as Error)?.constructor?.name || 'Error';
                const message = (err as Error)?.message || String(err);
                const stack = (err as Error)?.stack;

                if (enableConsole) {
                    console.error('Vue error:', { type, message, info });
                }

                if (
                    shouldTrackError(type, message) &&
                    !shouldSkipError(hashError(type, message))
                ) {
                    trackError(type, message, stack);
                }

                // Don't suppress error - let Vue's default handler run
            };

            /**
             * Global error handler for uncaught errors
             */
            const handleError = (event: ErrorEvent) => {
                const { error, filename } = event;

                const type = error?.constructor?.name || 'UnknownError';
                const message = error?.message || String(error);
                const stack = error?.stack;

                if (
                    shouldTrackError(type, message, filename) &&
                    !shouldSkipError(hashError(type, message))
                ) {
                    trackError(type, message, stack);
                }

                // Return false to let browser's default error handling continue
                return false;
            };

            window.addEventListener('error', handleError);

            /**
             * Unhandled promise rejection handler
             */
            const handleUnhandledRejection = (event: PromiseRejectionEvent) => {
                const { reason } = event;

                // Extract error details from rejected promise
                const type =
                    reason?.constructor?.name || 'UnhandledPromiseRejection';
                const message = reason?.message || String(reason);
                const stack = reason?.stack;

                if (
                    shouldTrackError(type, message) &&
                    !shouldSkipError(hashError(type, message))
                ) {
                    trackError(type, message, stack);
                }

                // Return false to let browser's default rejection handling continue
                return false;
            };

            window.addEventListener(
                'unhandledrejection',
                handleUnhandledRejection,
            );

            // Cleanup function (if app is unmounted)
            app.config.globalProperties.$errorTracking = {
                dispose: () => {
                    window.removeEventListener('error', handleError);
                    window.removeEventListener(
                        'unhandledrejection',
                        handleUnhandledRejection,
                    );
                },
            };
        },
    };
}
