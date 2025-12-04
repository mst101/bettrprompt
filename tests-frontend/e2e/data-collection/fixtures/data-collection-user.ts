import { test as base } from '../../fixtures/personality-user';

/**
 * Data Collection Test Fixture
 *
 * Extends the personality-user fixture to add:
 * - X-Data-Collection-Test header for database switching
 * - Helper to set personality type and traits for a session
 */

interface PersonalityTraits {
    mind?: number; // Introversion (I) vs Extraversion (E)
    energy?: number; // Intuition (N) vs Sensing (S)
    nature?: number; // Thinking (T) vs Feeling (F)
    tactics?: number; // Judging (J) vs Perceiving (P)
    identity?: number; // Assertive (A) vs Turbulent (T)
    // Legacy names (mapped to new format for backwards compatibility)
    extraversion?: number;
    intuition?: number;
    thinking?: number;
    judging?: number;
}

export const test = base.extend({
    page: async ({ page }, use) => {
        // Set up cookies and intercept routes to add BOTH X-Test-Auth and X-Data-Collection-Test headers
        // This must be done before any navigation

        // Pre-accept cookies to prevent cookie banner from blocking interactions
        await page.context().addCookies([
            {
                name: 'cookie_consent',
                value: encodeURIComponent(
                    JSON.stringify({
                        essential: true,
                        functional: true,
                        analytics: true,
                    }),
                ),
                domain: 'app.localhost',
                path: '/',
                expires: Math.floor(Date.now() / 1000) + 365 * 24 * 60 * 60, // 1 year
                httpOnly: false,
                secure: false,
                sameSite: 'Strict',
            },
        ]);

        // Intercept all requests to add BOTH test auth and data collection headers
        // This replaces the standard route interception in acceptCookies() to ensure data-collection header is preserved
        await page.route('**/*', async (route) => {
            const headers = {
                ...route.request().headers(),
                'X-Test-Auth': 'playwright-e2e-tests',
                'X-Data-Collection-Test': 'true',
            };
            await route.continue({ headers });
        });

        await use(page);
    },

    /**
     * Helper to set personality type for the test session
     * Usage: await setPersonality('INTJ', 'assertive', { extraversion: 30, ... })
     *
     * Note: Must be called AFTER user is authenticated. The fixture automatically
     * logs in the user before making the setPersonality call.
     */
    setPersonality: async ({ page }, use) => {
        const TEST_USER_EMAIL = 'test@example.com';

        const setPersonality = async (
            baseType: string,
            identity: 'assertive' | 'turbulent' = 'assertive',
            traits?: Partial<PersonalityTraits>,
        ) => {
            const personalityCode = `${baseType}-${identity.charAt(0).toUpperCase()}`;

            // Map MBTI trait names to the expected format
            // mind: Introversion/Extraversion (E=high, I=low)
            // energy: Intuition/Sensing (N=high, S=low)
            // nature: Thinking/Feeling (T=high, F=low)
            // tactics: Judging/Perceiving (J=high, P=low)
            // identity: Assertive/Turbulent (A=high, T=low)

            const mappedTraits = {
                mind: traits?.extraversion ?? 50,
                energy: traits?.intuition ?? 50,
                nature: traits?.thinking ?? 50,
                tactics: traits?.judging ?? 50,
                identity: identity === 'assertive' ? 75 : 25,
            };

            // Check if already logged in to avoid unnecessary login attempts
            const isAlreadyLoggedIn = await page
                .getByRole('button', { name: /user menu/i })
                .isVisible({ timeout: 3000 })
                .catch(() => false);

            if (!isAlreadyLoggedIn) {
                // Not logged in, need to login first
                // Navigate to homepage first
                await page.goto('/', { waitUntil: 'domcontentloaded' });

                // Use the test-only login endpoint via browser's fetch API
                await page.evaluate(async (email: string) => {
                    const csrfToken = (
                        document.querySelector(
                            'meta[name="csrf-token"]',
                        ) as HTMLMetaElement
                    )?.getAttribute('content');

                    const response = await fetch('/test/login', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken || '',
                            'X-Test-Auth': 'playwright-e2e-tests',
                            'X-Data-Collection-Test': 'true',
                        },
                        body: JSON.stringify({ email }),
                        credentials: 'include',
                    });

                    if (!response.ok) {
                        throw new Error(
                            `Login failed: ${response.status} ${response.statusText}`,
                        );
                    }

                    return response.json();
                }, TEST_USER_EMAIL);

                // Navigate away and back to trigger Inertia to reload with auth
                await page.goto('/', { waitUntil: 'domcontentloaded' });

                // Verify we're logged in
                const userMenu = page.getByRole('button', {
                    name: /user menu/i,
                });
                const isLoggedIn = await userMenu
                    .isVisible({ timeout: 2000 })
                    .catch(() => false);

                if (!isLoggedIn) {
                    throw new Error(
                        'Login failed - user menu not found. Check credentials or form validation.',
                    );
                }
            }

            // Use page.evaluate to run in browser context with authenticated cookies
            await page.evaluate(
                async (params) => {
                    const { bType, ident, traitValues } = params;
                    const response = await fetch('/test/set-personality', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Test-Auth': 'playwright-e2e-tests',
                            'X-Data-Collection-Test': 'true',
                        },
                        body: JSON.stringify({
                            personality_type: bType,
                            identity: ident,
                            traits: traitValues,
                        }),
                        credentials: 'include',
                    });

                    if (!response.ok) {
                        const body = await response
                            .text()
                            .catch(() => '<no body>');
                        console.error(
                            `[DEBUG] Failed to set personality - Status: ${response.status}, StatusText: ${response.statusText}, Body: ${body}`,
                        );
                        throw new Error(
                            `Failed to set personality: ${response.status} ${response.statusText}`,
                        );
                    }

                    return response.json();
                },
                {
                    bType: baseType,
                    ident: identity,
                    traitValues: mappedTraits,
                },
            );

            console.log(`✓ Set personality to ${personalityCode}`);
            return personalityCode;
        };

        await use(setPersonality);
    },
});

export { expect } from '@playwright/test';
