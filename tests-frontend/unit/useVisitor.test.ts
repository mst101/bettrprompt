import { useVisitor } from '@/Composables/useVisitor';
import { usePage } from '@inertiajs/vue3';
import { beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock('@inertiajs/vue3', () => ({
    usePage: vi.fn(),
}));

describe('useVisitor', () => {
    let mockUsePage: any;

    beforeEach(() => {
        mockUsePage = usePage as any;
    });

    describe('Visitor ID extraction', () => {
        it('should extract visitor ID from page props', () => {
            mockUsePage.mockReturnValue({
                props: {
                    visitor: {
                        id: 'visitor-123',
                    },
                },
            });

            const { visitorId } = useVisitor();

            expect(visitorId.value).toBe('visitor-123');
        });

        it('should return null when no visitor in props', () => {
            mockUsePage.mockReturnValue({
                props: {},
            });

            const { visitorId } = useVisitor();

            expect(visitorId.value).toBeNull();
        });

        it('should return null when visitor is undefined', () => {
            mockUsePage.mockReturnValue({
                props: {
                    visitor: undefined,
                },
            });

            const { visitorId } = useVisitor();

            expect(visitorId.value).toBeNull();
        });

        it('should return null when visitor is null', () => {
            mockUsePage.mockReturnValue({
                props: {
                    visitor: null,
                },
            });

            const { visitorId } = useVisitor();

            expect(visitorId.value).toBeNull();
        });

        it('should handle multiple different visitor IDs', () => {
            const visitorIds = [
                'visitor-001',
                'visitor-abc-def',
                'anon-xyz',
                'test-visitor-999',
            ];

            for (const id of visitorIds) {
                mockUsePage.mockReturnValue({
                    props: {
                        visitor: { id },
                    },
                });

                const { visitorId } = useVisitor();
                expect(visitorId.value).toBe(id);
            }
        });
    });

    describe('Visitor object', () => {
        it('should return visitor object with id property', () => {
            mockUsePage.mockReturnValue({
                props: {
                    visitor: {
                        id: 'visitor-456',
                    },
                },
            });

            const { visitor } = useVisitor();

            expect(visitor.value).toEqual({ id: 'visitor-456' });
        });

        it('should handle visitor object with additional properties', () => {
            mockUsePage.mockReturnValue({
                props: {
                    visitor: {
                        id: 'visitor-789',
                        email: 'test@example.com',
                        country: 'gb',
                    },
                },
            });

            const { visitor } = useVisitor();

            expect(visitor.value).toEqual({
                id: 'visitor-789',
                email: 'test@example.com',
                country: 'gb',
            });
        });
    });

    describe('isVisitorReady computed property', () => {
        it('should be true when visitor ID exists', () => {
            mockUsePage.mockReturnValue({
                props: {
                    visitor: {
                        id: 'visitor-ready',
                    },
                },
            });

            const { isVisitorReady } = useVisitor();

            expect(isVisitorReady.value).toBe(true);
        });

        it('should be false when no visitor ID', () => {
            mockUsePage.mockReturnValue({
                props: {},
            });

            const { isVisitorReady } = useVisitor();

            expect(isVisitorReady.value).toBe(false);
        });

        it('should be false when visitor is null', () => {
            mockUsePage.mockReturnValue({
                props: {
                    visitor: null,
                },
            });

            const { isVisitorReady } = useVisitor();

            expect(isVisitorReady.value).toBe(false);
        });

        it('should be false when visitor is undefined', () => {
            mockUsePage.mockReturnValue({
                props: {
                    visitor: undefined,
                },
            });

            const { isVisitorReady } = useVisitor();

            expect(isVisitorReady.value).toBe(false);
        });

        it('should be false when visitor has no id property', () => {
            mockUsePage.mockReturnValue({
                props: {
                    visitor: {
                        name: 'John',
                    },
                },
            });

            const { isVisitorReady } = useVisitor();

            expect(isVisitorReady.value).toBe(false);
        });

        it('should be false when visitor id is empty string', () => {
            mockUsePage.mockReturnValue({
                props: {
                    visitor: {
                        id: '',
                    },
                },
            });

            const { isVisitorReady } = useVisitor();

            expect(isVisitorReady.value).toBe(false);
        });

        it('should be true when visitor id is any non-empty string', () => {
            const testIds = ['a', '0', 'false', ' ', 'visitor-with-spaces '];

            for (const id of testIds) {
                mockUsePage.mockReturnValue({
                    props: {
                        visitor: { id },
                    },
                });

                const { isVisitorReady } = useVisitor();
                expect(isVisitorReady.value).toBe(true);
            }
        });
    });

    describe('Real-world usage scenarios', () => {
        it('should work with new visitor (first page load)', () => {
            mockUsePage.mockReturnValue({
                props: {
                    visitor: {
                        id: 'new-visitor-uuid-abc123',
                    },
                },
            });

            const { visitorId, isVisitorReady } = useVisitor();

            expect(isVisitorReady.value).toBe(true);
            expect(visitorId.value).toBe('new-visitor-uuid-abc123');
        });

        it('should work with returning visitor', () => {
            mockUsePage.mockReturnValue({
                props: {
                    visitor: {
                        id: 'returning-visitor-xyz789',
                    },
                },
            });

            const { visitorId, isVisitorReady } = useVisitor();

            expect(isVisitorReady.value).toBe(true);
            expect(visitorId.value).toBe('returning-visitor-xyz789');
        });

        it('should work when visitor page loads without visitor ID yet', () => {
            mockUsePage.mockReturnValue({
                props: {},
            });

            const { isVisitorReady } = useVisitor();

            expect(isVisitorReady.value).toBe(false);
        });
    });

    describe('Type safety', () => {
        it('should cast visitor to correct type', () => {
            mockUsePage.mockReturnValue({
                props: {
                    visitor: {
                        id: 'test-visitor',
                    },
                },
            });

            const { visitor } = useVisitor();

            // Verify we can access id property without type errors
            const id: string | null = visitor.value?.id || null;

            expect(id).toBe('test-visitor');
        });

        it('should cast visitorId to string or null', () => {
            mockUsePage.mockReturnValue({
                props: {
                    visitor: {
                        id: 'test-id',
                    },
                },
            });

            const { visitorId } = useVisitor();

            // Verify type is string | null
            const id: string | null = visitorId.value;

            expect(typeof id === 'string' || id === null).toBe(true);
        });
    });
});
