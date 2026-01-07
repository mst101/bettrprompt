/**
 * Augment the window object with custom properties
 */
declare global {
    interface Window {
        /**
         * Flag set when a component handles a CSRF token refresh event
         * Prevents the global error handler from reloading the page
         */
        csrfTokenRefreshHandled?: boolean;

        /**
         * Fullstory integration
         */
        FS?: {
            identify: (
                userId: string,
                attributes?: Record<string, unknown>,
            ) => void;
        };

        /**
         * Echo for real-time broadcasting
         */
        Echo?: unknown;

        /**
         * Helper to check if Echo is connected
         */
        isEchoConnected?: () => boolean;

        /**
         * Helper to get Echo connection state
         */
        getEchoConnectionState?: () => string;

        /**
         * Axios instance for HTTP requests
         */
        axios?: {
            defaults: {
                headers: {
                    common?: Record<string, string>;
                };
            };
            [key: string]: unknown;
        };

        /**
         * Pusher library
         */
        Pusher?: unknown;
    }
}

export {};
