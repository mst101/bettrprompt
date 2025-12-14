import { useTextAppend } from '@/Composables/useTextAppend';
import { describe, expect, it } from 'vitest';

describe('useTextAppend', () => {
    const { appendText } = useTextAppend();

    it('should append text to empty string', () => {
        const result = appendText('', 'Hello');
        expect(result).toBe('Hello');
    });

    it('should append text with space when existing text ends without space', () => {
        const result = appendText('Hello', 'world');
        expect(result).toBe('Hello world');
    });

    it('should append text with existing space without adding extra space', () => {
        const result = appendText('Hello ', 'world');
        expect(result).toBe('Hello world');
    });

    it('should handle appending to single space', () => {
        const result = appendText(' ', 'text');
        expect(result).toBe(' text');
    });

    it('should append empty string', () => {
        const result = appendText('Hello', '');
        expect(result).toBe('Hello ');
    });

    it('should handle multiple appends in sequence', () => {
        let text = 'Hello';
        text = appendText(text, 'world');
        text = appendText(text, 'from');
        text = appendText(text, 'Vue');
        expect(text).toBe('Hello world from Vue');
    });

    it('should preserve spaces in new text', () => {
        const result = appendText('Hello', 'world test');
        expect(result).toBe('Hello world test');
    });

    it('should handle text with special characters', () => {
        const result = appendText('Hello,', 'world!');
        expect(result).toBe('Hello, world!');
    });

    it('should handle text with punctuation', () => {
        const result = appendText('Hello.', 'This is new text.');
        expect(result).toBe('Hello. This is new text.');
    });

    it('should handle text with newlines', () => {
        const result = appendText('Line 1', 'Line 2');
        expect(result).toBe('Line 1 Line 2');
    });
});
