import { getCsrfToken } from '@/Utils/cookies';
import { logger } from '@/Utils/logger';
import { ref, type Ref } from 'vue';

export interface UseFetchOptions {
    method?: 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE';
    body?: unknown;
    headers?: Record<string, string>;
    retryOnError?: boolean;
    maxRetries?: number;
    timeout?: number;
    credentials?: RequestCredentials;
}

export interface UseFetchResult<T> {
    data: Ref<T | null>;
    error: Ref<string | null>;
    isLoading: Ref<boolean>;
    execute: () => Promise<T | null>;
}

/**
 * Centralized API fetch composable with error handling, retries, and CSRF token injection
 *
 * @example
 * const { data, error, isLoading, execute } = useFetch<ResponseType>(
 *     '/api/endpoint',
 *     { method: 'POST', body: { foo: 'bar' } }
 * );
 */
export function useFetch<T = unknown>(
    url: string,
    options: UseFetchOptions = {},
): UseFetchResult<T> {
    const {
        method = 'GET',
        body,
        headers: customHeaders = {},
        retryOnError = true,
        maxRetries = 3,
        timeout = 30000,
        credentials = 'same-origin',
    } = options;

    const data = ref<T | null>(null);
    const error = ref<string | null>(null);
    const isLoading = ref(false);

    /**
     * Build request headers with CSRF token injection
     */
    const buildHeaders = (): Record<string, string> => {
        const headers: Record<string, string> = {
            'Content-Type': 'application/json',
            ...customHeaders,
        };

        const csrfToken = getCsrfToken();
        if (csrfToken) {
            const decodedToken = decodeURIComponent(csrfToken);
            headers['X-CSRF-TOKEN'] = decodedToken;
            headers['X-XSRF-TOKEN'] = decodedToken;
        }

        return headers;
    };

    /**
     * Check if error is retryable (transient)
     */
    const isRetryableError = (status: number, error: unknown): boolean => {
        // Retry on network errors
        if (error instanceof TypeError) {
            return true;
        }

        // Retry on specific HTTP status codes
        return [408, 429, 500, 502, 503, 504].includes(status);
    };

    /**
     * Calculate exponential backoff delay
     */
    const getBackoffDelay = (attempt: number): number => {
        return Math.min(1000 * Math.pow(2, attempt), 30000);
    };

    /**
     * Execute fetch with retry logic
     */
    const execute = async (attempt = 0): Promise<T | null> => {
        isLoading.value = true;
        error.value = null;

        try {
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), timeout);

            const response = await fetch(url, {
                method,
                headers: buildHeaders(),
                credentials,
                body: body ? JSON.stringify(body) : undefined,
                signal: controller.signal,
            });

            clearTimeout(timeoutId);

            // Handle 419 token expiry
            if (response.status === 419) {
                error.value = 'Session expired. Please refresh and try again.';
                logger.warn('CSRF token expired (419 response)');
                return null;
            }

            if (!response.ok) {
                // Retry if configured and error is transient
                if (retryOnError && attempt < maxRetries) {
                    const delay = getBackoffDelay(attempt);
                    logger.debug(
                        `Retrying request (attempt ${attempt + 1}/${maxRetries}) after ${delay}ms`,
                    );
                    await new Promise((resolve) => setTimeout(resolve, delay));
                    return execute(attempt + 1);
                }

                throw new Error(
                    `HTTP ${response.status}: ${response.statusText}`,
                );
            }

            const result = await response.json();
            data.value = result;
            return result;
        } catch (err) {
            const isTimeout =
                err instanceof DOMException && err.name === 'AbortError';
            const errorMessage = isTimeout
                ? `Request timeout (${timeout}ms)`
                : err instanceof Error
                  ? err.message
                  : 'Unknown error';

            // Retry if transient error and retries available
            if (
                retryOnError &&
                attempt < maxRetries &&
                (isTimeout ||
                    (err instanceof TypeError && isRetryableError(0, err)))
            ) {
                const delay = getBackoffDelay(attempt);
                logger.debug(
                    `Retrying request (attempt ${attempt + 1}/${maxRetries}) after ${delay}ms due to ${isTimeout ? 'timeout' : 'network error'}`,
                );
                await new Promise((resolve) => setTimeout(resolve, delay));
                return execute(attempt + 1);
            }

            error.value = errorMessage;
            logger.error(`API request failed: ${url}`, err);
            return null;
        } finally {
            isLoading.value = false;
        }
    };

    return {
        data,
        error,
        isLoading,
        execute,
    };
}
