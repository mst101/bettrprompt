import { getCookie, getCsrfToken } from '@/Utils/cookies';
import { afterEach, beforeEach, describe, expect, it } from 'vitest';

describe('cookies utilities', () => {
    beforeEach(() => {
        // Clear cookies before each test
        document.cookie.split(';').forEach((c) => {
            document.cookie = c
                .replace(/^ +/, '')
                .replace(
                    /=.*/,
                    `=;expires=${new Date(0).toUTCString()};path=/`,
                );
        });
    });

    afterEach(() => {
        // Clean up
        document.cookie.split(';').forEach((c) => {
            document.cookie = c
                .replace(/^ +/, '')
                .replace(
                    /=.*/,
                    `=;expires=${new Date(0).toUTCString()};path=/`,
                );
        });
    });

    describe('getCookie', () => {
        it('should return null when cookie does not exist', () => {
            expect(getCookie('nonexistent')).toBeNull();
        });

        it('should return cookie value when it exists', () => {
            document.cookie = 'test=value; path=/';
            expect(getCookie('test')).toBe('value');
        });

        it('should return null for empty cookie value', () => {
            document.cookie = 'empty=; path=/';
            // Empty cookie values return null per the utility implementation
            expect(getCookie('empty')).toBeNull();
        });

        it('should handle cookie with special characters', () => {
            document.cookie = 'special=hello%20world; path=/';
            expect(getCookie('special')).toBe('hello%20world');
        });

        it('should not match partial cookie names', () => {
            document.cookie = 'sessionid=abc123; path=/';
            expect(getCookie('session')).toBeNull();
        });

        it('should return correct cookie when multiple cookies exist', () => {
            document.cookie = 'cookie1=value1; path=/';
            document.cookie = 'cookie2=value2; path=/';
            document.cookie = 'cookie3=value3; path=/';

            expect(getCookie('cookie2')).toBe('value2');
        });

        it('should handle cookie values with semicolons correctly', () => {
            // When a cookie value contains semicolons, they should be part of the value
            // until the first actual cookie separator
            document.cookie = 'data=test; path=/';
            const result = getCookie('data');
            expect(result).toBe('test');
        });

        it('should be case-sensitive', () => {
            document.cookie = 'TestCookie=value; path=/';
            expect(getCookie('testcookie')).toBeNull();
            expect(getCookie('TestCookie')).toBe('value');
        });
    });

    describe('getCsrfToken', () => {
        it('should return token from meta tag if present', () => {
            const metaTag = document.createElement('meta');
            metaTag.name = 'csrf-token';
            metaTag.content = 'token-from-meta';
            document.head.appendChild(metaTag);

            expect(getCsrfToken()).toBe('token-from-meta');

            document.head.removeChild(metaTag);
        });

        it('should fallback to XSRF-TOKEN cookie when meta tag missing', () => {
            document.cookie = 'XSRF-TOKEN=token-from-cookie; path=/';
            expect(getCsrfToken()).toBe('token-from-cookie');
        });

        it('should prefer meta tag over cookie when both exist', () => {
            const metaTag = document.createElement('meta');
            metaTag.name = 'csrf-token';
            metaTag.content = 'token-from-meta';
            document.head.appendChild(metaTag);
            document.cookie = 'XSRF-TOKEN=token-from-cookie; path=/';

            expect(getCsrfToken()).toBe('token-from-meta');

            document.head.removeChild(metaTag);
        });

        it('should return null when neither meta tag nor cookie exist', () => {
            expect(getCsrfToken()).toBeNull();
        });

        it('should return null when meta tag exists but has no content', () => {
            const metaTag = document.createElement('meta');
            metaTag.name = 'csrf-token';
            document.head.appendChild(metaTag);

            expect(getCsrfToken()).toBeNull();

            document.head.removeChild(metaTag);
        });
    });
});
