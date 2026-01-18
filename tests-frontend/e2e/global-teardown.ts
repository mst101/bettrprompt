import { execSync } from 'child_process';
import * as fs from 'fs';
import * as path from 'path';

/**
 * Global teardown for Playwright E2E tests
 * This runs once after all tests complete
 *
 * Restores the original .env file from backup
 */
async function globalTeardown() {
    console.log('🧹 Cleaning up E2E test environment...');

    const envPath = path.join(process.cwd(), '.env');
    const envLocalPath = path.join(process.cwd(), '.env.local');

    try {
        // Restore .env from .env.local (the permanent backup of the local environment)
        if (fs.existsSync(envLocalPath)) {
            // Read backup to verify it has content
            const localContent = fs.readFileSync(envLocalPath, 'utf-8');
            if (!localContent || localContent.trim().length === 0) {
                throw new Error('.env.local is empty - refusing to restore');
            }

            // Verify backup contains a valid environment config
            if (!localContent.includes('APP_ENV=')) {
                throw new Error(
                    '.env.local does not contain APP_ENV - not a valid backup',
                );
            }

            // Copy backup over current .env
            fs.copyFileSync(envLocalPath, envPath);

            // Verify the restoration worked by checking for APP_ENV
            const restoredContent = fs.readFileSync(envPath, 'utf-8');
            if (restoredContent.includes('APP_ENV=local')) {
                // Successfully restored to local environment
                // Keep .env.local (don't delete - it's the permanent backup)
                console.log(
                    '✅ Restored .env to local environment - Laravel dev server back to normal',
                );
            } else if (restoredContent.includes('APP_ENV=e2e')) {
                // .env.local is corrupted
                console.warn(
                    '⚠️  WARNING: .env.local also contains APP_ENV=e2e',
                );
                console.warn(
                    '   This should not happen - .env.local should always be in local mode.',
                );
                console.warn('   Restoration failed - manual recovery needed.');
                throw new Error('.env.local is corrupted (APP_ENV=e2e)');
            } else {
                // .env.local has unexpected APP_ENV
                console.warn(
                    '⚠️  WARNING: Restored APP_ENV is not "local" or "e2e":',
                );
                console.warn(
                    '   ' +
                        restoredContent
                            .split('\n')
                            .find((line) => line.includes('APP_ENV=')),
                );
                console.warn('   Proceeding anyway, but verify .env manually.');
            }
        } else {
            console.warn(
                '⚠️  .env.local not found - .env may not have been restored',
            );
        }
    } catch (error) {
        console.error('❌ Failed to restore .env:', error);
        console.error('');
        console.error(
            '   Your .env file may still be pointing to the e2e database!',
        );
        console.error('');
        console.error('📋 Recovery options:');
        console.error('   1. If .env.local exists and is valid:');
        console.error('      cp .env.local .env');
        console.error('   2. If .env.local is also corrupted:');
        console.error(
            '      Restore from version control or recreate .env manually',
        );
        console.error(
            '   3. Then run tests again (it will recreate .env.local)',
        );
        console.error('');
        console.error('Backup file location: ' + envLocalPath);
        // Don't throw - let tests complete normally but warn the user
    }

    // Clear config cache to ensure Laravel picks up the restored .env file
    try {
        execSync('./vendor/bin/sail artisan config:clear', {
            stdio: 'inherit',
        });
        console.log('✅ Config cache cleared after restoring .env');
    } catch (error) {
        console.warn(
            '⚠️  Warning: Failed to clear config cache after restoration:',
            error,
        );
        // Don't throw - non-critical cleanup
    }
}

export default globalTeardown;
