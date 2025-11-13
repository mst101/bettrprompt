import { PageProps as InertiaPageProps } from '@inertiajs/core';
import { AxiosInstance } from 'axios';
import Echo from 'laravel-echo';
import { route as ziggyRoute } from 'ziggy-js';
import { PageProps as AppPageProps } from './';

declare global {
    interface Window {
        axios: AxiosInstance;
        Echo: Echo<any> | null;
        Pusher: any;
        isEchoConnected: () => boolean;
        getEchoConnectionState: () =>
            | 'connected'
            | 'connecting'
            | 'disconnected'
            | 'failed';
        FS?: {
            (
                method: 'trackEvent',
                eventData: {
                    name: string;
                    properties: Record<string, any>;
                },
            ): void;
            identify(
                uid: string | number,
                customVars?: Record<string, any>,
            ): void;
            setUserVars(customVars: Record<string, any>): void;
            event(eventName: string, properties?: Record<string, any>): void;
            anonymize(): void;
            shutdown(): void;
            restart(): void;
        };
    }

    var route: typeof ziggyRoute;
}

declare module 'vue' {
    interface ComponentCustomProperties {
        route: typeof ziggyRoute;
    }
}

declare module '@inertiajs/core' {
    interface PageProps extends InertiaPageProps, AppPageProps {}
}
