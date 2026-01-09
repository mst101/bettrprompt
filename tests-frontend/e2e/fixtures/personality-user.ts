import { test as base } from '@playwright/test';
import { loginWithPersonalityType } from '../helpers/auth';
import { withLocale } from '../helpers/locale';

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
        const originalGoto = page.goto.bind(page);
        const originalWaitForURL = page.waitForURL.bind(page);

        const pageWithLocale = page as typeof page & {
            goto: typeof page.goto;
            waitForURL: typeof page.waitForURL;
        };

        pageWithLocale.goto = ((url, options) => {
            if (typeof url === 'string') {
                return originalGoto(withLocale(url), options);
            }

            return originalGoto(url, options);
        }) as typeof page.goto;

        pageWithLocale.waitForURL = ((url, options) => {
            if (typeof url === 'string') {
                return originalWaitForURL(withLocale(url), options);
            }

            return originalWaitForURL(url, options);
        }) as typeof page.waitForURL;

        await use(async (personalityCode: string) => {
            await loginWithPersonalityType(page, personalityCode);
        });
    },
});

export { expect } from '@playwright/test';
