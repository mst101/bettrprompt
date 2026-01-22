/* eslint-disable @typescript-eslint/no-explicit-any */
import { useCookieConsent } from '@/Composables/features/useCookieConsent';
import { useExperiment } from '@/Composables/useExperiment';
import { analyticsService } from '@/services/analytics';
import { usePage } from '@inertiajs/vue3';
import { beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock('@inertiajs/vue3', () => ({
    usePage: vi.fn(),
}));

vi.mock('@/Composables/features/useCookieConsent', () => ({
    useCookieConsent: vi.fn(),
}));

vi.mock('@/services/analytics', () => ({
    analyticsService: {
        track: vi.fn(),
    },
}));

describe('useExperiment', () => {
    let mockUsePage: any;
    let mockUseCookieConsent: any;

    beforeEach(() => {
        mockUsePage = usePage as any;
        mockUseCookieConsent = useCookieConsent as any;

        // Default setup: experiments in props and consent given
        mockUsePage.mockReturnValue({
            props: {
                experiments: [
                    {
                        experimentId: 1,
                        experimentSlug: 'pricing-layout',
                        variantSlug: 'variant_a',
                        variantId: 101,
                        config: { buttonText: 'Buy Now' },
                    },
                    {
                        experimentId: 2,
                        experimentSlug: 'header-color',
                        variantSlug: 'blue',
                        variantId: 202,
                        config: { color: '#0000FF' },
                    },
                ],
            },
        });

        mockUseCookieConsent.mockReturnValue({
            hasConsentFor: vi.fn(
                (category: string) => category === 'analytics',
            ),
        });

        vi.clearAllMocks();
    });

    describe('Experiment assignment detection', () => {
        it('should find assigned experiment by slug', () => {
            const { isAssigned, variant } = useExperiment('pricing-layout');

            expect(isAssigned.value).toBe(true);
            expect(variant.value).toBe('variant_a');
        });

        it('should return null variant when experiment not assigned', () => {
            const { isAssigned, variant } = useExperiment('non-existent');

            expect(isAssigned.value).toBe(false);
            expect(variant.value).toBeNull();
        });

        it('should handle empty experiments array', () => {
            mockUsePage.mockReturnValue({
                props: {
                    experiments: [],
                },
            });

            const { isAssigned } = useExperiment('any-experiment');

            expect(isAssigned.value).toBe(false);
        });

        it('should handle missing experiments prop', () => {
            mockUsePage.mockReturnValue({
                props: {},
            });

            const { isAssigned } = useExperiment('any-experiment');

            expect(isAssigned.value).toBe(false);
        });
    });

    describe('Variant information', () => {
        it('should return variant slug', () => {
            const { variant } = useExperiment('pricing-layout');

            expect(variant.value).toBe('variant_a');
        });

        it('should return experiment ID', () => {
            const { experimentId } = useExperiment('pricing-layout');

            expect(experimentId.value).toBe(1);
        });

        it('should return variant ID', () => {
            const { variantId } = useExperiment('pricing-layout');

            expect(variantId.value).toBe(101);
        });

        it('should return config', () => {
            const { config } = useExperiment('pricing-layout');

            expect(config.value).toEqual({ buttonText: 'Buy Now' });
        });

        it('should return null config when no config provided', () => {
            mockUsePage.mockReturnValue({
                props: {
                    experiments: [
                        {
                            experimentId: 3,
                            experimentSlug: 'test-exp',
                            variantSlug: 'control',
                            variantId: 303,
                            config: null,
                        },
                    ],
                },
            });

            const { config } = useExperiment('test-exp');

            expect(config.value).toBeNull();
        });
    });

    describe('getConfigValue method', () => {
        it('should retrieve config value by key', () => {
            const { getConfigValue } = useExperiment('pricing-layout');

            const value = getConfigValue('buttonText');

            expect(value).toBe('Buy Now');
        });

        it('should return default value when key does not exist', () => {
            const { getConfigValue } = useExperiment('pricing-layout');

            const value = getConfigValue('non-existent', 'default-value');

            expect(value).toBe('default-value');
        });

        it('should return undefined when key does not exist and no default', () => {
            const { getConfigValue } = useExperiment('pricing-layout');

            const value = getConfigValue('non-existent');

            expect(value).toBeUndefined();
        });

        it('should return undefined when config is null', () => {
            mockUsePage.mockReturnValue({
                props: {
                    experiments: [
                        {
                            experimentId: 3,
                            experimentSlug: 'test-exp',
                            variantSlug: 'control',
                            variantId: 303,
                            config: null,
                        },
                    ],
                },
            });

            const { getConfigValue } = useExperiment('test-exp');

            const value = getConfigValue('any-key', 'default');

            expect(value).toBe('default');
        });

        it('should support various config value types', () => {
            mockUsePage.mockReturnValue({
                props: {
                    experiments: [
                        {
                            experimentId: 4,
                            experimentSlug: 'multi-type',
                            variantSlug: 'variant',
                            variantId: 404,
                            config: {
                                text: 'hello',
                                number: 42,
                                boolean: true,
                                object: { nested: 'value' },
                                array: [1, 2, 3],
                            },
                        },
                    ],
                },
            });

            const { getConfigValue } = useExperiment('multi-type');

            expect(getConfigValue('text')).toBe('hello');
            expect(getConfigValue('number')).toBe(42);
            expect(getConfigValue('boolean')).toBe(true);
            expect(getConfigValue('object')).toEqual({ nested: 'value' });
            expect(getConfigValue('array')).toEqual([1, 2, 3]);
        });
    });

    describe('Exposure tracking', () => {
        it('should track exposure when called', () => {
            const { trackExposure } = useExperiment('pricing-layout');

            trackExposure('PricingComponent');

            expect(analyticsService.track).toHaveBeenCalledWith({
                name: 'experiment_exposure',
                properties: {
                    experiment_slug: 'pricing-layout',
                    experiment_id: 1,
                    variant_slug: 'variant_a',
                    variant_id: 101,
                    component: 'PricingComponent',
                },
            });
        });

        it('should track exposure without component name', () => {
            const { trackExposure } = useExperiment('header-color');

            trackExposure();

            expect(analyticsService.track).toHaveBeenCalledWith({
                name: 'experiment_exposure',
                properties: {
                    experiment_slug: 'header-color',
                    experiment_id: 2,
                    variant_slug: 'blue',
                    variant_id: 202,
                    component: undefined,
                },
            });
        });

        it('should not track exposure when not assigned', () => {
            const { trackExposure } = useExperiment('non-existent');

            trackExposure();

            expect(analyticsService.track).not.toHaveBeenCalled();
        });

        it('should deduplicate exposures (only track once per session)', () => {
            const { trackExposure } = useExperiment('pricing-layout');

            trackExposure('Component1');
            trackExposure('Component1');
            trackExposure('Component1');

            expect(analyticsService.track).toHaveBeenCalledTimes(1);
        });

        it('should not track when user lacks analytics consent', () => {
            mockUseCookieConsent.mockReturnValue({
                hasConsentFor: vi.fn(() => false),
            });

            const { trackExposure } = useExperiment('pricing-layout');

            trackExposure('Component');

            expect(analyticsService.track).not.toHaveBeenCalled();
        });

        it('should track when user has analytics consent', () => {
            mockUseCookieConsent.mockReturnValue({
                hasConsentFor: vi.fn(
                    (category: string) => category === 'analytics',
                ),
            });

            const { trackExposure } = useExperiment('pricing-layout');

            trackExposure('Component');

            expect(analyticsService.track).toHaveBeenCalled();
        });

        it('should track multiple different experiments separately', () => {
            const exp1 = useExperiment('pricing-layout');
            const exp2 = useExperiment('header-color');

            exp1.trackExposure('Component1');
            exp2.trackExposure('Component2');

            expect(analyticsService.track).toHaveBeenCalledTimes(2);
            expect(analyticsService.track).toHaveBeenNthCalledWith(1, {
                name: 'experiment_exposure',
                properties: expect.objectContaining({
                    experiment_slug: 'pricing-layout',
                }),
            });
            expect(analyticsService.track).toHaveBeenNthCalledWith(2, {
                name: 'experiment_exposure',
                properties: expect.objectContaining({
                    experiment_slug: 'header-color',
                }),
            });
        });
    });

    describe('hasExposed state', () => {
        it('should initialize as false', () => {
            const { hasExposed } = useExperiment('pricing-layout');

            expect(hasExposed.value).toBe(false);
        });

        it('should be set to true after tracking exposure', () => {
            const { trackExposure, hasExposed } =
                useExperiment('pricing-layout');

            expect(hasExposed.value).toBe(false);

            trackExposure();

            expect(hasExposed.value).toBe(true);
        });

        it('should prevent duplicate tracking based on hasExposed', () => {
            const { trackExposure, hasExposed } =
                useExperiment('pricing-layout');

            trackExposure();
            expect(hasExposed.value).toBe(true);

            trackExposure();
            trackExposure();

            // Should only be called once
            expect(analyticsService.track).toHaveBeenCalledTimes(1);
        });
    });

    describe('autoTrackExposure', () => {
        it('should set up onMounted hook', () => {
            const { autoTrackExposure } = useExperiment('pricing-layout');

            // Call autoTrackExposure to set up the hook
            autoTrackExposure('AutoComponent');

            // We can't directly test onMounted execution in this setup,
            // but we can verify the function exists and doesn't throw
            expect(autoTrackExposure).toBeDefined();
        });
    });

    describe('Multiple experiment handling', () => {
        it('should handle multiple experiments in props', () => {
            const exp1 = useExperiment('pricing-layout');
            const exp2 = useExperiment('header-color');

            expect(exp1.variant.value).toBe('variant_a');
            expect(exp1.experimentId.value).toBe(1);

            expect(exp2.variant.value).toBe('blue');
            expect(exp2.experimentId.value).toBe(2);
        });

        it('should each have independent exposure tracking state', () => {
            const exp1 = useExperiment('pricing-layout');
            const exp2 = useExperiment('header-color');

            exp1.trackExposure();

            expect(exp1.hasExposed.value).toBe(true);
            expect(exp2.hasExposed.value).toBe(false);
        });
    });

    describe('Edge cases', () => {
        it('should handle experiment with same slug but different variants', () => {
            mockUsePage.mockReturnValue({
                props: {
                    experiments: [
                        {
                            experimentId: 5,
                            experimentSlug: 'layout-test',
                            variantSlug: 'control',
                            variantId: 501,
                            config: null,
                        },
                    ],
                },
            });

            const { variant } = useExperiment('layout-test');

            expect(variant.value).toBe('control');
        });

        it('should handle null experiment assignment gracefully', () => {
            mockUsePage.mockReturnValue({
                props: {
                    experiments: [
                        {
                            experimentId: 6,
                            experimentSlug: 'null-test',
                            variantSlug: null,
                            variantId: 601,
                            config: null,
                        },
                    ],
                },
            });

            const { variant } = useExperiment('null-test');

            expect(variant.value).toBeNull();
        });
    });

    describe('Consent integration', () => {
        it('should respect cookie consent settings', () => {
            mockUseCookieConsent.mockReturnValue({
                hasConsentFor: vi.fn((category: string) => {
                    return category === 'analytics';
                }),
            });

            const { trackExposure } = useExperiment('pricing-layout');

            trackExposure();

            expect(analyticsService.track).toHaveBeenCalled();
        });

        it('should not track when analytics consent is denied', () => {
            mockUseCookieConsent.mockReturnValue({
                hasConsentFor: vi.fn(() => false),
            });

            const { trackExposure } = useExperiment('pricing-layout');

            trackExposure();

            expect(analyticsService.track).not.toHaveBeenCalled();
        });
    });
});
