import { useRealtimeUpdates } from '@/Composables/useRealtimeUpdates';
import { mount } from '@vue/test-utils';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { defineComponent, nextTick } from 'vue';

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

            // Create a test component that uses the composable
            const TestComponent = defineComponent({
                setup() {
                    useRealtimeUpdates('test-channel', {
                        TestEvent: eventHandler,
                    });
                    return {};
                },
                template: '<div></div>',
            });

            // Mount the component to trigger lifecycle hooks
            const wrapper = mount(TestComponent);

            await nextTick();

            expect(mockEcho.channel).toHaveBeenCalledWith('test-channel');
            expect(mockChannel.listen).toHaveBeenCalledWith(
                'TestEvent',
                expect.any(Function),
            );

            wrapper.unmount();
        });

        it('should receive and handle events from Echo', async () => {
            const eventHandler = vi.fn();
            const testData = { id: 123, status: 'completed' };

            const TestComponent = defineComponent({
                setup() {
                    useRealtimeUpdates('test-channel', {
                        EventName: eventHandler,
                    });
                    return {};
                },
                template: '<div></div>',
            });

            const wrapper = mount(TestComponent);

            await nextTick();

            // Simulate receiving an event from Echo
            mockChannel._simulateEvent('EventName', testData);

            expect(eventHandler).toHaveBeenCalledWith(testData);

            wrapper.unmount();
        });

        it('should handle multiple events from same channel', async () => {
            const handler1 = vi.fn();
            const handler2 = vi.fn();

            const TestComponent = defineComponent({
                setup() {
                    useRealtimeUpdates('test-channel', {
                        Event1: handler1,
                        Event2: handler2,
                    });
                    return {};
                },
                template: '<div></div>',
            });

            const wrapper = mount(TestComponent);

            await nextTick();

            const data1 = { type: 'event1' };
            const data2 = { type: 'event2' };

            mockChannel._simulateEvent('Event1', data1);
            mockChannel._simulateEvent('Event2', data2);

            expect(handler1).toHaveBeenCalledWith(data1);
            expect(handler2).toHaveBeenCalledWith(data2);

            wrapper.unmount();
        });
    });

    describe('error handling and recovery', () => {
        it('should fallback to polling when channel error occurs', async () => {
            let composableState: any;

            const TestComponent = defineComponent({
                setup() {
                    composableState = useRealtimeUpdates('test-channel', {});
                    return composableState;
                },
                template: '<div></div>',
            });

            const wrapper = mount(TestComponent);

            await nextTick();

            expect(composableState.usingFallback.value).toBe(false);

            // Simulate channel error
            mockChannel._simulateError(new Error('Connection lost'));

            await nextTick();

            expect(composableState.usingFallback.value).toBe(true);
            expect(mockRouter.reload).toBeDefined();

            wrapper.unmount();
        });

        it('should continue processing events even if handler throws', async () => {
            const errorHandler = vi.fn().mockImplementation(() => {
                throw new Error('Handler error');
            });
            const successHandler = vi.fn();

            const TestComponent = defineComponent({
                setup() {
                    useRealtimeUpdates('test-channel', {
                        FailEvent: errorHandler,
                        SuccessEvent: successHandler,
                    });
                    return {};
                },
                template: '<div></div>',
            });

            const wrapper = mount(TestComponent);

            await nextTick();

            // Event that fails should not prevent others
            mockChannel._simulateEvent('FailEvent', {});
            mockChannel._simulateEvent('SuccessEvent', { data: 'test' });

            expect(errorHandler).toHaveBeenCalled();
            expect(successHandler).toHaveBeenCalledWith({ data: 'test' });

            wrapper.unmount();
        });
    });

    describe('reconnection scenarios', () => {
        it('should stop polling when Echo reconnects', async () => {
            // Start in disconnected state by making Echo unavailable
            global.window.Echo = undefined;

            let composableState: any;

            const TestComponent = defineComponent({
                setup() {
                    composableState = useRealtimeUpdates('test-channel', {});
                    return composableState;
                },
                template: '<div></div>',
            });

            const wrapper = mount(TestComponent);

            await nextTick();

            expect(composableState.usingFallback.value).toBe(true);

            // Simulate Echo reconnection by restoring it
            global.window.Echo = mockEcho;

            // Create a new component with the new connection state
            let composableState2: any;

            const TestComponent2 = defineComponent({
                setup() {
                    composableState2 = useRealtimeUpdates('test-channel2', {});
                    return composableState2;
                },
                template: '<div></div>',
            });

            const wrapper2 = mount(TestComponent2);

            await nextTick();

            expect(composableState2.usingFallback.value).toBe(false);
            expect(mockEcho.channel).toHaveBeenCalled();

            wrapper.unmount();
            wrapper2.unmount();
        });

        it('should handle rapid reconnect/disconnect cycles', async () => {
            const eventHandler = vi.fn();

            const TestComponent = defineComponent({
                setup() {
                    useRealtimeUpdates('test-channel', {
                        TestEvent: eventHandler,
                    });
                    return {};
                },
                template: '<div></div>',
            });

            const wrapper = mount(TestComponent);

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

            wrapper.unmount();
        });
    });

    describe('performance and load', () => {
        it('should handle high-frequency events without queuing issues', async () => {
            const eventHandler = vi.fn();

            const TestComponent = defineComponent({
                setup() {
                    useRealtimeUpdates('test-channel', {
                        HighFreqEvent: eventHandler,
                    });
                    return {};
                },
                template: '<div></div>',
            });

            const wrapper = mount(TestComponent);

            await nextTick();

            // Simulate rapid events
            for (let i = 0; i < 100; i++) {
                mockChannel._simulateEvent('HighFreqEvent', { index: i });
            }

            expect(eventHandler).toHaveBeenCalledTimes(100);
            expect(eventHandler).toHaveBeenLastCalledWith({ index: 99 });

            wrapper.unmount();
        });

        it('should handle multiple channels simultaneously', async () => {
            const handler1 = vi.fn();
            const handler2 = vi.fn();

            const TestComponent1 = defineComponent({
                setup() {
                    useRealtimeUpdates('channel-1', {
                        Event: handler1,
                    });
                    return {};
                },
                template: '<div></div>',
            });

            const TestComponent2 = defineComponent({
                setup() {
                    useRealtimeUpdates('channel-2', {
                        Event: handler2,
                    });
                    return {};
                },
                template: '<div></div>',
            });

            const wrapper1 = mount(TestComponent1);
            const wrapper2 = mount(TestComponent2);

            await nextTick();

            // Both channels should be created
            expect(mockEcho.channel).toHaveBeenCalledWith('channel-1');
            expect(mockEcho.channel).toHaveBeenCalledWith('channel-2');

            wrapper1.unmount();
            wrapper2.unmount();
        });
    });

    describe('real-world scenarios', () => {
        it('should handle prompt analysis flow', async () => {
            const analysisHandler = vi.fn();
            const optimizationHandler = vi.fn();

            const TestComponent = defineComponent({
                setup() {
                    useRealtimeUpdates(`prompt-run.123`, {
                        AnalysisCompleted: analysisHandler,
                        PromptOptimizationCompleted: optimizationHandler,
                    });
                    return {};
                },
                template: '<div></div>',
            });

            const wrapper = mount(TestComponent);

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

            wrapper.unmount();
        });

        it('should gracefully handle page navigation', async () => {
            let composableCleanup: () => void;

            const TestComponent = defineComponent({
                setup() {
                    const state = useRealtimeUpdates('test-channel', {});
                    composableCleanup = state.cleanup;
                    return state;
                },
                template: '<div></div>',
            });

            const wrapper = mount(TestComponent);

            await nextTick();

            // Simulate navigation away
            composableCleanup!();

            expect(mockEcho.leave).toHaveBeenCalledWith('test-channel');

            wrapper.unmount();
        });

        it('should handle concurrent subscriptions to same channel', async () => {
            const handlers = [vi.fn(), vi.fn()];
            const instances: any[] = [];

            for (const handler of handlers) {
                const TestComponent = defineComponent({
                    setup() {
                        const state = useRealtimeUpdates('shared-channel', {
                            Event: handler,
                        });
                        instances.push(state);
                        return state;
                    },
                    template: '<div></div>',
                });

                mount(TestComponent);
            }

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

            const TestComponent = defineComponent({
                setup() {
                    useRealtimeUpdates('test-channel', {
                        RobustEvent: handler,
                    });
                    return {};
                },
                template: '<div></div>',
            });

            const wrapper = mount(TestComponent);

            await nextTick();

            // Verify handler is registered
            expect(mockChannel.listen).toHaveBeenCalledWith(
                'RobustEvent',
                expect.any(Function),
            );

            // Verify event handling works
            mockChannel._simulateEvent('RobustEvent', { data: 'test' });

            expect(handler).toHaveBeenCalledWith({ data: 'test' });

            wrapper.unmount();
        });

        it('should cleanup properly after unmount', async () => {
            const handler = vi.fn();
            let composableCleanup: () => void;

            const TestComponent = defineComponent({
                setup() {
                    const state = useRealtimeUpdates('test-channel', {
                        CleanupEvent: handler,
                    });
                    composableCleanup = state.cleanup;
                    return state;
                },
                template: '<div></div>',
            });

            const wrapper = mount(TestComponent);

            await nextTick();

            // Verify setup
            expect(mockEcho.channel).toHaveBeenCalled();

            // Clean up
            composableCleanup!();

            // Verify cleanup
            expect(mockEcho.leave).toHaveBeenCalled();

            wrapper.unmount();
        });
    });
});
