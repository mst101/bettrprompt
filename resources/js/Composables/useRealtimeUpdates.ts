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
        }
    };

    const setupEcho = () => {
        try {
            channel = window.Echo?.channel(channelName);

            if (!channel) {
                throw new Error('Echo channel could not be created');
            }

            Object.entries(events).forEach(([eventName, handler]) => {
                const wrappedHandler = (data: unknown) => {
                    try {
                        handler(data);
                    } catch (error) {
                        console.error(
                            `[useRealtimeUpdates] Error handling ${eventName}:`,
                            error,
                        );
                        throw error;
                    }
                };

                channel!.listen(eventName, wrappedHandler);
            });

            channel.error((error: Error) => {
                console.error(
                    '[useRealtimeUpdates] WebSocket channel error:',
                    error,
                );
                if (!usingFallback.value) {
                    startPolling();
                }
            });

            if ('subscriptionError' in channel) {
                (channel as any).subscriptionError((error: Error) => {
                    console.error(
                        '[useRealtimeUpdates] Subscription error:',
                        error,
                    );
                });
            }

            connected.value = true;
        } catch (error) {
            console.error('[useRealtimeUpdates] Failed to set up Echo:', error);
            startPolling();
        }
    };

    const handleEchoDisconnect = () => {
        if (!usingFallback.value) {
            connected.value = false;
            startPolling();
        }
    };

    const handleEchoReconnect = () => {
        if (usingFallback.value) {
            connected.value = true;
            stopPolling();
        }
    };

    const cleanup = () => {
        stopPolling();

        try {
            if (window.Echo && channel) {
                window.Echo.leave(channelName);
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

        const echoConnected = window.isEchoConnected();

        if (!echoConnected) {
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
