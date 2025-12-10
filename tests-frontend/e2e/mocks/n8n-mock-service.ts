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

import type { Page } from '@playwright/test';

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
    private isEnabled = false;

    constructor(page: Page) {
        this.page = page;
    }

    /**
     * Enable mocking of n8n webhook endpoints
     *
     * The mocking works by:
     * 1. Storing the mock scenario in a database table that the backend can read
     * 2. The MockN8nController reads from this table and simulates the scenario
     * 3. The controller updates the database with the result
     * 4. Frontend polling detects the change and reloads
     */
    async enableMocking(config: Partial<MockConfig> = {}): Promise<void> {
        this.config = { ...this.config, ...config };

        // Store the mock scenario in a test fixture via API
        // The backend will read this to determine how to respond
        try {
            const response = await this.page.request.post(
                `${this.getBaseUrl()}/api/test/set-mock-scenario`,
                {
                    data: {
                        scenario: this.config.scenario || 'success',
                    },
                },
            );

            if (!response.ok()) {
                console.error(
                    '[N8nMockService] Failed to set mock scenario:',
                    response.status(),
                );
            }
        } catch (error) {
            console.error(
                '[N8nMockService] Error setting mock scenario:',
                error,
            );
        }

        this.isEnabled = true;
    }

    /**
     * Disable mocking to allow real requests to n8n
     */
    async disableMocking(): Promise<void> {
        try {
            await this.page.request.post(
                `${this.getBaseUrl()}/api/test/clear-mock-scenario`,
                {},
            );
        } catch (error) {
            console.error(
                '[N8nMockService] Error clearing mock scenario:',
                error,
            );
        }

        this.isEnabled = false;
    }

    /**
     * Get the base URL for API calls
     */
    private getBaseUrl(): string {
        return this.page.url().split('/').slice(0, 3).join('/');
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
     * Check if mocking is currently enabled
     */
    isActive(): boolean {
        return this.isEnabled;
    }
}
