<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class N8nClient
{
    protected string $baseUrl;

    protected string $username;

    protected string $password;

    protected ?string $apiKey;

    protected int $timeout = 30; // seconds

    protected int $maxRetries = 3;

    protected int $circuitBreakerThreshold = 5; // failures before opening circuit

    protected int $circuitBreakerTimeout = 300; // seconds (5 minutes)

    public function __construct()
    {
        $this->baseUrl = config('services.n8n.url');
        $this->username = config('services.n8n.username');
        $this->password = config('services.n8n.password');
        $this->apiKey = config('services.n8n.api_key');

        // Validate configuration
        $this->validateConfiguration();
    }

    /**
     * Validate that N8n service is properly configured
     *
     * @throws RuntimeException
     */
    protected function validateConfiguration(): void
    {
        if (! $this->baseUrl) {
            throw new RuntimeException('N8n service URL is not configured. Check N8N_INTERNAL_URL environment variable.');
        }

        // Either API key or basic auth credentials required
        if (! $this->apiKey && (! $this->username || ! $this->password)) {
            throw new RuntimeException('N8n service requires either N8N_API_KEY or (N8N_BASIC_AUTH_USER and N8N_BASIC_AUTH_PASSWORD) environment variables.');
        }
    }

    /**
     * Check if circuit breaker is open
     */
    protected function isCircuitOpen(): bool
    {
        $failureCount = (int) Cache::get('n8n_circuit_breaker_failures', 0);
        $circuitOpenUntil = Cache::get('n8n_circuit_breaker_open_until');

        // If circuit is open, check if cooldown period has passed
        if ($circuitOpenUntil && now()->isBefore($circuitOpenUntil)) {
            return true;
        }

        // Open circuit if too many failures
        if ($failureCount >= $this->circuitBreakerThreshold) {
            Cache::put('n8n_circuit_breaker_open_until', now()->addSeconds($this->circuitBreakerTimeout));

            return true;
        }

        return false;
    }

    /**
     * Record a failure for circuit breaker
     */
    protected function recordFailure(): void
    {
        $failures = (int) Cache::get('n8n_circuit_breaker_failures', 0);
        Cache::put('n8n_circuit_breaker_failures', $failures + 1, now()->addSeconds($this->circuitBreakerTimeout * 2));
    }

    /**
     * Record a success for circuit breaker (resets failures)
     */
    protected function recordSuccess(): void
    {
        Cache::forget('n8n_circuit_breaker_failures');
        Cache::forget('n8n_circuit_breaker_open_until');
    }

    /**
     * Perform exponential backoff delay between retry attempts
     * Skipped in testing environment for faster test execution
     */
    protected function backoffDelay(int $attempt): void
    {
        // Skip sleep in testing environment to speed up tests
        if (app()->environment('testing')) {
            return;
        }

        sleep(pow(2, $attempt));
    }

    /**
     * Update a workflow in n8n via the API
     *
     * @param  string  $workflowId  The n8n workflow ID
     * @param  array  $workflow  The workflow definition
     * @return array Response with success status and data/error
     *
     * @throws ConnectionException
     * @throws Throwable
     */
    public function updateWorkflow(string $workflowId, array $workflow): array
    {
        // Check circuit breaker
        if ($this->isCircuitOpen()) {
            Log::warning('N8n circuit breaker is open, rejecting workflow update request', [
                'workflowId' => $workflowId,
            ]);

            return [
                'success' => false,
                'error' => 'N8n service is temporarily unavailable. Please try again later.',
            ];
        }

        $attempt = 0;
        $lastException = null;

        while ($attempt < $this->maxRetries) {
            try {
                $http = Http::timeout($this->timeout);

                // Use API key if available, otherwise use basic auth
                if ($this->apiKey) {
                    $http = $http->withHeader('X-N8N-API-KEY', $this->apiKey);
                } else {
                    $http = $http->withBasicAuth($this->username, $this->password);
                }

                $response = $http->put(rtrim($this->baseUrl, '/')."/api/v1/workflows/$workflowId", $workflow);

                // Check for HTTP errors
                if ($response->failed()) {
                    $status = $response->status();

                    Log::error('N8n workflow update HTTP error', [
                        'workflowId' => $workflowId,
                        'status' => $status,
                        'body' => $response->body(),
                        'attempt' => $attempt + 1,
                    ]);

                    // Don't retry client errors (4xx), only server errors (5xx)
                    if ($status < 500) {
                        $this->recordFailure();

                        return [
                            'success' => false,
                            'error' => 'Failed to update workflow in n8n',
                            'details' => $response->json(),
                        ];
                    }

                    // Retry server errors
                    $attempt++;
                    if ($attempt >= $this->maxRetries) {
                        $this->recordFailure();

                        return [
                            'success' => false,
                            'error' => 'Failed to update workflow after multiple attempts',
                        ];
                    }

                    // Exponential backoff
                    $this->backoffDelay($attempt);

                    continue;
                }

                // Success
                $this->recordSuccess();

                return [
                    'success' => true,
                    'data' => $response->json(),
                ];

            } catch (ConnectionException $e) {
                // Network/connection errors - retry
                $attempt++;
                $lastException = $e;

                Log::warning('N8n connection error updating workflow, retrying', [
                    'workflowId' => $workflowId,
                    'attempt' => $attempt,
                    'error' => $e->getMessage(),
                ]);

                if ($attempt >= $this->maxRetries) {
                    break;
                }

                // Exponential backoff
                $this->backoffDelay($attempt);

            } catch (Throwable $e) {
                // Unexpected errors - don't retry
                Log::error('N8n unexpected error updating workflow', [
                    'workflowId' => $workflowId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                $this->recordFailure();
                throw $e;
            }
        }

        // Max retries exceeded
        $this->recordFailure();

        Log::error('N8n workflow update failed after all retries', [
            'workflowId' => $workflowId,
            'attempts' => $attempt,
            'last_error' => $lastException?->getMessage(),
        ]);

        return [
            'success' => false,
            'error' => 'N8n service is unavailable. Please try again later.',
        ];
    }

    /**
     * Trigger an N8n webhook with retry logic and circuit breaker
     *
     * @param  string  $path  Webhook path (e.g., '/webhook/my-webhook')
     * @param  array  $payload  Data to send
     * @return array Response with success status and data/error
     *
     * @throws ConnectionException
     * @throws Throwable
     */
    public function triggerWebhook(string $path, array $payload = []): array
    {
        // Check circuit breaker
        if ($this->isCircuitOpen()) {
            Log::warning('N8n circuit breaker is open, rejecting request', [
                'path' => $path,
            ]);

            return [
                'success' => false,
                'error' => 'N8n service is temporarily unavailable. Please try again later.',
            ];
        }

        $attempt = 0;
        $lastException = null;

        while ($attempt < $this->maxRetries) {
            try {
                $response = Http::timeout($this->timeout)
                    ->retry(1) // Laravel's built-in retry (attempt once, no delay)
                    ->withBasicAuth($this->username, $this->password)
                    ->post(rtrim($this->baseUrl, '/').'/'.ltrim($path, '/'), $payload);

                // Check for HTTP errors
                if ($response->failed()) {
                    $status = $response->status();

                    Log::error('N8n webhook HTTP error', [
                        'path' => $path,
                        'status' => $status,
                        'body' => $response->body(),
                        'attempt' => $attempt + 1,
                    ]);

                    // Special handling for rate limits (429)
                    if ($status === 429) {
                        Log::warning('N8n workflow hit Anthropic rate limit', [
                            'path' => $path,
                            'attempt' => $attempt + 1,
                        ]);

                        // Extend circuit breaker cooldown for rate limits
                        Cache::put('n8n_circuit_breaker_open_until', now()->addMinutes(15));

                        $this->recordFailure();

                        return [
                            'success' => false,
                            'error' => 'API rate limit reached. Please try again in a few minutes.',
                            'error_context' => [
                                'error_type' => 'rate_limit',
                                'timestamp' => now()->toIso8601String(),
                            ],
                        ];
                    }

                    // Don't retry client errors (4xx), only server errors (5xx)
                    if ($status < 500) {
                        $this->recordFailure();

                        return [
                            'success' => false,
                            'error' => 'N8n webhook request failed',
                            'payload' => $response->json(),
                        ];
                    }

                    // Retry server errors
                    $attempt++;
                    if ($attempt >= $this->maxRetries) {
                        $this->recordFailure();

                        return [
                            'success' => false,
                            'error' => 'N8n webhook failed after multiple attempts',
                            'payload' => $response->json(),
                        ];
                    }

                    // Exponential backoff
                    $this->backoffDelay($attempt);

                    continue;
                }

                // Success
                $this->recordSuccess();

                return [
                    'success' => true,
                    'data' => $response->json(),
                ];

            } catch (ConnectionException $e) {
                // Network/connection errors - retry
                $attempt++;
                $lastException = $e;

                Log::warning('N8n connection error, retrying', [
                    'path' => $path,
                    'attempt' => $attempt,
                    'error' => $e->getMessage(),
                ]);

                if ($attempt >= $this->maxRetries) {
                    break;
                }

                // Exponential backoff
                $this->backoffDelay($attempt);

            } catch (RequestException $e) {
                // HTTP request errors (5xx) - retry
                $attempt++;
                $lastException = $e;

                Log::warning('N8n request error, retrying', [
                    'path' => $path,
                    'attempt' => $attempt,
                    'status' => $e->response?->status(),
                    'error' => $e->getMessage(),
                ]);

                if ($attempt >= $this->maxRetries) {
                    break;
                }

                // Exponential backoff
                $this->backoffDelay($attempt);

            } catch (Throwable $e) {
                // Unexpected errors - don't retry
                Log::error('N8n unexpected error', [
                    'path' => $path,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'payload' => $payload,
                ]);

                $this->recordFailure();
                throw $e;
            }
        }

        // Max retries exceeded
        $this->recordFailure();

        Log::error('N8n webhook failed after all retries', [
            'path' => $path,
            'attempts' => $attempt,
            'last_error' => $lastException?->getMessage(),
        ]);

        return [
            'success' => false,
            'error' => 'N8n service is unavailable. Please try again later.',
        ];
    }
}
