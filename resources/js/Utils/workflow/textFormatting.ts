import DOMPurify from 'dompurify';
import { marked } from 'marked';

export const renderMarkdown = (text: string | null | undefined): string => {
    if (!text) return '';
    const html = marked(text) as string;
    return DOMPurify.sanitize(html);
};

export const copyToClipboard = async (text: string | null | undefined) => {
    if (!text) return;
    try {
        await navigator.clipboard.writeText(text);
    } catch {
        // Silently fail - clipboard might not be available
    }
};

export const getMessagesAsText = (messages: unknown) => {
    if (!Array.isArray(messages)) {
        return JSON.stringify(messages, null, 2);
    }
    return messages
        .map((msg) => {
            if (typeof msg === 'object' && msg !== null) {
                if ((msg as Record<string, unknown>).content)
                    return String((msg as Record<string, unknown>).content);
                return JSON.stringify(msg, null, 2);
            }
            return String(msg);
        })
        .join('\n\n');
};
