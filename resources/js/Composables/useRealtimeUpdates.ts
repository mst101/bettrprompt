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
        if (pollInterval) return; // Already polling

        // Check if polling should be active (if shouldPoll is provided)
        if (shouldPoll && !shouldPoll.value) {
            return;
        }

        usingFallback.value = true;

        // Perform an immediate reload on first poll
        router.reload(reloadOptions);

        pollInterval = window.setInterval(() => {
            // Check if we should still be polling
            if (shouldPoll && !shouldPoll.value) {
                stopPolling();
                return;
            }

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

            console.log(
                `[useRealtimeUpdates] Connected to channel: ${channelName}`,
            );
            console.log(
                `[useRealtimeUpdates] Listening for events:`,
                Object.keys(events),
            );

            Object.entries(events).forEach(([eventName, handler]) => {
                const wrappedHandler = (data: unknown) => {
                    console.log(
                        `[useRealtimeUpdates] Received event: ${eventName}`,
                        data,
                    );
                    try {
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
                    `[useRealtimeUpdates] Setting up listener for: ${eventName}`,
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
        // Always cleanup old channel before setting up new one
        // This is important when router.reload() is called with partial props (only: [...])
        // because the component won't unmount/remount, so we need to manually disconnect
        // from the old channel and reconnect to the new one
        if (channel) {
            try {
                console.log(
                    '[useRealtimeUpdates] Leaving old channel to reconnect to new one',
                );
                // Force unsubscribe from all events on this channel
                if ('listeners' in channel && typeof (channel as any).listeners === 'function') {
                    (channel as any).listeners = {};
                }
                window.Echo?.leave(channelName);
                channel = null;
                console.log('[useRealtimeUpdates] Old channel cleaned up');
            } catch (error) {
                console.warn('[useRealtimeUpdates] Error leaving old channel:', error);
                channel = null;
            }
        }

        // If Echo is not available, start fallback polling immediately
        if (!window.Echo) {
            if (!usingFallback.value) {
                startPolling();
            }
            return;
        }

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

        // Watch for channelName changes (e.g., when router.reload updates props)
        // This is important for partial Inertia reloads that don't unmount/remount the component
        const unwatch = watch(
            () => channelName,
            (newChannelName, oldChannelName) => {
                if (newChannelName && newChannelName !== oldChannelName) {
                    console.log(
                        `[useRealtimeUpdates] Channel name changed from ${oldChannelName} to ${newChannelName}, reconnecting`,
                    );
                    // Cleanup old channel and setup new one
                    if (channel) {
                        try {
                            window.Echo?.leave(oldChannelName || channelName);
                        } catch (error) {
                            console.warn(
                                '[useRealtimeUpdates] Error leaving old channel during name change:',
                                error,
                            );
                        }
                        channel = null;
                    }
                    // Setup new channel
                    trySetup();
                }
            },
        );

        // Watch shouldPoll and start/stop polling accordingly (only if using fallback)
        if (shouldPoll) {
            watch(shouldPoll, (newValue) => {
                if (!usingFallback.value) return; // Only relevant when using fallback

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
