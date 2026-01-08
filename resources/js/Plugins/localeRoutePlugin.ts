import { usePage } from '@inertiajs/vue3';
import type { App } from 'vue';

/**
 * Vue plugin to provide localeRoute as a global helper function
 * Automatically injects the current locale into route parameters
 */
export function createLocaleRoutePlugin() {
    return {
        install(app: App) {
            // Make localeRoute available as a global function
            const localeRoute = (
                name: string,
                parameters?: Record<string, any>,
            ) => {
                const page = usePage();
                const locale = (page.props.locale as string) || 'en';

                return route(name, {
                    locale,
                    ...(parameters || {}),
                });
            };

            // Expose as global property for templates
            app.config.globalProperties.localeRoute = localeRoute;

            // Also expose on window for module usage
            (window as any).localeRoute = localeRoute;
        },
    };
}
