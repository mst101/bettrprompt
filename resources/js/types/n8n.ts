/**
 * TypeScript definitions for n8n webhook responses
 */

export interface N8nErrorDetails {
    readonly httpCode?: string;
    readonly errorType?: string;
    readonly description?: string;
    readonly apiMessage?: string;
    readonly nodeName?: string;
    readonly time?: string;
    readonly rawError?: string;
}

export interface N8nErrorResponse {
    readonly promptRunId: number;
    readonly success: false;
    readonly error: string;
    readonly details: N8nErrorDetails;
}

/**
 * n8nResponsePayload can be either an error response or a success response (generic object)
 */
export type N8nResponsePayload = N8nErrorResponse | Record<string, unknown>;
