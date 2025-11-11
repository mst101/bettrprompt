/**
 * Composable for appending transcribed text to existing text with proper spacing
 */
export function useTextAppend() {
    const appendText = (existingText: string, newText: string): string => {
        let result = existingText;

        // Add space if existing text doesn't end with one
        if (result && !result.endsWith(' ')) {
            result += ' ';
        }

        result += newText;

        return result;
    };

    return {
        appendText,
    };
}
