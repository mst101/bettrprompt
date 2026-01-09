export const DEFAULT_LOCALE = 'en-GB';

const localePattern = /^\/[a-z]{2}(?:-[A-Z]{2})?(?:\/|$)/;

export function withLocale(
    path: string,
    locale: string = DEFAULT_LOCALE,
): string {
    if (!path) {
        return `/${locale}`;
    }

    if (/^https?:\/\//.test(path)) {
        return path;
    }

    const match = path.match(/^([^?#]*)(.*)$/);
    const pathname = match?.[1] ?? path;
    const suffix = match?.[2] ?? '';

    if (pathname === '/') {
        return `/${locale}${suffix}`;
    }

    if (localePattern.test(pathname)) {
        return path;
    }

    const normalized = pathname.startsWith('/') ? pathname : `/${pathname}`;
    return `/${locale}${normalized}${suffix}`;
}
