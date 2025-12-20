/**
 * Formatting utilities for dates, text, and other display values
 */

/**
 * Format a date string or Date object to a localised string
 *
 * @param dateString - The date to format (string or Date object)
 * @param options - Intl.DateTimeFormatOptions to customise formatting
 * @returns Formatted date string in British English locale
 *
 * @example
 * formatDate('2024-01-15T10:30:00Z')
 * // Returns: "15 Jan 2024, 10:30"
 *
 * formatDate('2024-01-15', { dateStyle: 'full' })
 * // Returns: "Monday, 15 January 2024"
 */
export function formatDate(
    dateString: string | Date,
    options?: Intl.DateTimeFormatOptions,
): string {
    const defaultOptions: Intl.DateTimeFormatOptions = {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    };

    return new Date(dateString).toLocaleString(
        'en-GB',
        options ?? defaultOptions,
    );
}

/**
 * Truncate text to a maximum length, adding ellipsis if needed
 *
 * @param text - The text to truncate
 * @param maxLength - Maximum length before truncation (default: 100)
 * @returns Truncated text with ellipsis if longer than maxLength
 *
 * @example
 * truncateText('This is a very long sentence that needs truncating', 20)
 * // Returns: "This is a very long..."
 *
 * truncateText('Short text', 20)
 * // Returns: "Short text"
 *
 * truncateText(null, 20)
 * // Returns: ""
 */
export function truncateText(
    text: string | null | undefined,
    maxLength: number = 100,
): string {
    if (!text) return '';
    if (text.length <= maxLength) return text;
    return text.substring(0, maxLength) + '...';
}
