/**
 * Get a cookie value by name
 */
export function getCookie(name: string): string | null {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);

    if (parts.length === 2) {
        return parts.pop()?.split(';').shift() || null;
    }

    return null;
}

/**
 * Get CSRF token from meta tag or cookie
 */
export function getCsrfToken(): string | null {
    // First try meta tag (Laravel's default)
    const metaTag = document.querySelector('meta[name="csrf-token"]');
    if (metaTag) {
        return metaTag.getAttribute('content');
    }

    // Fallback to XSRF-TOKEN cookie
    return getCookie('XSRF-TOKEN');
}
