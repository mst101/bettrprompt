import { Config } from 'ziggy-js';
import type { UserResource } from './resources';
import type { SubscriptionStatus } from './shared/subscription';

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth?: {
        user?: UserResource;
    };
    ziggy: Config & { location: string };
    subscription?: SubscriptionStatus | null;
};

// Re-export everything from shared
export * from './shared';

// Re-export everything from models
export * from './models';

// Re-export everything from resources
export * from './resources';

// Re-export n8n types
export * from './integrations/n8n';

// Re-export form types
export * from './form';
