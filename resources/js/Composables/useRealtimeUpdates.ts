import { router } from '@inertiajs/vue3';
import type { Channel } from 'laravel-echo';
import { onMounted, onUnmounted, ref } from 'vue';

interface ReloadOptions {
    only?: string[];
}

interface EventHandlers {
    [eventName: string]: (data: unknown) => void;
}

/**
 * Composable for real-time updates via Laravel Echo/WebSockets
 * with automatic fallback to polling if WebSockets fail
 *
 * @param channelName - Echo channel name
 * @param events - Event handlers map
 * @param reloadOptions - Inertia reload options for polling fallback
 * @param pollingInterval - Polling interval in milliseconds (default: 5000)
 *
 * @example
 * const { connected, usingFallback } = useRealtimeUpdates(
 *     `prompt-run.${promptRunId}`,
 *     {
 *         'FrameworkSelected': (data) => console.log('Framework selected', data),
 *         'PromptOptimizationCompleted': (data) => router.reload(),
 *     },
 *     { only: ['promptRun', 'progress'] }
 * );
 */
export function useRealtimeUpdates(
    channelName: string,
    events: EventHandlers,
    reloadOptions?: ReloadOptions,
    pollingInterval: number = 5000,
) {
    const connected = ref(false);
    const usingFallback = ref(false);
    let pollInterval: number | null = null;
    let channel: Channel | null = null;

    const startPolling = () => {
        if (pollInterval) return; // Already polling

        console.log(
            `[useRealtimeUpdates] Starting polling fallback for ${channelName}`,
        );
        usingFallback.value = true;

        pollInterval = window.setInterval(() => {
            router.reload(reloadOptions);
        }, pollingInterval);
    };

    const stopPolling = () => {
        if (pollInterval) {
            clearInterval(pollInterval);
            pollInterval = null;
            usingFallback.value = false;
            console.log('[useRealtimeUpdates] Stopped polling fallback');
        }
    };

    const setupEcho = () => {
        try {
            channel = window.Echo?.channel(channelName);

            if (!channel) {
                throw new Error('Echo channel could not be created');
            }

            // Set up event listeners
            Object.entries(events).forEach(([eventName, handler]) => {
                // Event names from broadcastAs() are used directly without a dot prefix
                const echoEventName = eventName;
                channel!.listen(echoEventName, (data: unknown) => {
                    try {
                        console.log(
                            `[useRealtimeUpdates] Event: ${eventName}`,
                            data,
                        );
                        handler(data);
                    } catch (error) {
                        console.error(
                            `[useRealtimeUpdates] Error handling ${eventName}:`,
                            error,
                        );
                    }
                });
            });

            // Handle channel errors
            channel.error((error: Error) => {
                console.error(
                    '[useRealtimeUpdates] WebSocket channel error:',
                    error,
                );
                if (!usingFallback.value) {
                    console.warn(
                        '[useRealtimeUpdates] Falling back to polling due to channel error',
                    );
                    startPolling();
                }
            });

            connected.value = true;
            console.log(
                `[useRealtimeUpdates] Connected to channel: ${channelName}`,
            );
        } catch (error) {
            console.error('[useRealtimeUpdates] Failed to set up Echo:', error);
            startPolling();
        }
    };

    const handleEchoDisconnect = () => {
        if (!usingFallback.value) {
            console.warn(
                '[useRealtimeUpdates] Echo disconnected, falling back to polling',
            );
            connected.value = false;
            startPolling();
        }
    };

    const handleEchoReconnect = () => {
        if (usingFallback.value) {
            console.log(
                '[useRealtimeUpdates] Echo reconnected, stopping polling',
            );
            connected.value = true;
            stopPolling();
        }
    };

    const cleanup = () => {
        stopPolling();

        try {
            if (window.Echo && channel) {
                window.Echo.leave(channelName);
                console.log(
                    `[useRealtimeUpdates] Left channel: ${channelName}`,
                );
            }
        } catch (error) {
            console.error('[useRealtimeUpdates] Error leaving channel:', error);
        }

        // Remove event listeners
        window.removeEventListener('echo-disconnected', handleEchoDisconnect);
        window.removeEventListener('echo-connected', handleEchoReconnect);
    };

    const trySetup = () => {
        if (channel || !window.Echo) {
            return;
        }

        if (!window.isEchoConnected()) {
            console.warn(
                '[useRealtimeUpdates] Echo not connected yet, waiting while polling',
            );
            startPolling();
            return;
        }

        setupEcho();
    };

    onMounted(() => {
        // Always attach listeners so we can recover once Echo connects
        window.addEventListener('echo-disconnected', handleEchoDisconnect);
        window.addEventListener('echo-connected', handleEchoReconnect);

        trySetup();

        // If Echo is not ready at mount, keep trying when it becomes available
        if (!channel) {
            const echoCheck = window.setInterval(() => {
                if (window.Echo) {
                    trySetup();
                    if (channel) {
                        clearInterval(echoCheck);
                    }
                }
            }, 1000);
        }
    });

    onUnmounted(() => {
        cleanup();
    });

    return {
        connected,
        usingFallback,
        cleanup,
    };
}
