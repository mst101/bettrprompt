import type { Page } from '@playwright/test';
import { test as base } from '@playwright/test';
import { loginWithPersonalityType } from '../helpers/auth';
import { withCountryCode } from '../helpers/country';

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

        const pageWithCountry = page as Page & {
            goto: typeof page.goto;
            waitForURL: typeof page.waitForURL;
        };

        pageWithCountry.goto = ((url, options) => {
            if (typeof url === 'string') {
                return originalGoto(withCountryCode(url), options);
            }

            return originalGoto(url, options);
        }) as typeof page.goto;

        pageWithCountry.waitForURL = ((url, options) => {
            if (typeof url === 'string') {
                return originalWaitForURL(withCountryCode(url), options);
            }

            return originalWaitForURL(url, options);
        }) as typeof page.waitForURL;

        await use(async (personalityCode: string) => {
            await loginWithPersonalityType(page, personalityCode);
        });
    },
});

export { expect } from '@playwright/test';
