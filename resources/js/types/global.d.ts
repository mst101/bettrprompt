declare global {
    /**
     * Global countryRoute helper function
     * Automatically injects the current locale into route parameters
     */
    function countryRoute(
        name: string,
        parameters?: Record<string, any>,
    ): string;
}

declare module 'vue' {
    interface ComponentCustomProperties {
        /**
         * Generate a route URL with locale parameter automatically injected
         * Available as a global helper on all Vue components
         */
        countryRoute(name: string, parameters?: Record<string, any>): string;
    }
}

export {};
