import { execSync } from 'child_process';

/**
 * Global setup for Data Collection E2E tests
 * This runs once before all data collection tests
 *
 * Data collection tests use a separate persistent database (personality_data_collection)
 * that is NOT reset between test runs. This preserves all framework selection data for analysis.
 */
async function globalSetup() {
    console.log('🧪 Setting up Data Collection test environment...');

    try {
        // Set APP_ENV to e2e so Laravel uses .env.e2e
        process.env.APP_ENV = 'e2e';

        console.log(
            '📦 Ensuring data collection database (personality_data_collection) exists...',
        );
        // Create the personality_data_collection database if it doesn't exist
        execSync(
            './vendor/bin/sail exec -T pgsql psql -U sail -d personality -tc "SELECT 1 FROM pg_database WHERE datname = \'personality_data_collection\'" | grep -q 1 || ./vendor/bin/sail exec -T pgsql psql -U sail -d personality -c "CREATE DATABASE personality_data_collection;"',
            { stdio: 'inherit', shell: '/bin/bash' },
        );

        console.log(
            '🔄 Cloning full database from personality_e2e to personality_data_collection...',
        );
        // Clone the entire database from personality_e2e to personality_data_collection
        // This is the safest approach as it preserves all schema and constraints
        try {
            execSync(
                './vendor/bin/sail exec -T pgsql pg_dump -U sail personality_e2e | ./vendor/bin/sail exec -T pgsql psql -U sail personality_data_collection',
                { stdio: 'inherit', shell: '/bin/bash' },
            );
            console.log('✓ Database cloned to personality_data_collection');
        } catch {
            // Database might already exist with data, which is okay for data collection
            // Just ensure schema exists
            console.log(
                '  (database may already exist, attempting schema-only clone)',
            );
            try {
                execSync(
                    './vendor/bin/sail exec -T pgsql pg_dump -U sail personality_e2e --schema-only | ./vendor/bin/sail exec -T pgsql psql -U sail personality_data_collection',
                    { stdio: 'inherit', shell: '/bin/bash' },
                );
            } catch {
                console.log('  (schema already in place)');
            }
        }

        console.log('✅ Data Collection test environment ready!');
        console.log(
            '📊 Database: personality_data_collection (persisted for analysis)',
        );
        console.log(
            '⚠️  Data is NOT reset - framework selection data is preserved across test runs',
        );
    } catch (error) {
        console.error(
            '❌ Failed to set up Data Collection test environment:',
            error,
        );
        throw error;
    }
}

export default globalSetup;
