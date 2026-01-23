/**
 * Environment-aware logger utility
 * Only outputs console messages in development mode
 * In production, all console.* calls are silenced
 */

const isDevelopment = import.meta.env.DEV;

export const logger = {
    /**
     * Log debug information (development only)
     */
    debug: (...args: unknown[]) => {
        if (isDevelopment) {
            console.log('[DEBUG]', ...args);
        }
    },

    /**
     * Log info messages (development only)
     */
    info: (...args: unknown[]) => {
        if (isDevelopment) {
            console.info('[INFO]', ...args);
        }
    },

    /**
     * Log warning messages (always shown)
     */
    warn: (...args: unknown[]) => {
        console.warn('[WARN]', ...args);
    },

    /**
     * Log error messages (always shown)
     */
    error: (...args: unknown[]) => {
        console.error('[ERROR]', ...args);
    },
};
