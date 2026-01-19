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
    const envLocalPath = path.join(process.cwd(), '.env.local');
    const envE2ePath = path.join(process.cwd(), '.env.e2e');
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

        // CRITICAL: Ensure .env.local exists as a backup of the local environment
        // This is used to restore .env after tests complete
        if (fs.existsSync(envPath)) {
            const envContent = fs.readFileSync(envPath, 'utf-8');

            // Check if current .env is in test mode (shouldn't be when tests start)
            if (envContent.includes('APP_ENV=e2e')) {
                console.warn('⚠️  WARNING: .env already has APP_ENV=e2e');
                console.warn(
                    '   This indicates a previous E2E test run did not complete cleanly.',
                );

                // Try to recover from the corrupted state
                if (fs.existsSync(envLocalPath)) {
                    const localContent = fs.readFileSync(envLocalPath, 'utf-8');
                    if (localContent.includes('APP_ENV=local')) {
                        console.log(
                            '   Restoring from .env.local (backup of local environment)...',
                        );
                        fs.copyFileSync(envLocalPath, envPath);
                        console.log('✅ Restored .env to local state');
                    }
                } else {
                    console.warn(
                        '   No .env.local backup found. Cannot auto-recover.',
                    );
                    throw new Error(
                        '.env is in e2e mode but no .env.local backup exists for recovery',
                    );
                }
            }

            // Now that .env is in a good state, ensure we have .env.local for restoration later
            if (!fs.existsSync(envLocalPath)) {
                const currentContent = fs.readFileSync(envPath, 'utf-8');
                if (currentContent.includes('APP_ENV=local')) {
                    fs.copyFileSync(envPath, envLocalPath);
                    console.log(
                        '📝 Created .env.local as backup of local environment',
                    );
                } else {
                    throw new Error(
                        '.env does not contain APP_ENV=local - cannot create backup',
                    );
                }
            }
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

        // Clear config cache to ensure Laravel picks up the new .env file
        console.log('🧹 Clearing config cache...');
        try {
            execSync('./vendor/bin/sail artisan config:clear', {
                stdio: 'inherit',
            });
            console.log('✅ Config cache cleared');
        } catch (error) {
            console.warn('⚠️  Warning: Failed to clear config cache:', error);
            // Don't throw - continue anyway as config caching might be disabled
        }

        // Note: Database isolation is enforced via VerifyE2eTestAuth middleware
        // which routes requests with X-Test-Auth header to the test database.
        // Do NOT restart the dev server - it breaks sail composer dev setups.

        console.log('📦 Creating test database...');
        // Create the test database if it doesn't exist
        // Using psql -tc to suppress unnecessary output
        execSync(
            './vendor/bin/sail exec -T pgsql psql -U sail -d bettrprompt -tc "SELECT 1 FROM pg_database WHERE datname = \'bettrprompt_e2e\'" | grep -q 1 || ./vendor/bin/sail exec -T pgsql psql -U sail -d bettrprompt -c "CREATE DATABASE bettrprompt_e2e;"',
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
    } catch (error) {
        console.error('❌ Failed to set up E2E test environment:', error);
        throw error;
    }
}

export default globalSetup;
