/**
 * Country Code URL Helper
 *
 * This module provides utilities for working with country-code-based URLs in E2E tests.
 * The application uses lowercase 2-letter ISO country codes in URLs (e.g., /gb/, /us/)
 * rather than full locale identifiers.
 *
 * Example: withCountryCode('/pricing') => '/gb/pricing'
 */

export const DEFAULT_COUNTRY = 'gb';

const countryPattern = /^\/[a-z]{2}(?:\/|$)/;

/**
 * Adds a country code prefix to a URL path if not already present
 *
 * @param path - The URL path to process
 * @param country - The 2-letter country code (default: 'gb')
 * @returns The path with country code prefix
 *
 * @example
 * withCountryCode('/pricing') => '/gb/pricing'
 * withCountryCode('/gb/pricing') => '/gb/pricing' (no change)
 * withCountryCode('https://example.com/pricing') => 'https://example.com/pricing' (no change)
 */
export function withCountryCode(
    path: string,
    country: string = DEFAULT_COUNTRY,
): string {
    if (!path) {
        return `/${country}`;
    }

    if (/^https?:\/\//.test(path)) {
        return path;
    }

    const match = path.match(/^([^?#]*)(.*)$/);
    const pathname = match?.[1] ?? path;
    const suffix = match?.[2] ?? '';

    if (pathname === '/') {
        return `/${country}${suffix}`;
    }

    if (countryPattern.test(pathname)) {
        return path;
    }

    const normalized = pathname.startsWith('/') ? pathname : `/${pathname}`;
    return `/${country}${normalized}${suffix}`;
}

/**
 * Get the default country home page URL
 * Useful for navigation after login in e2e tests
 *
 * @example
 * await page.goto(getDefaultCountryUrl())  // => /gb/
 * await page.goto(getDefaultCountryUrl('/history'))  // => /gb/history
 */
export function getDefaultCountryUrl(path: string = '/'): string {
    return withCountryCode(path, DEFAULT_COUNTRY);
}
