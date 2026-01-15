const BLOCKED_PATH_PATTERNS = [/^\/[a-z]{2}\/admin(\/|$)/, /^\/horizon(\/|$)/];

export function isAnalyticsBlockedPath(pathname: string): boolean {
    return BLOCKED_PATH_PATTERNS.some((pattern) => pattern.test(pathname));
}

export function getAnalyticsPathname(url?: string): string | null {
    if (typeof window === 'undefined') {
        return null;
    }

    if (!url) {
        return window.location.pathname;
    }

    try {
        return new URL(url, window.location.origin).pathname;
    } catch (error) {
        console.warn('[Analytics] Failed to parse URL for tracking:', error);
        return url;
    }
}
