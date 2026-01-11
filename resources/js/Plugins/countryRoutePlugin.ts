import { usePage } from '@inertiajs/vue3';
import type { App } from 'vue';

/**
 * Vue plugin to provide countryRoute as a global helper function
 * Automatically injects the current country into route parameters
 */
export function createCountryRoutePlugin() {
    return {
        install(app: App) {
            // Make countryRoute available as a global function
            const countryRoute = (
                name: string,
                parameters?: Record<string, any>,
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
            (window as any).countryRoute = countryRoute;
        },
    };
}
