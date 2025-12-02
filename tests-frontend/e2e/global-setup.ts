import { execSync } from 'child_process';

/**
 * Global setup for Playwright E2E tests
 * This runs once before all tests
 */
async function globalSetup() {
    console.log('🧪 Setting up E2E test environment...');

    try {
        // Set APP_ENV to e2e so Laravel uses .env.e2e
        process.env.APP_ENV = 'e2e';

        console.log('📦 Creating test database...');
        // Create the test database if it doesn't exist
        // Using psql -tc to suppress unnecessary output
        execSync(
            './vendor/bin/sail exec -T pgsql psql -U sail -d personality -tc "SELECT 1 FROM pg_database WHERE datname = \'personality_e2e\'" | grep -q 1 || ./vendor/bin/sail exec -T pgsql psql -U sail -d personality -c "CREATE DATABASE personality_e2e;"',
            { stdio: 'inherit', shell: '/bin/bash' },
        );

        console.log('🔄 Running migrations on test database...');
        // Run migrations on the test database
        execSync('./vendor/bin/sail artisan migrate:fresh --env=e2e --force', {
            stdio: 'inherit',
        });

        console.log('✅ E2E test environment ready!');
    } catch (error) {
        console.error('❌ Failed to set up E2E test environment:', error);
        throw error;
    }
}

export default globalSetup;
