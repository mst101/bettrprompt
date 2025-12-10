import { useRealtimeUpdates } from '@/Composables/useRealtimeUpdates';
import { mount } from '@vue/test-utils';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { defineComponent, nextTick } from 'vue';

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

            let composableState: any;

            const TestComponent = defineComponent({
                setup() {
                    composableState = useRealtimeUpdates(channelName, events);
                    return composableState;
                },
                template: '<div></div>',
            });

            const wrapper = mount(TestComponent);

            await nextTick();

            expect(window.Echo.channel).toHaveBeenCalledWith(channelName);
            expect(mockChannel.listen).toHaveBeenCalledWith(
                'TestEvent',
                expect.any(Function),
            );
            expect(composableState.connected.value).toBe(true);

            wrapper.unmount();
        });

        it('should register multiple event handlers', async () => {
            const channelName = 'prompt-run.123';
            const events = {
                AnalysisCompleted: vi.fn(),
                PromptOptimizationCompleted: vi.fn(),
            };

            const TestComponent = defineComponent({
                setup() {
                    useRealtimeUpdates(channelName, events);
                    return {};
                },
                template: '<div></div>',
            });

            const wrapper = mount(TestComponent);

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

            wrapper.unmount();
        });
    });

    describe('event handling', () => {
        it('should call event handler when event is triggered', async () => {
            const handler = vi.fn();
            const events = { TestEvent: handler };

            const TestComponent = defineComponent({
                setup() {
                    useRealtimeUpdates('test-channel', events);
                    return {};
                },
                template: '<div></div>',
            });

            const wrapper = mount(TestComponent);

            await nextTick();

            // Get the handler that was registered and call it
            const registeredHandler = mockChannel.listen.mock.calls[0][1];
            const testData = { id: 123, status: 'completed' };
            registeredHandler(testData);

            expect(handler).toHaveBeenCalledWith(testData);

            wrapper.unmount();
        });

        it('should handle handler errors gracefully', async () => {
            const errorHandler = vi.fn().mockImplementation(() => {
                throw new Error('Handler error');
            });
            const events = { TestEvent: errorHandler };
            const consoleSpy = vi.spyOn(console, 'error');

            const TestComponent = defineComponent({
                setup() {
                    useRealtimeUpdates('test-channel', events);
                    return {};
                },
                template: '<div></div>',
            });

            const wrapper = mount(TestComponent);

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

            wrapper.unmount();
        });

        it('should register channel error handler', async () => {
            const TestComponent = defineComponent({
                setup() {
                    useRealtimeUpdates('test-channel', {});
                    return {};
                },
                template: '<div></div>',
            });

            const wrapper = mount(TestComponent);

            await nextTick();

            expect(mockChannel.error).toHaveBeenCalledWith(
                expect.any(Function),
            );

            wrapper.unmount();
        });
    });

    describe('fallback polling', () => {
        it('should start polling when Echo is not available', async () => {
            // Make Echo unavailable by setting it to undefined
            global.window.Echo = undefined;

            let composableState: any;

            const TestComponent = defineComponent({
                setup() {
                    composableState = useRealtimeUpdates('test-channel', {});
                    return {};
                },
                template: '<div></div>',
            });

            const wrapper = mount(TestComponent);

            await nextTick();

            expect(composableState.usingFallback.value).toBe(true);

            wrapper.unmount();
        });

        it('should start polling when Echo channel creation fails', async () => {
            global.window.Echo.channel = vi.fn().mockReturnValue(null);

            let composableState: any;

            const TestComponent = defineComponent({
                setup() {
                    composableState = useRealtimeUpdates('test-channel', {});
                    return {};
                },
                template: '<div></div>',
            });

            const wrapper = mount(TestComponent);

            await nextTick();

            expect(composableState.usingFallback.value).toBe(true);

            wrapper.unmount();
        });
    });

    describe('channel cleanup', () => {
        it('should leave channel on unmount', async () => {
            global.window.Echo.leave = vi.fn();

            let composableState: any;

            const TestComponent = defineComponent({
                setup() {
                    composableState = useRealtimeUpdates('test-channel', {});
                    return {};
                },
                template: '<div></div>',
            });

            const wrapper = mount(TestComponent);

            await nextTick();

            composableState.cleanup();

            expect(global.window.Echo.leave).toHaveBeenCalledWith(
                'test-channel',
            );

            wrapper.unmount();
        });

        it('should stop polling on cleanup', async () => {
            // Make Echo unavailable to trigger fallback polling
            global.window.Echo = undefined;

            let composableState: any;

            const TestComponent = defineComponent({
                setup() {
                    composableState = useRealtimeUpdates('test-channel', {});
                    return {};
                },
                template: '<div></div>',
            });

            const wrapper = mount(TestComponent);

            await nextTick();
            expect(composableState.usingFallback.value).toBe(true);

            composableState.cleanup();

            expect(composableState.usingFallback.value).toBe(false);

            wrapper.unmount();
        });

        it('should remove event listeners on cleanup', async () => {
            const removeEventListenerSpy = vi.spyOn(
                window,
                'removeEventListener',
            );

            let composableState: any;

            const TestComponent = defineComponent({
                setup() {
                    composableState = useRealtimeUpdates('test-channel', {});
                    return {};
                },
                template: '<div></div>',
            });

            const wrapper = mount(TestComponent);

            await nextTick();

            composableState.cleanup();

            expect(removeEventListenerSpy).toHaveBeenCalledWith(
                'echo-disconnected',
                expect.any(Function),
            );
            expect(removeEventListenerSpy).toHaveBeenCalledWith(
                'echo-connected',
                expect.any(Function),
            );

            removeEventListenerSpy.mockRestore();

            wrapper.unmount();
        });

        it('should handle cleanup errors gracefully', async () => {
            global.window.Echo.leave = vi.fn().mockImplementation(() => {
                throw new Error('Channel leave error');
            });
            const consoleSpy = vi.spyOn(console, 'error');

            let composableState: any;

            const TestComponent = defineComponent({
                setup() {
                    composableState = useRealtimeUpdates('test-channel', {});
                    return {};
                },
                template: '<div></div>',
            });

            const wrapper = mount(TestComponent);

            await nextTick();

            // Should not throw
            expect(() => composableState.cleanup()).not.toThrow();
            expect(consoleSpy).toHaveBeenCalledWith(
                '[useRealtimeUpdates] Error leaving channel:',
                expect.any(Error),
            );

            wrapper.unmount();
        });
    });

    describe('connection state', () => {
        it('should reflect connected state', async () => {
            let composableState: any;

            const TestComponent = defineComponent({
                setup() {
                    composableState = useRealtimeUpdates('test-channel', {});
                    return {};
                },
                template: '<div></div>',
            });

            const wrapper = mount(TestComponent);

            await nextTick();

            expect(composableState.connected.value).toBe(true);

            wrapper.unmount();
        });

        it('should mark as not connected when channel fails', async () => {
            global.window.Echo.channel = vi.fn().mockReturnValue(null);

            let composableState: any;

            const TestComponent = defineComponent({
                setup() {
                    composableState = useRealtimeUpdates('test-channel', {});
                    return {};
                },
                template: '<div></div>',
            });

            const wrapper = mount(TestComponent);

            await nextTick();

            expect(composableState.connected.value).toBe(false);

            wrapper.unmount();
        });
    });

    describe('lifecycle and recovery', () => {
        it('should attach listeners for Echo reconnection', async () => {
            const addEventListenerSpy = vi.spyOn(window, 'addEventListener');

            const TestComponent = defineComponent({
                setup() {
                    useRealtimeUpdates('test-channel', {});
                    return {};
                },
                template: '<div></div>',
            });

            const wrapper = mount(TestComponent);

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

            wrapper.unmount();
        });

        it('should recover from Echo disconnect by polling', async () => {
            const addEventListenerSpy = vi.spyOn(window, 'addEventListener');

            let composableState: any;

            const TestComponent = defineComponent({
                setup() {
                    composableState = useRealtimeUpdates('test-channel', {});
                    return {};
                },
                template: '<div></div>',
            });

            const wrapper = mount(TestComponent);

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
                expect(composableState.usingFallback.value).toBe(true);
            }

            addEventListenerSpy.mockRestore();

            wrapper.unmount();
        });

        it('should stop polling when Echo reconnects', async () => {
            // Make Echo unavailable initially
            global.window.Echo = undefined;
            const addEventListenerSpy = vi.spyOn(window, 'addEventListener');

            let composableState: any;

            const TestComponent = defineComponent({
                setup() {
                    composableState = useRealtimeUpdates('test-channel', {});
                    return {};
                },
                template: '<div></div>',
            });

            const wrapper = mount(TestComponent);

            await nextTick();

            expect(composableState.usingFallback.value).toBe(true);

            // Get reconnect handler and trigger it
            const reconnectHandler =
                addEventListenerSpy.mock.calls.find(
                    (call: Array<unknown>) => call[0] === 'echo-connected',
                )?.[1] || null;

            // Now make Echo available again
            global.window.Echo = {
                channel: vi.fn().mockReturnValue(mockChannel),
                leave: vi.fn(),
            };

            if (reconnectHandler) {
                (reconnectHandler as () => void)();
                await nextTick();

                // Should stop polling
                expect(composableState.usingFallback.value).toBe(false);
            }

            addEventListenerSpy.mockRestore();

            wrapper.unmount();
        });
    });

    describe('edge cases', () => {
        it('should handle missing Echo gracefully', async () => {
            // Simulate missing Echo by making it fail to connect
            global.window.Echo.channel = vi.fn().mockReturnValue(null);
            global.window.isEchoConnected = vi.fn().mockReturnValue(false);

            let composableState: any;

            const TestComponent = defineComponent({
                setup() {
                    composableState = useRealtimeUpdates('test-channel', {});
                    return {};
                },
                template: '<div></div>',
            });

            const wrapper = mount(TestComponent);

            await nextTick();

            expect(composableState.connected.value).toBe(false);
            expect(composableState.usingFallback.value).toBe(true);

            wrapper.unmount();
        });

        it('should not start polling twice', async () => {
            // Make Echo unavailable to trigger fallback polling
            global.window.Echo = undefined;

            let composableState: any;

            const TestComponent = defineComponent({
                setup() {
                    composableState = useRealtimeUpdates(
                        'test-channel',
                        {},
                        {},
                        1000,
                    );
                    return {};
                },
                template: '<div></div>',
            });

            const wrapper = mount(TestComponent);

            await nextTick();

            expect(composableState.usingFallback.value).toBe(true);

            // Advance time and trigger polling multiple times
            vi.advanceTimersByTime(1000);
            vi.advanceTimersByTime(1000);

            // Should only set one interval, not multiple
            const intervalCalls = vi.getTimerCount();
            expect(intervalCalls).toBeLessThanOrEqual(2); // One interval + one check interval

            wrapper.unmount();
        });

        it('should work with empty event handlers', async () => {
            let composableState: any;

            const TestComponent = defineComponent({
                setup() {
                    composableState = useRealtimeUpdates('test-channel', {});
                    return {};
                },
                template: '<div></div>',
            });

            const wrapper = mount(TestComponent);

            await nextTick();

            expect(composableState.connected.value).toBe(true);
            expect(mockChannel.listen).not.toHaveBeenCalled();

            wrapper.unmount();
        });

        it('should handle channel error without already polling', async () => {
            let composableState: any;

            const TestComponent = defineComponent({
                setup() {
                    composableState = useRealtimeUpdates('test-channel', {});
                    return {};
                },
                template: '<div></div>',
            });

            const wrapper = mount(TestComponent);

            await nextTick();

            // Simulate channel error
            const errorHandler = mockChannel.error.mock.calls[0][0];
            errorHandler(new Error('Channel error'));

            await nextTick();

            expect(composableState.usingFallback.value).toBe(true);

            wrapper.unmount();
        });
    });

    describe('integration scenarios', () => {
        it('should handle real-time event flow correctly', async () => {
            const eventHandlers = {
                AnalysisCompleted: vi.fn(),
                PromptOptimizationCompleted: vi.fn(),
            };

            let composableState: any;

            const TestComponent = defineComponent({
                setup() {
                    composableState = useRealtimeUpdates(
                        'prompt-run.123',
                        eventHandlers,
                    );
                    return {};
                },
                template: '<div></div>',
            });

            const wrapper = mount(TestComponent);

            await nextTick();

            // Simulate receiving an event
            const analysisHandler = mockChannel.listen.mock.calls[0][1];
            const eventData = { promptRunId: 123, status: 'completed' };
            analysisHandler(eventData);

            expect(eventHandlers.AnalysisCompleted).toHaveBeenCalledWith(
                eventData,
            );
            expect(composableState.connected.value).toBe(true);

            wrapper.unmount();
        });

        it('should gracefully degrade from WebSocket to polling', async () => {
            global.window.isEchoConnected = vi
                .fn()
                .mockReturnValueOnce(true)
                .mockReturnValueOnce(false);

            let composableState: any;

            const TestComponent = defineComponent({
                setup() {
                    composableState = useRealtimeUpdates('test-channel', {});
                    return {};
                },
                template: '<div></div>',
            });

            const wrapper = mount(TestComponent);

            await nextTick();

            // Initially should use WebSocket
            expect(composableState.usingFallback.value).toBe(false);

            // Simulate Echo disconnection and trigger channel error
            const channelErrorHandler = mockChannel.error.mock.calls[0][0];
            channelErrorHandler(new Error('Connection lost'));

            await nextTick();

            // Should fallback to polling
            expect(composableState.usingFallback.value).toBe(true);

            wrapper.unmount();
        });
    });
});
