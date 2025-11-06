import { Config } from 'ziggy-js';

export interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at?: string;
    personalityType?: string | null;
    traitPercentages?: {
        mind: number | null;
        energy: number | null;
        nature: number | null;
        tactics: number | null;
        identity: number | null;
    } | null;
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth?: {
        user?: User;
    };
    ziggy: Config & { location: string };
};

// Re-export everything from shared
export * from './shared';

// Re-export everything from models
export * from './models';

// Re-export everything from resources
export * from './resources';

// Re-export n8n types
export * from './n8n';
