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
        };

        console.log('[Echo Init] Configuration:', {
            wsHost: reverbConfig.wsHost,
            wsPort: reverbConfig.wsPort,
            wssPort: reverbConfig.wssPort,
            forceTLS: reverbConfig.forceTLS,
            scheme: import.meta.env.VITE_REVERB_SCHEME,
        });

        echoInstance = new Echo(reverbConfig);

        // Access underlying Pusher connection
        const pusher = echoInstance.connector.pusher;

        console.log('[Echo Init] Pusher instance created:', {
            options: pusher.options,
            state: pusher.connection.state,
        });

        // Hook into Pusher's message event to log ALL messages
        pusher.bind('message', (data: any) => {
            console.log('[Pusher Message Event]', {
                event: data.event,
                channel: data.channel,
                dataPreview:
                    typeof data.data === 'string'
                        ? data.data.substring(0, 100)
                        : JSON.stringify(data.data).substring(0, 100),
                fullData: data,
            });
        });
        console.log('[Echo Init] Installed Pusher message event logger');

        // Log the connection info including socket ID
        console.log('[Echo Init] Pusher connection info:', {
            socketId: pusher.connection.socket_id,
            state: pusher.connection.state,
            transport: pusher.connection.transport,
        });

        // Handle connection events
        pusher.connection.bind('connected', () => {
            console.log('[Echo] WebSocket connected successfully');
            console.log('[Echo] Pusher connection info after connect:', {
                socketId: pusher.connection.socket_id,
                state: pusher.connection.state,
            });
            connectionState = 'connected';
            window.dispatchEvent(new CustomEvent('echo-connected'));

            // Also try to hook into the socket's onmessage if available
            if (pusher.connection.socket) {
                console.log(
                    '[Echo Init] WebSocket socket is available, type:',
                    typeof pusher.connection.socket,
                );
                const originalOnMessage = pusher.connection.socket.onmessage;
                pusher.connection.socket.onmessage = function (event: Event) {
                    const wsEvent = event as MessageEvent;
                    try {
                        const data = JSON.parse(wsEvent.data);
                        console.log('[WebSocket onmessage Raw]', {
                            event: data.event,
                            channel: data.channel,
                            fullData: data,
                        });
                    } catch {
                        console.log(
                            '[WebSocket onmessage] (non-JSON):',
                            wsEvent.data,
                        );
                    }
                    // Call original handler
                    if (originalOnMessage) {
                        return originalOnMessage.call(this, event);
                    }
                };
                console.log(
                    '[Echo Init] Installed raw WebSocket onmessage logger',
                );
            }
        });

        pusher.connection.bind('connecting', () => {
            console.log('[Echo] WebSocket connecting...');
            connectionState = 'connecting';
        });

        pusher.connection.bind('disconnected', () => {
            console.warn('[Echo] WebSocket disconnected');
            connectionState = 'disconnected';
            window.dispatchEvent(new CustomEvent('echo-disconnected'));
        });

        pusher.connection.bind('failed', () => {
            console.error('[Echo] WebSocket connection failed');
            console.error('[Echo] Connection state:', pusher.connection.state);
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
        console.log('[Echo Init] Echo initialized successfully');
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
