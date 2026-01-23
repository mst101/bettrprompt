import { usePage } from '@inertiajs/vue3';
import type { App } from 'vue';

type CountryRouteFunction = (
    name: string,
    parameters?: Record<string, string | number>,
) => string;

declare global {
    interface Window {
        countryRoute: CountryRouteFunction;
    }
}

/**
 * Vue plugin to provide countryRoute as a global helper function
 * Automatically injects the current country into route parameters
 */
export function createCountryRoutePlugin() {
    return {
        install(app: App) {
            // Make countryRoute available as a global function
            const countryRoute: CountryRouteFunction = (
                name: string,
                parameters?: Record<string, string | number>,
            ) => {
                const page = usePage();
                const country = (page.props.country as string) || 'gb';

                return route(name, {
                    country,
                    ...(parameters || {}),
                });
            };

            // Expose as global property for templates
            app.config.globalProperties.countryRoute = countryRoute;

            // Also expose on window for module usage
            window.countryRoute = countryRoute;
        },
    };
}
