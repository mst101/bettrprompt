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
let echoInstance: Echo<any> | null = null;
let connectionState: 'connected' | 'connecting' | 'disconnected' | 'failed' =
    'connecting';

// Initialize Echo with error handling
function initializeEcho() {
    try {
        echoInstance = new Echo({
            broadcaster: 'reverb',
            key: import.meta.env.VITE_REVERB_APP_KEY,
            wsHost: window.location.hostname,
            wsPort: import.meta.env.VITE_REVERB_SCHEME === 'https' ? 443 : 80,
            wssPort: 443,
            forceTLS: window.location.protocol === 'https:',
            enabledTransports: ['ws', 'wss'],
            enableLogging: import.meta.env.DEV,
            authEndpoint: '/broadcasting/auth',
        });

        // Access underlying Pusher connection
        const pusher = echoInstance.connector.pusher;

        // Handle connection events
        pusher.connection.bind('connected', () => {
            console.log('WebSocket connected');
            connectionState = 'connected';
            window.dispatchEvent(new CustomEvent('echo-connected'));
        });

        pusher.connection.bind('connecting', () => {
            console.log('WebSocket connecting...');
            connectionState = 'connecting';
        });

        pusher.connection.bind('disconnected', () => {
            console.warn('WebSocket disconnected');
            connectionState = 'disconnected';
            window.dispatchEvent(new CustomEvent('echo-disconnected'));
        });

        pusher.connection.bind('failed', () => {
            console.error('WebSocket connection failed');
            connectionState = 'failed';
            window.dispatchEvent(new CustomEvent('echo-failed'));
        });

        pusher.connection.bind('error', (error: any) => {
            console.error('WebSocket error', error);
        });

        pusher.connection.bind('unavailable', () => {
            console.warn('WebSocket unavailable, will retry...');
        });

        window.Echo = echoInstance;
    } catch (error) {
        console.error('Failed to initialize Echo', error);
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
export function getEcho(): Echo<any> | null {
    return echoInstance;
}

export function getEchoConnectionState() {
    return connectionState;
}
