import { useRealtimeUpdates } from '@/Composables/useRealtimeUpdates';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { nextTick } from 'vue';

/**
 * Unit tests for useRealtimeUpdates composable
 *
 * Tests the composable's core functionality:
 * - Echo channel subscription
 * - Event handler registration
 * - Fallback polling mechanism
 * - Channel cleanup and lifecycle
 * - Error handling and recovery
 */

describe('useRealtimeUpdates', () => {
    // Mock window.Echo and related globals
    let mockChannel: any;
    let mockRouter: any;

    beforeEach(() => {
        // Clear all mocks before each test
        vi.clearAllMocks();

        // Mock channel
        mockChannel = {
            listen: vi.fn().mockReturnValue(mockChannel),
            error: vi.fn().mockReturnValue(mockChannel),
            leave: vi.fn(),
        };

        // Mock window.Echo
        global.window.Echo = {
            channel: vi.fn().mockReturnValue(mockChannel),
            leave: vi.fn(),
        };

        // Mock window.isEchoConnected
        global.window.isEchoConnected = vi.fn().mockReturnValue(true);

        // Mock router.reload
        mockRouter = {
            reload: vi.fn(),
        };
        vi.stubGlobal('router', mockRouter);

        // Mock setInterval and clearInterval
        vi.useFakeTimers();
    });

    afterEach(() => {
        vi.useRealTimers();
        vi.clearAllMocks();
    });

    describe('channel subscription', () => {
        it('should create and subscribe to Echo channel on mount', async () => {
            const channelName = 'test-channel';
            const events = {
                TestEvent: vi.fn(),
            };

            const { connected } = useRealtimeUpdates(channelName, events);

            await nextTick();

            expect(window.Echo.channel).toHaveBeenCalledWith(channelName);
            expect(mockChannel.listen).toHaveBeenCalledWith(
                'TestEvent',
                expect.any(Function),
            );
            expect(connected.value).toBe(true);
        });

        it('should register multiple event handlers', async () => {
            const channelName = 'prompt-run.123';
            const events = {
                AnalysisCompleted: vi.fn(),
                PromptOptimizationCompleted: vi.fn(),
            };

            useRealtimeUpdates(channelName, events);

            await nextTick();

            expect(mockChannel.listen).toHaveBeenCalledTimes(2);
            expect(mockChannel.listen).toHaveBeenCalledWith(
                'AnalysisCompleted',
                expect.any(Function),
            );
            expect(mockChannel.listen).toHaveBeenCalledWith(
                'PromptOptimizationCompleted',
                expect.any(Function),
            );
        });
    });

    describe('event handling', () => {
        it('should call event handler when event is triggered', async () => {
            const handler = vi.fn();
            const events = { TestEvent: handler };

            useRealtimeUpdates('test-channel', events);

            await nextTick();

            // Get the handler that was registered and call it
            const registeredHandler = mockChannel.listen.mock.calls[0][1];
            const testData = { id: 123, status: 'completed' };
            registeredHandler(testData);

            expect(handler).toHaveBeenCalledWith(testData);
        });

        it('should handle handler errors gracefully', async () => {
            const errorHandler = vi.fn().mockImplementation(() => {
                throw new Error('Handler error');
            });
            const events = { TestEvent: errorHandler };
            const consoleSpy = vi.spyOn(console, 'error');

            useRealtimeUpdates('test-channel', events);

            await nextTick();

            // Trigger the handler
            const registeredHandler = mockChannel.listen.mock.calls[0][1];
            registeredHandler({ data: 'test' });

            // Should log error but not crash
            expect(consoleSpy).toHaveBeenCalledWith(
                '[useRealtimeUpdates] Error handling TestEvent:',
                expect.any(Error),
            );
            expect(errorHandler).toHaveBeenCalled();
        });

        it('should register channel error handler', async () => {
            useRealtimeUpdates('test-channel', {});

            await nextTick();

            expect(mockChannel.error).toHaveBeenCalledWith(
                expect.any(Function),
            );
        });
    });

    describe('fallback polling', () => {
        it('should start polling when Echo is not available', async () => {
            global.window.isEchoConnected = vi.fn().mockReturnValue(false);

            const { usingFallback } = useRealtimeUpdates('test-channel', {});

            await nextTick();

            expect(usingFallback.value).toBe(true);
            expect(mockRouter.reload).toBeDefined();
        });

        it('should start polling when Echo channel creation fails', async () => {
            global.window.Echo.channel = vi.fn().mockReturnValue(null);

            const { usingFallback } = useRealtimeUpdates('test-channel', {});

            await nextTick();

            expect(usingFallback.value).toBe(true);
        });

        it('should poll at specified interval', async () => {
            global.window.isEchoConnected = vi.fn().mockReturnValue(false);
            const pollingInterval = 3000;

            useRealtimeUpdates('test-channel', {}, {}, pollingInterval);

            await nextTick();

            // Fast-forward time
            vi.advanceTimersByTime(pollingInterval);

            expect(mockRouter.reload).toHaveBeenCalled();
        });

        it('should pass reload options to polling', async () => {
            global.window.isEchoConnected = vi.fn().mockReturnValue(false);
            const reloadOptions = { only: ['promptRun'] };

            useRealtimeUpdates('test-channel', {}, reloadOptions);

            await nextTick();

            vi.advanceTimersByTime(5000);

            expect(mockRouter.reload).toHaveBeenCalledWith(reloadOptions);
        });
    });

    describe('channel cleanup', () => {
        it('should leave channel on unmount', async () => {
            global.window.Echo.leave = vi.fn();

            const { cleanup } = useRealtimeUpdates('test-channel', {});

            await nextTick();

            cleanup();

            expect(global.window.Echo.leave).toHaveBeenCalledWith(
                'test-channel',
            );
        });

        it('should stop polling on cleanup', async () => {
            global.window.isEchoConnected = vi.fn().mockReturnValue(false);

            const { usingFallback, cleanup } = useRealtimeUpdates(
                'test-channel',
                {},
            );

            await nextTick();
            expect(usingFallback.value).toBe(true);

            cleanup();

            expect(usingFallback.value).toBe(false);
        });

        it('should remove event listeners on cleanup', async () => {
            const removeEventListenerSpy = vi.spyOn(
                window,
                'removeEventListener',
            );

            const { cleanup } = useRealtimeUpdates('test-channel', {});

            await nextTick();

            cleanup();

            expect(removeEventListenerSpy).toHaveBeenCalledWith(
                'echo-disconnected',
                expect.any(Function),
            );
            expect(removeEventListenerSpy).toHaveBeenCalledWith(
                'echo-connected',
                expect.any(Function),
            );

            removeEventListenerSpy.mockRestore();
        });

        it('should handle cleanup errors gracefully', async () => {
            global.window.Echo.leave = vi.fn().mockImplementation(() => {
                throw new Error('Channel leave error');
            });
            const consoleSpy = vi.spyOn(console, 'error');

            const { cleanup } = useRealtimeUpdates('test-channel', {});

            await nextTick();

            // Should not throw
            expect(() => cleanup()).not.toThrow();
            expect(consoleSpy).toHaveBeenCalledWith(
                '[useRealtimeUpdates] Error leaving channel:',
                expect.any(Error),
            );
        });
    });

    describe('connection state', () => {
        it('should reflect connected state', async () => {
            const { connected } = useRealtimeUpdates('test-channel', {});

            await nextTick();

            expect(connected.value).toBe(true);
        });

        it('should mark as not connected when channel fails', async () => {
            global.window.Echo.channel = vi.fn().mockReturnValue(null);

            const { connected } = useRealtimeUpdates('test-channel', {});

            await nextTick();

            expect(connected.value).toBe(false);
        });
    });

    describe('lifecycle and recovery', () => {
        it('should attach listeners for Echo reconnection', async () => {
            const addEventListenerSpy = vi.spyOn(window, 'addEventListener');

            useRealtimeUpdates('test-channel', {});

            await nextTick();

            expect(addEventListenerSpy).toHaveBeenCalledWith(
                'echo-disconnected',
                expect.any(Function),
            );
            expect(addEventListenerSpy).toHaveBeenCalledWith(
                'echo-connected',
                expect.any(Function),
            );

            addEventListenerSpy.mockRestore();
        });

        it('should recover from Echo disconnect by polling', async () => {
            const addEventListenerSpy = vi.spyOn(window, 'addEventListener');

            const { usingFallback } = useRealtimeUpdates('test-channel', {});

            await nextTick();

            // Get the disconnect handler and trigger it
            const disconnectHandler =
                addEventListenerSpy.mock.calls.find(
                    (call: Array<unknown>) => call[0] === 'echo-disconnected',
                )?.[1] || null;

            expect(disconnectHandler).not.toBeNull();

            if (disconnectHandler) {
                (disconnectHandler as () => void)();
                await nextTick();

                // Should fallback to polling
                expect(usingFallback.value).toBe(true);
            }

            addEventListenerSpy.mockRestore();
        });

        it('should stop polling when Echo reconnects', async () => {
            global.window.isEchoConnected = vi.fn().mockReturnValue(false);
            const addEventListenerSpy = vi.spyOn(window, 'addEventListener');

            const { usingFallback } = useRealtimeUpdates('test-channel', {});

            await nextTick();

            expect(usingFallback.value).toBe(true);

            // Get reconnect handler and trigger it
            const reconnectHandler =
                addEventListenerSpy.mock.calls.find(
                    (call: Array<unknown>) => call[0] === 'echo-connected',
                )?.[1] || null;

            // First make Echo connected
            global.window.isEchoConnected = vi.fn().mockReturnValue(true);
            global.window.Echo.channel = vi.fn().mockReturnValue(mockChannel);

            if (reconnectHandler) {
                (reconnectHandler as () => void)();
                await nextTick();

                // Should stop polling
                expect(usingFallback.value).toBe(false);
            }

            addEventListenerSpy.mockRestore();
        });
    });

    describe('edge cases', () => {
        it('should handle missing Echo gracefully', async () => {
            global.window.Echo = undefined as any;

            const { connected, usingFallback } = useRealtimeUpdates(
                'test-channel',
                {},
            );

            await nextTick();

            expect(connected.value).toBe(false);
            expect(usingFallback.value).toBe(true);
        });

        it('should not start polling twice', async () => {
            global.window.isEchoConnected = vi.fn().mockReturnValue(false);

            const { usingFallback } = useRealtimeUpdates(
                'test-channel',
                {},
                {},
                1000,
            );

            await nextTick();

            expect(usingFallback.value).toBe(true);

            // Advance time and trigger polling multiple times
            vi.advanceTimersByTime(1000);
            vi.advanceTimersByTime(1000);

            // Should only set one interval, not multiple
            const intervalCalls = vi.getTimerCount();
            expect(intervalCalls).toBeLessThanOrEqual(2); // One interval + one check interval
        });

        it('should work with empty event handlers', async () => {
            const { connected } = useRealtimeUpdates('test-channel', {});

            await nextTick();

            expect(connected.value).toBe(true);
            expect(mockChannel.listen).not.toHaveBeenCalled();
        });

        it('should handle channel error without already polling', async () => {
            const { usingFallback } = useRealtimeUpdates('test-channel', {});

            await nextTick();

            // Simulate channel error
            const errorHandler = mockChannel.error.mock.calls[0][0];
            errorHandler(new Error('Channel error'));

            await nextTick();

            expect(usingFallback.value).toBe(true);
        });
    });

    describe('integration scenarios', () => {
        it('should handle real-time event flow correctly', async () => {
            const eventHandlers = {
                AnalysisCompleted: vi.fn(),
                PromptOptimizationCompleted: vi.fn(),
            };

            const { connected } = useRealtimeUpdates(
                'prompt-run.123',
                eventHandlers,
            );

            await nextTick();

            // Simulate receiving an event
            const analysisHandler = mockChannel.listen.mock.calls[0][1];
            const eventData = { promptRunId: 123, status: 'completed' };
            analysisHandler(eventData);

            expect(eventHandlers.AnalysisCompleted).toHaveBeenCalledWith(
                eventData,
            );
            expect(connected.value).toBe(true);
        });

        it('should gracefully degrade from WebSocket to polling', async () => {
            global.window.isEchoConnected = vi
                .fn()
                .mockReturnValueOnce(true)
                .mockReturnValueOnce(false);

            const { usingFallback } = useRealtimeUpdates('test-channel', {});

            await nextTick();

            // Initially should use WebSocket
            expect(usingFallback.value).toBe(false);

            // Simulate Echo disconnection and trigger channel error
            const channelErrorHandler = mockChannel.error.mock.calls[0][0];
            channelErrorHandler(new Error('Connection lost'));

            await nextTick();

            // Should fallback to polling
            expect(usingFallback.value).toBe(true);
        });
    });
});
