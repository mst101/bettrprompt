import { execSync } from 'child_process';
import * as fs from 'fs';
import * as path from 'path';

/**
 * Global setup for Playwright E2E tests
 * This runs once before all tests
 *
 * IMPORTANT: This swaps .env files so the Laravel dev server connects to
 * bettrprompt_e2e instead of production bettrprompt database.
 * After all tests complete, the original .env is restored.
 */
async function globalSetup() {
    console.log('🧪 Setting up E2E test environment...');

    const envPath = path.join(process.cwd(), '.env');
    const envE2ePath = path.join(process.cwd(), '.env.e2e');
    const envBackupPath = path.join(process.cwd(), '.env.backup');
    const configCachePath = path.join(
        process.cwd(),
        'bootstrap/cache/config.php',
    );

    try {
        // SAFETY CHECK: Prevent tests from hitting production database
        // If config caching is enabled, Laravel won't read the swapped .env file
        if (fs.existsSync(configCachePath)) {
            console.error('❌ CRITICAL: Config caching is enabled!');
            console.error(
                '   Laravel will NOT read the swapped .env file, causing tests to hit the production database.',
            );
            console.error('');
            console.error('   Clear the config cache before running tests:');
            console.error('   ./vendor/bin/sail artisan config:clear');
            console.error('');
            throw new Error(
                'Config cache detected. Run "sail artisan config:clear" before running e2e tests.',
            );
        }
        // CRITICAL: Backup and swap .env files
        // This ensures the running Laravel dev server will use bettrprompt_e2e database
        if (fs.existsSync(envPath)) {
            fs.copyFileSync(envPath, envBackupPath);
            console.log('📝 Backed up production .env to .env.backup');
        }

        if (fs.existsSync(envE2ePath)) {
            fs.copyFileSync(envE2ePath, envPath);
            console.log(
                '🔄 Swapped to .env.e2e - Laravel dev server will now use bettrprompt_e2e database',
            );
        } else {
            throw new Error('.env.e2e not found!');
        }

        // Set APP_ENV to e2e for any Node/artisan processes
        process.env.APP_ENV = 'e2e';

        console.log('📦 Creating test database...');
        // Create the test database if it doesn't exist
        // Using psql -tc to suppress unnecessary output
        execSync(
            './vendor/bin/sail exec -T pgsql psql -U sail -d personality -tc "SELECT 1 FROM pg_database WHERE datname = \'bettrprompt_e2e\'" | grep -q 1 || ./vendor/bin/sail exec -T pgsql psql -U sail -d personality -c "CREATE DATABASE bettrprompt_e2e;"',
            { stdio: 'inherit', shell: '/bin/bash' },
        );

        console.log('🔄 Running migrations on test database...');
        // Run migrations on the test database
        execSync('./vendor/bin/sail artisan migrate:fresh --env=e2e --force', {
            stdio: 'inherit',
        });

        console.log('🌱 Seeding test data...');
        // Seed test data for E2E tests
        execSync(
            './vendor/bin/sail artisan db:seed --env=e2e --class=E2eTestSeeder --force',
            {
                stdio: 'inherit',
            },
        );

        console.log('✅ E2E test environment ready!');

        // Return a cleanup function that runs after all tests complete
        return async () => {
            try {
                console.log('🧹 Cleaning up E2E test environment...');

                // Restore the production .env from backup
                if (fs.existsSync(envBackupPath)) {
                    fs.copyFileSync(envBackupPath, envPath);
                    fs.unlinkSync(envBackupPath);
                    console.log(
                        '✅ Restored production .env - Laravel dev server back to normal',
                    );
                }
            } catch (cleanupError) {
                console.error(
                    '⚠️  Failed to restore production .env:',
                    cleanupError,
                );
                // Don't throw during cleanup to avoid masking test failures
            }
        };
    } catch (error) {
        console.error('❌ Failed to set up E2E test environment:', error);
        throw error;
    }
}

export default globalSetup;
