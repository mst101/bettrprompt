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
    const envBackupPath = path.join(process.cwd(), '.env.backup');

    try {
        // Restore the production .env from backup
        if (fs.existsSync(envBackupPath)) {
            // Read backup to verify it has content
            const backupContent = fs.readFileSync(envBackupPath, 'utf-8');
            if (!backupContent || backupContent.trim().length === 0) {
                throw new Error('.env.backup is empty - refusing to restore');
            }

            // Copy backup over current .env
            fs.copyFileSync(envBackupPath, envPath);

            // Verify the restoration worked by checking for APP_ENV
            const restoredContent = fs.readFileSync(envPath, 'utf-8');
            if (!restoredContent.includes('APP_ENV=local')) {
                throw new Error(
                    'Restoration failed: APP_ENV is not set to local in restored .env',
                );
            }

            // Only delete the backup if restoration succeeded
            fs.unlinkSync(envBackupPath);
            console.log(
                '✅ Restored production .env - Laravel dev server back to normal',
            );
        } else {
            console.warn(
                '⚠️  .env.backup not found - .env may not have been restored',
            );
        }
    } catch (error) {
        console.error('❌ Failed to restore .env:', error);
        console.error(
            '   Your .env file is still pointing to the e2e database!',
        );
        console.error('   Backup file location: ' + envBackupPath);
        console.error(
            '   Please manually restore your .env or run: git checkout .env',
        );
        // Don't throw - let tests complete normally but warn the user
    }
}

export default globalTeardown;
