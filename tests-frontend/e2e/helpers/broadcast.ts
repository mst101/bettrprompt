import type { Page } from '@playwright/test';
import { PromptBuilderPage } from '../pages/PromptBuilderPage';

/**
 * Helper functions for triggering WebSocket broadcast events in E2E tests
 *
 * These helpers use the test-only broadcast endpoints to manually trigger
 * WebSocket events, allowing us to test real-time update functionality
 * without waiting for asynchronous n8n workflows.
 */

/**
 * Trigger an AnalysisCompleted event for a prompt run
 *
 * This simulates the event that fires when n8n completes framework selection.
 * The event will update the prompt run with a test framework and questions,
 * then broadcast the event via WebSockets.
 *
 * @param page - Playwright page object
 * @param promptRunId - ID of the prompt run
 * @returns Promise that resolves when the event has been triggered
 */
export async function triggerAnalysisCompleted(
    page: Page,
    promptRunId: number,
): Promise<void> {
    await page.evaluate(async (id: number) => {
        const csrfToken = document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content');

        const response = await fetch(
            `/test/broadcast/analysis-completed/${id}`,
            {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken || '',
                    'X-Test-Auth': 'playwright-e2e-tests',
                },
                credentials: 'include',
            },
        );

        if (!response.ok) {
            throw new Error(
                `Failed to trigger AnalysisCompleted event: ${response.statusText}`,
            );
        }

        const data = await response.json();
        console.log('[E2E] AnalysisCompleted event triggered:', data);
    }, promptRunId);

    // Wait for WebSocket to process the event - check for framework tab to appear
    const promptBuilder = new PromptBuilderPage(page);
    await promptBuilder.waitForFrameworkTab();
}

/**
 * Trigger a PromptOptimizationCompleted event for a prompt run
 *
 * This simulates the event that fires when n8n completes prompt optimisation.
 * The event will update the prompt run with a test optimised prompt,
 * then broadcast the event via WebSockets.
 *
 * @param page - Playwright page object
 * @param promptRunId - ID of the prompt run
 * @returns Promise that resolves when the event has been triggered
 */
export async function triggerPromptOptimizationCompleted(
    page: Page,
    promptRunId: number,
): Promise<void> {
    await page.evaluate(async (id: number) => {
        const csrfToken = document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content');

        const response = await fetch(
            `/test/broadcast/prompt-optimization-completed/${id}`,
            {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken || '',
                    'X-Test-Auth': 'playwright-e2e-tests',
                },
                credentials: 'include',
            },
        );

        if (!response.ok) {
            const errorText = await response.text();
            throw new Error(
                `Failed to trigger PromptOptimizationCompleted event: ${response.statusText} - ${errorText}`,
            );
        }

        const data = await response.json();
        console.log('[E2E] PromptOptimizationCompleted event triggered:', data);
    }, promptRunId);

    // Wait for the optimised prompt tab to appear, indicating the event was processed
    const promptBuilder = new PromptBuilderPage(page);
    await promptBuilder.waitForOptimisedPromptTab();
}

/**
 * Get Echo/WebSocket connection information for debugging
 *
 * @param page - Playwright page object
 * @returns Promise resolving to Echo connection info
 */
export async function getEchoInfo(page: Page): Promise<{
    reverb_enabled: boolean;
    reverb_host: string;
    reverb_port: number;
    app_key: string;
    environment: string;
}> {
    return await page.evaluate(async () => {
        const response = await fetch('/test/echo-info', {
            method: 'GET',
            headers: {
                'X-Test-Auth': 'playwright-e2e-tests',
            },
            credentials: 'include',
        });

        if (!response.ok) {
            throw new Error(`Failed to get Echo info: ${response.statusText}`);
        }

        return await response.json();
    });
}

/**
 * Wait for Echo/WebSocket connection to be established
 *
 * @param page - Playwright page object
 * @param timeout - Maximum time to wait in milliseconds (default: 10000)
 * @returns Promise that resolves when Echo is connected, or rejects on timeout
 */
export async function waitForEchoConnection(
    page: Page,
    timeout: number = 10000,
): Promise<boolean> {
    try {
        await page.waitForFunction(
            () => {
                return (
                    window.Echo !== null &&
                    window.Echo !== undefined &&
                    typeof window.isEchoConnected === 'function' &&
                    window.isEchoConnected()
                );
            },
            { timeout },
        );
        console.log('[E2E] Echo connection established');
        return true;
    } catch {
        console.warn('[E2E] Echo connection timeout - falling back to polling');
        return false;
    }
}

/**
 * Extract prompt run ID from the current URL
 *
 * @param page - Playwright page object
 * @returns Promise resolving to the prompt run ID, or null if not found
 */
export async function getPromptRunIdFromUrl(
    page: Page,
): Promise<number | null> {
    const url = page.url();
    const match = url.match(/\/prompt-builder\/(\d+)/);
    return match ? parseInt(match[1], 10) : null;
}

/**
 * Create a test prompt run in a specific workflow stage for testing
 *
 * This endpoint allows tests to create prompts with pre-configured stages
 * without relying on the prompt history or form submissions.
 *
 * Workflow stages supported:
 * - '0_processing': Pre-analysis in progress
 * - '0_completed': Pre-analysis complete with quick queries
 * - '0_failed': Pre-analysis failed
 * - '1_processing': Main analysis in progress, no framework selected
 * - '1_completed': Framework selected, no optimised prompt
 * - '1_failed': Main analysis failed
 * - '2_processing': Prompt optimisation in progress
 * - '2_completed': Full workflow completed with optimised prompt
 * - '2_failed': Prompt optimisation failed
 *
 * @param page - Playwright page object
 * @param state - The workflow stage the prompt run should be created in
 * @returns Promise resolving to the created prompt run ID
 */
export async function createTestPromptRun(
    page: Page,
    state:
        | '0_processing'
        | '0_completed'
        | '0_failed'
        | '1_processing'
        | '1_completed'
        | '1_failed'
        | '2_processing'
        | '2_completed'
        | '2_failed' = '1_processing',
): Promise<number> {
    return await page.evaluate(async (s: string) => {
        const response = await fetch(`/test/create-prompt-run?state=${s}`, {
            method: 'POST',
            headers: {
                'X-Test-Auth': 'playwright-e2e-tests',
            },
            credentials: 'include',
        });

        if (!response.ok) {
            const errorText = await response.text();
            throw new Error(
                `Failed to create test prompt run: ${response.status} ${response.statusText} - ${errorText}`,
            );
        }

        const data = await response.json();
        console.log(
            `[E2E] Created test prompt run (state: ${s}):`,
            data.prompt_run_id,
        );
        return data.prompt_run_id;
    }, state);
}
