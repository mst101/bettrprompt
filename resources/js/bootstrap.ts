import { isAnalyticsBlockedPath } from '@/Utils/analyticsGuard';
import axios from 'axios';
import { analyticsSessionService } from './services/analyticsSession';

window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

/**
 * Add analytics session ID to all requests
 * The session ID is optional and only added if analytics consent is granted
 */
window.axios.interceptors.request.use((config) => {
    if (
        typeof window !== 'undefined' &&
        isAnalyticsBlockedPath(window.location.pathname)
    ) {
        return config;
    }

    const sessionId = analyticsSessionService.getCurrentSessionId();
    if (sessionId) {
        config.headers['X-Analytics-Session-Id'] = sessionId;
    }
    return config;
});

/**
 * Laravel Echo for real-time broadcasting with comprehensive error handling
 * Deferred initialization - only loaded when prompt-builder or authenticated pages need it
 */

// Track connection state
let echoInstance: Echo<unknown> | null = null;
let connectionState: 'connected' | 'connecting' | 'disconnected' | 'failed' =
    'disconnected';
let echoInitialized = false;

// Lazy imports - only loaded when needed
async function initializeEcho() {
    // Return early if already initialized
    if (echoInitialized) {
        return;
    }
    echoInitialized = true;

    try {
        // Import only when needed
        const { default: Echo } = await import('laravel-echo');
        const { default: Pusher } = await import('pusher-js');

        window.Pusher = Pusher;
        // Only initialize Echo if we have a valid app key
        if (!import.meta.env.VITE_REVERB_APP_KEY) {
            connectionState = 'disconnected';
            window.Echo = null;
            return;
        }

        connectionState = 'connecting';

        const reverbConfig = {
            broadcaster: 'reverb',
            key: import.meta.env.VITE_REVERB_APP_KEY,
            wsHost: import.meta.env.VITE_REVERB_HOST,
            wsPort: Number(import.meta.env.VITE_REVERB_PORT) || 80,
            wssPort: Number(import.meta.env.VITE_REVERB_PORT) || 443,
            forceTLS: import.meta.env.VITE_REVERB_SCHEME === 'https',
            enabledTransports: ['ws', 'wss'],
            enableLogging: import.meta.env.DEV,
            authEndpoint: '/broadcasting/auth',
            namespace: null,
        };

        echoInstance = new Echo(reverbConfig);

        // Access underlying Pusher connection
        const pusher = echoInstance.connector.pusher;

        pusher.connection.bind('connected', () => {
            connectionState = 'connected';
            window.dispatchEvent(new CustomEvent('echo-connected'));
        });

        pusher.connection.bind('connecting', () => {
            connectionState = 'connecting';
        });

        pusher.connection.bind('disconnected', () => {
            console.warn('[Echo] WebSocket disconnected');
            connectionState = 'disconnected';
            window.dispatchEvent(new CustomEvent('echo-disconnected'));
        });

        pusher.connection.bind('failed', () => {
            console.error('[Echo] WebSocket connection failed');
            connectionState = 'failed';
            window.dispatchEvent(new CustomEvent('echo-failed'));
        });

        pusher.connection.bind('error', (error: unknown) => {
            console.error('[Echo] WebSocket error:', error);
        });

        pusher.connection.bind('unavailable', () => {
            console.warn('[Echo] WebSocket unavailable, will retry...');
        });

        window.Echo = echoInstance;
    } catch (error) {
        console.error('[Echo Init] Failed to initialize Echo:', error);
        connectionState = 'failed';
        window.Echo = null;
        window.dispatchEvent(new CustomEvent('echo-failed'));
    }
}

// Helper to check if Echo is available and connected
window.isEchoConnected = () => {
    return connectionState === 'connected' && window.Echo !== null;
};

// Helper to get current connection state
window.getEchoConnectionState = () => {
    return connectionState;
};

// Echo is now deferred - will be initialized when needed (prompt-builder, real-time pages)
// Don't initialize on app startup to avoid blocking the main thread

// Export for use in components
export async function initializeEchoIfNeeded(): Promise<void> {
    return initializeEcho();
}

export function getEcho(): Echo<unknown> | null {
    return echoInstance;
}

export function getEchoConnectionState() {
    return connectionState;
}
