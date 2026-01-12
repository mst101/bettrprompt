import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

/**
 * Composable for accessing visitor context
 * Provides server-derived visitor ID for analytics and experiments
 */
export function useVisitor() {
    const page = usePage<{
        visitor?: {
            id: string;
        };
    }>();

    const visitor = computed(() => page.props.visitor as { id: string } | null);

    const visitorId = computed(() => page.props.visitor?.id || null);

    const isVisitorReady = computed(() => !!visitorId.value);

    return {
        visitor,
        visitorId,
        isVisitorReady,
    };
}
