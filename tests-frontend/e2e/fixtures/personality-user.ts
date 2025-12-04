import { test as base } from '@playwright/test';
import { loginWithPersonalityType } from '../helpers/auth';

export type PersonalityUserFixture = {
    personalityUser: (personalityCode: string) => Promise<void>;
};

/**
 * Personality user fixture
 *
 * Provides quick login with a specific personality type for tests
 * Usage:
 *   test('my test', async ({ page, personalityUser }) => {
 *     await personalityUser('INTJ-A');
 *     // User is now logged in with INTJ-A personality type
 *   });
 */
export const test = base.extend<PersonalityUserFixture>({
    personalityUser: async ({ page }, use) => {
        await use(async (personalityCode: string) => {
            await loginWithPersonalityType(page, personalityCode);
        });
    },
});

export { expect } from '@playwright/test';
