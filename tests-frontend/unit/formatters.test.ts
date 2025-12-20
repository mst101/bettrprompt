import { formatDate, truncateText } from '@/Utils/formatting/formatters';
import { describe, expect, it } from 'vitest';

describe('formatters utilities', () => {
    describe('formatDate', () => {
        it('should format date string with default options', () => {
            const result = formatDate('2024-01-15T10:30:00Z');
            // British locale format: "15 Jan 2024, 10:30"
            expect(result).toMatch(/15 Jan 2024/);
        });

        it('should format Date object', () => {
            const date = new Date('2024-01-15T10:30:00Z');
            const result = formatDate(date);
            expect(result).toMatch(/15 Jan 2024/);
        });

        it('should accept custom formatting options', () => {
            const result = formatDate('2024-01-15', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
            });
            expect(result).toContain('January');
            expect(result).toContain('2024');
        });

        it('should format with only date when specified', () => {
            const result = formatDate('2024-01-15', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
            });
            expect(result).toMatch(/\d{2}\/\d{2}\/\d{4}/);
        });

        it('should handle different date formats', () => {
            const result = formatDate('2024-12-25T15:45:30Z');
            expect(result).toMatch(/25/);
            expect(result).toMatch(/Dec/);
            expect(result).toMatch(/2024/);
        });

        it('should format dates in British English locale', () => {
            // British format should use 24-hour time by default
            const result = formatDate('2024-01-15T15:30:00Z');
            expect(result).toContain('15:30');
        });

        it('should handle beginning of year dates', () => {
            const result = formatDate('2024-01-01T00:00:00Z');
            expect(result).toMatch(/01 Jan 2024/);
        });

        it('should handle end of year dates', () => {
            const result = formatDate('2024-12-31T23:59:59Z');
            expect(result).toMatch(/31 Dec 2024/);
        });
    });

    describe('truncateText', () => {
        it('should return text as-is if shorter than maxLength', () => {
            expect(truncateText('Hello', 20)).toBe('Hello');
        });

        it('should truncate text longer than maxLength', () => {
            const longText =
                'This is a very long sentence that needs truncating';
            const result = truncateText(longText, 20);
            // Should start with the correct prefix and end with ellipsis
            expect(result).toContain('...');
            expect(result.length).toBe(23); // 20 chars + '...'
        });

        it('should use default maxLength of 100', () => {
            const text = 'a'.repeat(101);
            expect(truncateText(text)).toBe('a'.repeat(100) + '...');
        });

        it('should return empty string for null', () => {
            expect(truncateText(null)).toBe('');
        });

        it('should return empty string for undefined', () => {
            expect(truncateText(undefined)).toBe('');
        });

        it('should return empty string for empty string', () => {
            expect(truncateText('')).toBe('');
        });

        it('should handle exact length match', () => {
            expect(truncateText('12345', 5)).toBe('12345');
        });

        it('should add ellipsis only when truncating', () => {
            expect(truncateText('12345', 6)).toBe('12345');
            expect(truncateText('12345', 4)).toBe('1234...');
        });

        it('should handle single character with low maxLength', () => {
            expect(truncateText('a', 1)).toBe('a');
            expect(truncateText('ab', 1)).toBe('a...');
        });

        it('should work with special characters', () => {
            const text = 'Hello! @#$%^&*()';
            expect(truncateText(text, 10)).toBe('Hello! @#$...');
        });

        it('should work with whitespace', () => {
            const text = 'Hello   World   Test';
            expect(truncateText(text, 10)).toBe('Hello   Wo...');
        });

        it('should work with emojis', () => {
            const text = 'Hello 👋 World 🌍';
            expect(truncateText(text, 12)).toContain('...');
        });

        it('should work with unicode characters', () => {
            const text = 'Héllo Wørld Tëst';
            const result = truncateText(text, 10);
            // Should end with ellipsis and truncate at 10 chars
            expect(result).toContain('...');
            expect(result.substring(0, 10)).toBe('Héllo Wørl');
        });

        it('should handle zero maxLength', () => {
            expect(truncateText('Hello', 0)).toBe('...');
        });
    });
});
