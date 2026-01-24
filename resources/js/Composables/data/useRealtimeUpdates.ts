import { logger } from '@/Utils/logger';
import { router } from '@inertiajs/vue3';
import type { Channel } from 'laravel-echo';
import {
    computed,
    onMounted,
    onUnmounted,
    ref,
    watch,
    type ComputedRef,
    type Ref,
} from 'vue';

interface ReloadOptions {
    only?: string[];
}

interface EventHandlers {
    [eventName: string]: (data: unknown) => void;
}

type ChannelNameParam = string | Ref<string> | ComputedRef<string>;

/**
 * Extended Channel interface to include internal properties
 * that may not be exposed in the type definition
 */
interface ExtendedChannel extends Channel {
    subscriptionError?: (callback: (error: Error) => void) => void;
    listeners?: Record<string, unknown> | (() => Record<string, unknown>);
}

/**
 * Composable for real-time updates via Laravel Echo/WebSockets
 * with automatic fallback to polling if WebSockets fail
 *
 * @param channelNameParam - Echo channel name (can be string, ref, or computed)
 * @param events - Event handlers map
 * @param reloadOptions - Inertia reload options for polling fallback
 * @param pollingInterval - Polling interval in milliseconds (default: 5000)
 * @param shouldPoll - Optional reactive ref to control when polling should be active
 *
 * @example
 * const { connected, usingFallback } = useRealtimeUpdates(
 *     computed(() => `prompt-run.${promptRunId.value}`),
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
    channelNameParam: ChannelNameParam,
    events: EventHandlers,
    reloadOptions?: ReloadOptions,
    pollingInterval: number = 5000,
    shouldPoll?: Ref<boolean>,
) {
    // Convert string to computed ref for consistent handling
    const channelNameComputed = computed(() => {
        if (typeof channelNameParam === 'string') {
            return channelNameParam;
        }
        return channelNameParam.value;
    });
    const channelName = computed(() => channelNameComputed.value);
    const connected = ref(false);
    const usingFallback = ref(false);
    let pollInterval: number | null = null;
    let channel: ExtendedChannel | null = null;

    const startPolling = () => {
        if (pollInterval) return; // Already polling

        // Check if polling should be active (if shouldPoll is provided)
        if (shouldPoll && !shouldPoll.value) {
            return;
        }

        usingFallback.value = true;
        logger.warn(
            '[useRealtimeUpdates] WebSocket unavailable, falling back to polling',
        );

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
            logger.debug(
                '[useRealtimeUpdates] WebSocket reconnected, stopping polling fallback',
            );
            clearInterval(pollInterval);
            pollInterval = null;
            usingFallback.value = false;
        }
    };

    const setupEcho = () => {
        try {
            // Use .value to get the actual string from the computed ref
            const actualChannelName = channelName.value;
            const nextChannel = window.Echo?.channel(actualChannelName);
            if (nextChannel) {
                channel = nextChannel as ExtendedChannel;
            }

            if (!channel) {
                throw new Error('Echo channel could not be created');
            }

            Object.entries(events).forEach(([eventName, handler]) => {
                const wrappedHandler = (data: unknown) => {
                    try {
                        handler(data);
                    } catch (error) {
                        logger.error(
                            `[useRealtimeUpdates] Error handling ${eventName}:`,
                            error,
                        );
                        // Don't rethrow - allow other handlers to continue processing
                    }
                };

                channel!.listen(eventName, wrappedHandler);
            });

            channel.error((error: Error) => {
                logger.error(
                    '[useRealtimeUpdates] WebSocket channel error:',
                    error,
                );
                if (!usingFallback.value) {
                    logger.warn(
                        '[useRealtimeUpdates] WebSocket error detected, starting polling fallback',
                    );
                    startPolling();
                }
            });

            if (channel.subscriptionError) {
                channel.subscriptionError((error: Error) => {
                    logger.error(
                        '[useRealtimeUpdates] Subscription error:',
                        error,
                    );
                });
            }

            connected.value = true;
            logger.debug(
                '[useRealtimeUpdates] WebSocket connected to channel:',
                channelName.value,
            );
        } catch (error) {
            logger.error('[useRealtimeUpdates] Failed to set up Echo:', error);
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
                window.Echo.leave(channelName.value);
            }
        } catch (error) {
            logger.error('[useRealtimeUpdates] Error leaving channel:', error);
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
                // Force unsubscribe from all events on this channel
                if (
                    channel.listeners &&
                    typeof channel.listeners === 'function'
                ) {
                    channel.listeners = {};
                }
                window.Echo?.leave(channelName.value);
                channel = null;
            } catch (error) {
                logger.warn(
                    '[useRealtimeUpdates] Error leaving old channel:',
                    error,
                );
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

        // Always try to set up Echo, even if connection state is uncertain
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
        let previousChannelName = channelName.value;
        watch(channelName, (newChannelName) => {
            if (newChannelName && newChannelName !== previousChannelName) {
                // Cleanup old channel and setup new one
                if (channel) {
                    try {
                        window.Echo?.leave(previousChannelName);
                    } catch (error) {
                        logger.warn(
                            '[useRealtimeUpdates] Error leaving old channel during name change:',
                            error,
                        );
                    }
                    channel = null;
                }
                previousChannelName = newChannelName;
                // Setup new channel
                trySetup();
            }
        });

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
