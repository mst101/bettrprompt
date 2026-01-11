import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

/**
 * Composable for generating country-aware routes
 * Automatically injects the current country code into route parameters
 *
 * Country code is the 2-letter ISO code (gb, us, mx, etc.)
 * Locale is the full language code (en-GB, es-MX, etc.)
 * Currency is the 3-letter currency code (GBP, USD, etc.)
 */
export function useCountryRoute() {
    const page = usePage();

    // Country code from URL (gb, mx, sg)
    const currentCountry = computed(
        () => (page.props.country as string) || 'gb',
    );

    // Full locale for translations (en-GB, es-MX)
    const currentLocale = computed(
        () => (page.props.locale as string) || 'en-GB',
    );

    // Currency code for pricing (GBP, USD, EUR)
    const currentCurrency = computed(
        () => (page.props.currency as string) || 'GBP',
    );

    /**
     * Generate a route URL with country parameter automatically injected
     */
    const countryRoute = (
        name: string,
        parameters?: Record<string, unknown>,
    ) => {
        return route(name, {
            country: currentCountry.value,
            ...(parameters || {}),
        });
    };

    return {
        currentCountry,
        currentLocale,
        currentCurrency,
        countryRoute,
    };
}
