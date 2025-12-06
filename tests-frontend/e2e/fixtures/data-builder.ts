import type { Page } from '@playwright/test';

/**
 * Test Data Builder
 * Provides a fluent API for creating test data and making API calls to set up test state
 * Usage: await builder.withPromptRun('submitted').create()
 */

export interface PromptRunData {
    task: string;
    state: 'submitted' | 'analysis_complete' | 'completed';
    frameworkName?: string;
    optimizedPrompt?: string;
}

export interface UserData {
    name: string;
    email: string;
    password: string;
}

/**
 * Builder for creating test data via API endpoints
 */
export class TestDataBuilder {
    private page: Page;
    private promptRunData: Partial<PromptRunData> = {};
    private userData: Partial<UserData> = {};

    constructor(page: Page) {
        this.page = page;
    }

    /**
     * Set up a prompt run with a specific state
     */
    withPromptRun(state: PromptRunData['state']): this {
        this.promptRunData.state = state;
        return this;
    }

    /**
     * Set task description
     */
    withTask(task: string): this {
        this.promptRunData.task = task;
        return this;
    }

    /**
     * Set framework name for prompt
     */
    withFramework(frameworkName: string): this {
        this.promptRunData.frameworkName = frameworkName;
        return this;
    }

    /**
     * Set optimized prompt content
     */
    withOptimizedPrompt(prompt: string): this {
        this.promptRunData.optimizedPrompt = prompt;
        return this;
    }

    /**
     * Create prompt run via test API endpoint
     */
    async createPromptRun(): Promise<number> {
        const state = this.promptRunData.state || 'submitted';
        const promptRunId = await this.page.evaluate(async (s: string) => {
            const response = await fetch(`/test/create-prompt-run?state=${s}`, {
                method: 'POST',
                headers: {
                    'X-Test-Auth': 'playwright-e2e-tests',
                },
                credentials: 'include',
            });

            if (!response.ok) {
                throw new Error(
                    `Failed to create test prompt run: ${response.statusText}`,
                );
            }

            const data = await response.json();
            return data.prompt_run_id;
        }, state);

        return promptRunId;
    }

    /**
     * Create user via test API endpoint
     */
    async createUser(): Promise<{ email: string; password: string }> {
        const email = this.userData.email || `test-${Date.now()}@example.com`;
        const password = this.userData.password || 'test-password-123';
        const name = this.userData.name || 'Test User';

        await this.page.evaluate(
            async (userData: any) => {
                const csrfToken = document
                    .querySelector('meta[name="csrf-token"]')
                    ?.getAttribute('content');

                const response = await fetch('/test/create-user', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken || '',
                        'X-Test-Auth': 'playwright-e2e-tests',
                    },
                    body: JSON.stringify(userData),
                    credentials: 'include',
                });

                if (!response.ok) {
                    throw new Error(
                        `Failed to create test user: ${response.statusText}`,
                    );
                }

                return response.json();
            },
            { name, email, password },
        );

        return { email, password };
    }

    /**
     * Reset to builder with no data
     */
    reset(): this {
        this.promptRunData = {};
        this.userData = {};
        return this;
    }
}

/**
 * Predefined test data scenarios for quick setup
 */
export const testDataScenarios = {
    /**
     * Create a submitted prompt run (no analysis done yet)
     */
    async createSubmittedPrompt(
        page: Page,
        task: string = 'Write a professional email',
    ): Promise<number> {
        const builder = new TestDataBuilder(page);
        return builder
            .withPromptRun('submitted')
            .withTask(task)
            .createPromptRun();
    },

    /**
     * Create a prompt run with framework selected
     */
    async createFrameworkSelectedPrompt(
        page: Page,
        task: string = 'Write a professional email',
        framework: string = 'STAR',
    ): Promise<number> {
        const builder = new TestDataBuilder(page);
        return builder
            .withPromptRun('analysis_complete')
            .withTask(task)
            .withFramework(framework)
            .createPromptRun();
    },

    /**
     * Create a completed prompt run
     */
    async createCompletedPrompt(
        page: Page,
        task: string = 'Write a professional email',
        optimizedPrompt: string = 'Here is your optimised prompt: [content]',
    ): Promise<number> {
        const builder = new TestDataBuilder(page);
        return builder
            .withPromptRun('completed')
            .withTask(task)
            .withOptimizedPrompt(optimizedPrompt)
            .createPromptRun();
    },

    /**
     * Create a test user
     */
    async createTestUser(
        page: Page,
        overrides?: Partial<UserData>,
    ): Promise<{ email: string; password: string }> {
        const builder = new TestDataBuilder(page);
        if (overrides?.name) builder.userData.name = overrides.name;
        if (overrides?.email) builder.userData.email = overrides.email;
        if (overrides?.password) builder.userData.password = overrides.password;
        return builder.createUser();
    },
};

/**
 * Usage examples:
 *
 * // Using the builder directly
 * const promptRunId = await new TestDataBuilder(page)
 *     .withPromptRun('analysis_complete')
 *     .withTask('Write a poem')
 *     .withFramework('Poetic Structure')
 *     .createPromptRun();
 *
 * // Using predefined scenarios
 * const submittedPromptId = await testDataScenarios.createSubmittedPrompt(
 *     page,
 *     'Write a business proposal'
 * );
 *
 * // Create a user with custom data
 * const user = await testDataScenarios.createTestUser(page, {
 *     name: 'John Doe',
 *     email: 'john@example.com',
 * });
 */
