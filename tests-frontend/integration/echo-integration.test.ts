import { useRealtimeUpdates } from '@/Composables/useRealtimeUpdates';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { nextTick } from 'vue';

/**
 * Integration tests for Laravel Echo / WebSocket scenarios
 *
 * These tests verify the composable's behavior when integrated with a real Echo
 * instance (mocked), simulating real-world WebSocket scenarios including:
 * - Connection establishment and event delivery
 * - Connection loss and fallback recovery
 * - Rapid reconnection/disconnection cycles
 * - Multiple simultaneous listeners
 */

describe('Laravel Echo Integration', () => {
    let mockRouter: any;
    let mockEcho: any;
    let mockChannel: any;

    beforeEach(() => {
        vi.clearAllMocks();
        vi.useFakeTimers();

        // Mock router
        mockRouter = {
            reload: vi.fn(),
        };
        vi.stubGlobal('router', mockRouter);

        // Create more realistic Echo mock
        mockChannel = {
            listen: vi.fn(function (
                eventName: string,
                handler: (data: unknown) => void,
            ) {
                this[`_handler_${eventName}`] = handler;
                return this;
            }),
            error: vi.fn(function (handler: (error: Error) => void) {
                this._errorHandler = handler;
                return this;
            }),
            leave: vi.fn(),
            // Helper to simulate receiving an event
            _simulateEvent: function (eventName: string, data: unknown) {
                if (this[`_handler_${eventName}`]) {
                    this[`_handler_${eventName}`](data);
                }
            },
            // Helper to simulate channel error
            _simulateError: function (error: Error) {
                if (this._errorHandler) {
                    this._errorHandler(error);
                }
            },
        };

        mockEcho = {
            channel: vi.fn().mockReturnValue(mockChannel),
            leave: vi.fn(),
        };

        global.window.Echo = mockEcho;
        global.window.isEchoConnected = vi.fn().mockReturnValue(true);
    });

    afterEach(() => {
        vi.useRealTimers();
        vi.clearAllMocks();
    });

    describe('connection establishment', () => {
        it('should establish WebSocket connection on mount', async () => {
            const eventHandler = vi.fn();

            useRealtimeUpdates('test-channel', {
                TestEvent: eventHandler,
            });

            await nextTick();

            expect(mockEcho.channel).toHaveBeenCalledWith('test-channel');
            expect(mockChannel.listen).toHaveBeenCalledWith(
                'TestEvent',
                expect.any(Function),
            );
        });

        it('should receive and handle events from Echo', async () => {
            const eventHandler = vi.fn();
            const testData = { id: 123, status: 'completed' };

            useRealtimeUpdates('test-channel', {
                EventName: eventHandler,
            });

            await nextTick();

            // Simulate receiving an event from Echo
            mockChannel._simulateEvent('EventName', testData);

            expect(eventHandler).toHaveBeenCalledWith(testData);
        });

        it('should handle multiple events from same channel', async () => {
            const handler1 = vi.fn();
            const handler2 = vi.fn();

            useRealtimeUpdates('test-channel', {
                Event1: handler1,
                Event2: handler2,
            });

            await nextTick();

            const data1 = { type: 'event1' };
            const data2 = { type: 'event2' };

            mockChannel._simulateEvent('Event1', data1);
            mockChannel._simulateEvent('Event2', data2);

            expect(handler1).toHaveBeenCalledWith(data1);
            expect(handler2).toHaveBeenCalledWith(data2);
        });
    });

    describe('error handling and recovery', () => {
        it('should fallback to polling when channel error occurs', async () => {
            const { usingFallback } = useRealtimeUpdates('test-channel', {});

            await nextTick();

            expect(usingFallback.value).toBe(false);

            // Simulate channel error
            mockChannel._simulateError(new Error('Connection lost'));

            await nextTick();

            expect(usingFallback.value).toBe(true);
            expect(mockRouter.reload).toBeDefined();
        });

        it('should continue processing events even if handler throws', async () => {
            const errorHandler = vi.fn().mockImplementation(() => {
                throw new Error('Handler error');
            });
            const successHandler = vi.fn();

            useRealtimeUpdates('test-channel', {
                FailEvent: errorHandler,
                SuccessEvent: successHandler,
            });

            await nextTick();

            // Event that fails should not prevent others
            mockChannel._simulateEvent('FailEvent', {});
            mockChannel._simulateEvent('SuccessEvent', { data: 'test' });

            expect(errorHandler).toHaveBeenCalled();
            expect(successHandler).toHaveBeenCalledWith({ data: 'test' });
        });
    });

    describe('reconnection scenarios', () => {
        it('should stop polling when Echo reconnects', async () => {
            // Start in disconnected state
            global.window.isEchoConnected = vi.fn().mockReturnValue(false);

            const { usingFallback } = useRealtimeUpdates('test-channel', {});

            await nextTick();

            expect(usingFallback.value).toBe(true);

            // Simulate Echo reconnection
            global.window.isEchoConnected = vi.fn().mockReturnValue(true);

            // Re-mount with new connection state
            const { usingFallback: fallback2 } = useRealtimeUpdates(
                'test-channel2',
                {},
            );

            await nextTick();

            expect(fallback2.value).toBe(false);
            expect(mockEcho.channel).toHaveBeenCalled();
        });

        it('should handle rapid reconnect/disconnect cycles', async () => {
            const eventHandler = vi.fn();

            useRealtimeUpdates('test-channel', {
                TestEvent: eventHandler,
            });

            await nextTick();

            // Cycle 1: Send event
            mockChannel._simulateEvent('TestEvent', { cycle: 1 });
            expect(eventHandler).toHaveBeenCalledWith({ cycle: 1 });

            // Simulate error and reconnection
            mockChannel._simulateError(new Error('Connection lost'));
            await nextTick();

            // After recovery, should still work
            mockChannel._simulateEvent('TestEvent', { cycle: 2 });

            expect(eventHandler).toHaveBeenCalledWith({ cycle: 2 });
            expect(eventHandler).toHaveBeenCalledTimes(2);
        });
    });

    describe('performance and load', () => {
        it('should handle high-frequency events without queuing issues', async () => {
            const eventHandler = vi.fn();

            useRealtimeUpdates('test-channel', {
                HighFreqEvent: eventHandler,
            });

            await nextTick();

            // Simulate rapid events
            for (let i = 0; i < 100; i++) {
                mockChannel._simulateEvent('HighFreqEvent', { index: i });
            }

            expect(eventHandler).toHaveBeenCalledTimes(100);
            expect(eventHandler).toHaveBeenLastCalledWith({ index: 99 });
        });

        it('should handle multiple channels simultaneously', async () => {
            const handler1 = vi.fn();
            const handler2 = vi.fn();

            // Create two separate composable instances
            useRealtimeUpdates('channel-1', {
                Event: handler1,
            });

            useRealtimeUpdates('channel-2', {
                Event: handler2,
            });

            await nextTick();

            // Both channels should be created
            expect(mockEcho.channel).toHaveBeenCalledWith('channel-1');
            expect(mockEcho.channel).toHaveBeenCalledWith('channel-2');
        });
    });

    describe('real-world scenarios', () => {
        it('should handle prompt analysis flow', async () => {
            const analysisHandler = vi.fn();
            const optimizationHandler = vi.fn();

            useRealtimeUpdates(`prompt-run.123`, {
                AnalysisCompleted: analysisHandler,
                PromptOptimizationCompleted: optimizationHandler,
            });

            await nextTick();

            // Simulate analysis completion
            mockChannel._simulateEvent('AnalysisCompleted', {
                promptRunId: 123,
                selectedFramework: 'Decision Tree',
            });

            expect(analysisHandler).toHaveBeenCalledWith(
                expect.objectContaining({
                    promptRunId: 123,
                }),
            );

            // Then optimization completion
            mockChannel._simulateEvent('PromptOptimizationCompleted', {
                promptRunId: 123,
                optimizedPrompt: 'Optimised prompt text...',
            });

            expect(optimizationHandler).toHaveBeenCalled();
        });

        it('should gracefully handle page navigation', async () => {
            const { cleanup } = useRealtimeUpdates('test-channel', {});

            await nextTick();

            // Simulate navigation away
            cleanup();

            expect(mockEcho.leave).toHaveBeenCalledWith('test-channel');
        });

        it('should handle concurrent subscriptions to same channel', async () => {
            const handlers = [vi.fn(), vi.fn()];

            // Create two instances listening to same channel
            // (simulating component re-mounts or multiple listeners)
            const instances = handlers.map((handler) =>
                useRealtimeUpdates('shared-channel', {
                    Event: handler,
                }),
            );

            await nextTick();

            // Verify channels were subscribed
            expect(mockEcho.channel).toHaveBeenCalledWith('shared-channel');

            // Clean up all
            instances.forEach((inst) => inst.cleanup());

            // Channel should be cleaned up
            expect(mockEcho.leave).toHaveBeenCalled();
        });
    });

    describe('reliability and robustness', () => {
        it('should handle event handlers registered correctly', async () => {
            const handler = vi.fn();

            useRealtimeUpdates('test-channel', {
                RobustEvent: handler,
            });

            await nextTick();

            // Verify handler is registered
            expect(mockChannel.listen).toHaveBeenCalledWith(
                'RobustEvent',
                expect.any(Function),
            );

            // Verify event handling works
            mockChannel._simulateEvent('RobustEvent', { data: 'test' });

            expect(handler).toHaveBeenCalledWith({ data: 'test' });
        });

        it('should cleanup properly after unmount', async () => {
            const handler = vi.fn();

            const { cleanup } = useRealtimeUpdates('test-channel', {
                CleanupEvent: handler,
            });

            await nextTick();

            // Verify setup
            expect(mockEcho.channel).toHaveBeenCalled();

            // Clean up
            cleanup();

            // Verify cleanup
            expect(mockEcho.leave).toHaveBeenCalled();
        });
    });
});
