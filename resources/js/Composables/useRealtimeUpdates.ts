import { router } from '@inertiajs/vue3';
import type { Channel } from 'laravel-echo';
import { onMounted, onUnmounted, ref, watch, type Ref } from 'vue';

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
 * @param shouldPoll - Optional reactive ref to control when polling should be active
 *
 * @example
 * const { connected, usingFallback } = useRealtimeUpdates(
 *     `prompt-run.${promptRunId}`,
 *     {
 *         'FrameworkSelected': (data) => console.log('Framework selected', data),
 *         'PromptOptimizationCompleted': (data) => router.reload(),
 *     },
 *     { only: ['promptRun', 'progress'] },
 *     5000,
 *     computed(() => promptRun.status === 'processing')
 * );
 */
export function useRealtimeUpdates(
    channelName: string,
    events: EventHandlers,
    reloadOptions?: ReloadOptions,
    pollingInterval: number = 5000,
    shouldPoll?: Ref<boolean>,
) {
    const connected = ref(false);
    const usingFallback = ref(false);
    let pollInterval: number | null = null;
    let channel: Channel | null = null;

    const startPolling = () => {
        if (pollInterval) {
            console.log(
                '[useRealtimeUpdates] Polling already active, skipping start',
            );
            return; // Already polling
        }

        // Check if polling should be active (if shouldPoll is provided)
        if (shouldPoll && !shouldPoll.value) {
            console.log(
                '[useRealtimeUpdates] Polling not active yet (shouldPoll is false)',
            );
            return;
        }

        console.log(
            `[useRealtimeUpdates] Starting polling every ${pollingInterval}ms for channel: ${channelName}`,
        );
        usingFallback.value = true;

        pollInterval = window.setInterval(() => {
            // Check if we should still be polling
            if (shouldPoll && !shouldPoll.value) {
                stopPolling();
                return;
            }

            console.log('[useRealtimeUpdates] Polling reload...');
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
            console.log(
                '[useRealtimeUpdates] Setting up Echo channel:',
                channelName,
            );
            channel = window.Echo?.channel(channelName);

            if (!channel) {
                throw new Error('Echo channel could not be created');
            }

            console.log(
                `[useRealtimeUpdates] Channel created successfully. Listening for events:`,
                Object.keys(events),
            );

            Object.entries(events).forEach(([eventName, handler]) => {
                const wrappedHandler = (data: unknown) => {
                    try {
                        console.log(
                            `[useRealtimeUpdates] Event received: ${eventName}`,
                            data,
                        );
                        handler(data);
                    } catch (error) {
                        console.error(
                            `[useRealtimeUpdates] Error handling ${eventName}:`,
                            error,
                        );
                        // Don't rethrow - allow other handlers to continue processing
                    }
                };

                console.log(
                    `[useRealtimeUpdates] Registering listener for ${eventName}`,
                );
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
            console.log(
                '[useRealtimeUpdates] Echo setup complete for channel:',
                channelName,
            );
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
            if (channel) {
                console.log(
                    '[useRealtimeUpdates] Channel already set up, skipping setup',
                );
            } else {
                console.log(
                    '[useRealtimeUpdates] Echo not available, skipping setup',
                );
            }
            return;
        }

        console.log('[useRealtimeUpdates] Attempting to setup Echo...');

        // Always try to setup Echo, even if connection state is uncertain
        // The setupEcho function will handle errors and fallback to polling if needed
        try {
            setupEcho();
        } catch {
            // setupEcho will call startPolling on error, but ensure we have a fallback
            if (!usingFallback.value) {
                startPolling();
            }
        }
    };

    onMounted(() => {
        console.log('[useRealtimeUpdates] Mounted for channel:', channelName);
        // Always attach listeners so we can recover once Echo connects
        window.addEventListener('echo-disconnected', handleEchoDisconnect);
        window.addEventListener('echo-connected', handleEchoReconnect);

        trySetup();

        // If Echo is not ready at mount, keep trying when it becomes available
        if (!channel) {
            console.log(
                '[useRealtimeUpdates] Channel not ready at mount, will retry...',
            );
            const echoCheck = window.setInterval(() => {
                if (window.Echo) {
                    console.log(
                        '[useRealtimeUpdates] Echo available, retrying setup...',
                    );
                    trySetup();
                    if (channel) {
                        console.log(
                            '[useRealtimeUpdates] Channel setup successful',
                        );
                        clearInterval(echoCheck);
                    }
                }
            }, 1000);
        }

        // Watch shouldPoll and start/stop polling accordingly (only if using fallback)
        if (shouldPoll) {
            watch(shouldPoll, (newValue) => {
                console.log(
                    `[useRealtimeUpdates] shouldPoll changed to ${newValue} for channel: ${channelName}`,
                );
                if (!usingFallback.value) {
                    console.log(
                        '[useRealtimeUpdates] Not using fallback, ignoring shouldPoll change',
                    );
                    return;
                } // Only relevant when using fallback

                if (newValue && !pollInterval) {
                    // Should poll but not currently polling - start it
                    startPolling();
                } else if (!newValue && pollInterval) {
                    // Should not poll but currently polling - stop it
                    stopPolling();
                }
            });
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
