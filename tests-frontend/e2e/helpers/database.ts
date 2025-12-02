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
        // Use Sail with bash -c to properly pass environment variables into the Docker container
        const env = status
            ? `SEED_COUNT=${count} SEED_STATUS=${status}`
            : `SEED_COUNT=${count}`;
        await execAsync(
            `./vendor/bin/sail bash -c "${env} php artisan db:seed --class=TestPromptRunsSeeder --env=e2e"`,
        );
    } catch (error) {
        console.error('Failed to seed prompt runs:', error);
        throw error;
    }
}
