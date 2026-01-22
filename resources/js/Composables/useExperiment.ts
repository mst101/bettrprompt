import { useCookieConsent } from '@/Composables/features/useCookieConsent';
import { analyticsService } from '@/services/analytics';
import { usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';

interface ExperimentAssignment {
    experimentId: number;
    experimentSlug: string;
    variantSlug: string;
    variantId: number;
    config: Record<string, unknown> | null;
}

/**
 * Composable for using experiments and tracking exposures
 * Provides access to experiment variants, variant config, and exposure tracking
 *
 * Usage:
 * ```typescript
 * const { variant, config, isExposed } = useExperiment('pricing-layout');
 *
 * if (variant.value === 'variant_a') {
 *   // Show variant A UI
 * } else {
 *   // Show control UI
 * }
 * ```
 */
export function useExperiment(experimentSlug: string) {
    const page = usePage<{
        experiments?: ExperimentAssignment[];
    }>();

    const { hasConsentFor } = useCookieConsent();

    const assignment = computed(() => {
        const experiments =
            (page.props.experiments as ExperimentAssignment[]) || [];
        return experiments.find((exp) => exp.experimentSlug === experimentSlug);
    });

    const variant = computed(() => assignment.value?.variantSlug || null);

    const config = computed(() => assignment.value?.config || null);

    const experimentId = computed(() => assignment.value?.experimentId);

    const variantId = computed(() => assignment.value?.variantId);

    const isAssigned = computed(() => !!assignment.value);

    const hasExposed = ref(false);

    /**
     * Track an exposure event when the variant is rendered
     * Should be called when the component actually displays the variant UI
     */
    const trackExposure = (componentName?: string) => {
        // Don't track if no assignment
        if (!isAssigned.value) {
            return;
        }

        // Don't track multiple exposures from same visit (deduplicate)
        if (hasExposed.value) {
            return;
        }

        // Don't track without analytics consent
        if (!hasConsentFor('analytics')) {
            return;
        }

        hasExposed.value = true;

        // Track exposure event
        analyticsService.track({
            name: 'experiment_exposure',
            properties: {
                experiment_slug: experimentSlug,
                experiment_id: experimentId.value,
                variant_slug: variant.value,
                variant_id: variantId.value,
                component: componentName,
            },
        });
    };

    /**
     * Auto-track exposure when the composable is used
     * Call this in onMounted of your component
     */
    const autoTrackExposure = (componentName?: string) => {
        onMounted(() => {
            trackExposure(componentName);
        });
    };

    /**
     * Get variant config value with optional default
     */
    const getConfigValue = (key: string, defaultValue?: unknown) => {
        return (config.value?.[key] ?? defaultValue) as unknown;
    };

    return {
        // Assignment data
        variant,
        config,
        isAssigned,
        experimentId,
        variantId,

        // Methods
        trackExposure,
        autoTrackExposure,
        getConfigValue,

        // Computed
        hasExposed,
    };
}
