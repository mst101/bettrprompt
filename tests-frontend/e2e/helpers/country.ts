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

/**
 * Get the regex pattern for matching country codes
 * Replaces legacy locale pattern [a-z]{2} with [a-z]{2}
 *
 * Use this constant to replace hardcoded patterns in tests
 *
 * @example
 * // OLD (legacy locale pattern):
 * /[a-z]{2}\/prompt-builder\/\d+/
 *
 * // NEW (country code pattern):
 * new RegExp(`${COUNTRY_CODE_PATTERN}\\/prompt-builder\\/\\d+`)
 * // or simply:
 * /[a-z]{2}\/prompt-builder\/\d+/
 */
export const COUNTRY_CODE_PATTERN = '[a-z]{2}';

/**
 * Helper to build URL patterns for country-code URLs
 * Simplifies replacing legacy /[a-z]{2}/ patterns with /[a-z]{2}/
 *
 * @param pathPattern - The path pattern after the country code (with regex syntax)
 * @returns RegExp that matches country code + path
 *
 * @example
 * // OLD: /[a-z]{2}\/prompt-builder\/\d+/
 * // NEW:
 * getCountryCodeUrlPattern('/prompt-builder/\\d+')
 * // Result: /[a-z]{2}\/prompt-builder\/\d+/
 *
 * // Works with anchors too:
 * getCountryCodeUrlPattern('/prompt-builder/\\d+(\\?.*)?$')
 */
export function getCountryCodeUrlPattern(pathPattern: string): RegExp {
    return new RegExp(`[a-z]{2}${pathPattern}`);
}
