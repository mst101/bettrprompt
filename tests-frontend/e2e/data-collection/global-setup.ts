import { execSync } from 'child_process';

/**
 * Global setup for Data Collection E2E tests
 * This runs once before all data collection tests
 *
 * Data collection tests use a separate persistent database (bettrprompt_data_collection)
 * that is NOT reset between test runs. This preserves all framework selection data for analysis.
 */
async function globalSetup() {
    console.log('🧪 Setting up Data Collection test environment...');

    try {
        // Set APP_ENV to e2e so Laravel uses .env.e2e
        process.env.APP_ENV = 'e2e';

        console.log(
            '📦 Preparing data collection database (bettrprompt_data_collection)...',
        );

        // Check if data collection database already exists
        let dbExists = false;
        try {
            execSync(
                './vendor/bin/sail exec -T pgsql psql -U sail -d bettrprompt_data_collection -c "SELECT 1;" 2>/dev/null',
                { stdio: 'pipe', shell: '/bin/bash' },
            );
            dbExists = true;
            console.log(
                '✓ Data collection database already exists, preserving existing data',
            );
        } catch {
            console.log('📋 Creating new data collection database');
        }

        if (!dbExists) {
            // Create the database for the first time
            try {
                execSync(
                    './vendor/bin/sail exec -T pgsql psql -U sail -d postgres -c "CREATE DATABASE bettrprompt_data_collection;"',
                    { stdio: 'inherit', shell: '/bin/bash' },
                );
                console.log('✓ Created data collection database');
            } catch (e) {
                console.error(
                    '❌ Failed to create database:',
                    (e as Error).message,
                );
                throw e;
            }

            console.log(
                '🔄 Cloning schema from personality_e2e to bettrprompt_data_collection...',
            );
            // Clone only the schema (not data) from personality_e2e
            try {
                execSync(
                    './vendor/bin/sail exec -T pgsql pg_dump -U sail personality_e2e --schema-only | ./vendor/bin/sail exec -T pgsql psql -U sail bettrprompt_data_collection',
                    { stdio: 'inherit', shell: '/bin/bash' },
                );
                console.log('✓ Database schema cloned successfully');
            } catch (e) {
                console.error(
                    '❌ Failed to clone schema:',
                    (e as Error).message,
                );
                throw e;
            }
        } else {
            // Database exists, ensure schema is up-to-date by applying any missing tables/columns
            // Get schema from personality_e2e and apply only new/modified structures
            console.log('🔄 Syncing schema from personality_e2e...');
            try {
                execSync(
                    './vendor/bin/sail exec -T pgsql pg_dump -U sail personality_e2e --schema-only | ./vendor/bin/sail exec -T pgsql psql -U sail bettrprompt_data_collection 2>&1 | grep -v "already exists" || true',
                    { stdio: 'inherit', shell: '/bin/bash' },
                );
                console.log('✓ Schema is up-to-date');
            } catch (e) {
                console.warn(
                    '⚠ Could not fully sync schema:',
                    (e as Error).message,
                );
            }
        }

        // Sync PostgreSQL sequences to match current data
        // This ensures that IDs continue from the highest existing value
        console.log('🔄 Syncing PostgreSQL sequences...');
        try {
            execSync(
                `./vendor/bin/sail exec -T pgsql psql -U sail bettrprompt_data_collection << 'EOF'
                SELECT setval('prompt_runs_id_seq', COALESCE((SELECT MAX(id) FROM prompt_runs), 0) + 1);
                SELECT setval('users_id_seq', COALESCE((SELECT MAX(id) FROM users), 0) + 1);
                SELECT setval('visitors_id_seq', COALESCE((SELECT MAX(id) FROM visitors), 0) + 1);
                SELECT setval('feedbacks_id_seq', COALESCE((SELECT MAX(id) FROM feedbacks), 0) + 1);
EOF`,
                { stdio: 'inherit', shell: '/bin/bash' },
            );
            console.log('✓ Sequences synced to match existing data');
        } catch (e) {
            console.warn('⚠ Could not sync sequences:', (e as Error).message);
        }

        console.log('✅ Data Collection test environment ready!');
        console.log(
            '📊 Database: bettrprompt_data_collection (persisted for analysis)',
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
