import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

/**
 * Laravel Echo for real-time broadcasting with comprehensive error handling
 */
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

// Track connection state
let echoInstance: Echo<unknown> | null = null;
let connectionState: 'connected' | 'connecting' | 'disconnected' | 'failed' =
    'connecting';

// Initialize Echo with error handling
function initializeEcho() {
    try {
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

// Initialize Echo
initializeEcho();

// Export for use in components
export function getEcho(): Echo<unknown> | null {
    return echoInstance;
}

export function getEchoConnectionState() {
    return connectionState;
}
