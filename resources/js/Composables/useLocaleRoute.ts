import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

/**
 * Composable for generating locale-aware routes
 * Automatically injects the current locale into route parameters
 */
export function useLocaleRoute() {
    const page = usePage();

    const currentLocale = computed(() => (page.props.locale as string) || 'en');

    /**
     * Generate a route URL with locale parameter automatically injected
     */
    const localeRoute = (name: string, parameters?: Record<string, any>) => {
        return route(name, {
            locale: currentLocale.value,
            ...(parameters || {}),
        });
    };

    return {
        currentLocale,
        localeRoute,
    };
}
