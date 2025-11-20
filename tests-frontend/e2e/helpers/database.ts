import { exec } from 'child_process';
import { promisify } from 'util';

export const execAsync = promisify(exec);

/**
 * Seed prompt runs for the test user
 *
 * @param count Number of prompt runs to create
 * @param status Optional status for the prompt runs (completed, processing, failed, pending)
 */
export async function seedPromptRuns(
    count: number = 5,
    status?: string,
): Promise<void> {
    try {
        const env = status
            ? `SEED_COUNT=${count} SEED_STATUS=${status}`
            : `SEED_COUNT=${count}`;
        await execAsync(
            `${env} ./vendor/bin/sail artisan db:seed --class=TestPromptRunsSeeder`,
        );
    } catch (error) {
        console.error('Failed to seed prompt runs:', error);
        throw error;
    }
}
