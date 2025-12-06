/**
 * N8n Mock Service for E2E Tests
 *
 * Intercepts n8n webhook calls and returns deterministic mock responses.
 * This allows tests to run in seconds instead of waiting for real n8n workflows.
 *
 * Usage:
 *   const n8nMock = new N8nMockService(page);
 *   await n8nMock.enableMocking();
 *   // Tests will now receive mocked responses
 */

import type { Page, Route } from '@playwright/test';
import {
    mockApiError,
    mockFrameworkSelectionResponse,
    mockPromptGenerationResponse,
    mockRateLimitError,
    mockTimeoutError,
    mockValidationError,
    type N8nAnalysisResponse,
    type N8nCompletionResponse,
    type N8nErrorResponse,
} from './n8n-responses';

export type N8nMockScenario =
    | 'success'
    | 'timeout'
    | 'api-error'
    | 'validation-error'
    | 'rate-limit';

interface MockConfig {
    scenario?: N8nMockScenario;
    responseDelay?: number;
    taskDescription?: string;
    personalityType?: string;
}

export class N8nMockService {
    private page: Page;
    private config: MockConfig = {
        scenario: 'success',
        responseDelay: 100, // Simulate API processing time
    };
    private promptRunId: number | null = null;
    private isEnabled = false;

    constructor(page: Page) {
        this.page = page;
    }

    /**
     * Enable mocking of n8n webhook endpoints
     * All requests to /api/n8n/webhook will be intercepted and mocked
     */
    async enableMocking(config: Partial<MockConfig> = {}): Promise<void> {
        this.config = { ...this.config, ...config };

        // Route all n8n webhook requests
        await this.page.route('**/api/n8n/webhook', (route) =>
            this.handleWebhookRequest(route),
        );

        this.isEnabled = true;
    }

    /**
     * Disable mocking to allow real requests to n8n
     */
    async disableMocking(): Promise<void> {
        await this.page.unroute('**/api/n8n/webhook');
        this.isEnabled = false;
    }

    /**
     * Set the mock scenario for the next request
     */
    setScenario(scenario: N8nMockScenario, delay?: number): void {
        this.config.scenario = scenario;
        if (delay !== undefined) {
            this.config.responseDelay = delay;
        }
    }

    /**
     * Set context for mock responses
     */
    setContext(taskDescription: string, personalityType?: string): void {
        this.config.taskDescription = taskDescription;
        this.config.personalityType = personalityType;
    }

    /**
     * Handle incoming webhook requests
     */
    private async handleWebhookRequest(route: Route): Promise<void> {
        const request = route.request();
        const payload = request.postDataJSON();

        // Extract prompt_run_id for response mapping
        this.promptRunId = payload.prompt_run_id;

        // Apply response delay to simulate API processing
        if (this.config.responseDelay && this.config.responseDelay > 0) {
            await new Promise((resolve) =>
                setTimeout(resolve, this.config.responseDelay),
            );
        }

        // Generate mock response based on scenario
        let response:
            | N8nAnalysisResponse
            | N8nCompletionResponse
            | N8nErrorResponse;

        switch (this.config.scenario) {
            case 'timeout':
                response = mockTimeoutError(payload.prompt_run_id);
                return route.abort('timedout');

            case 'api-error':
                response = mockApiError(payload.prompt_run_id);
                break;

            case 'validation-error':
                response = mockValidationError(
                    payload.prompt_run_id,
                    'task_description',
                );
                break;

            case 'rate-limit':
                response = mockRateLimitError(payload.prompt_run_id);
                break;

            case 'success':
            default:
                // Determine response type based on payload
                if (payload.workflow_stage === 'framework_selected') {
                    // Return framework selection response
                    response = mockFrameworkSelectionResponse(
                        payload.prompt_run_id,
                        this.config.taskDescription || payload.task_description,
                        this.config.personalityType || payload.personality_type,
                    );
                } else if (payload.workflow_stage === 'generating_prompt') {
                    // Return prompt generation response
                    const frameworkFromPayload = payload.selected_framework;
                    response = mockPromptGenerationResponse(
                        payload.prompt_run_id,
                        this.config.taskDescription || payload.task_description,
                        frameworkFromPayload,
                        payload.clarifying_answers,
                    );
                } else {
                    // Default to framework selection
                    response = mockFrameworkSelectionResponse(
                        payload.prompt_run_id,
                        this.config.taskDescription || payload.task_description,
                        this.config.personalityType || payload.personality_type,
                    );
                }
                break;
        }

        // Return mock response
        await route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify(response),
        });
    }

    /**
     * Check if mocking is currently enabled
     */
    isActive(): boolean {
        return this.isEnabled;
    }
}
