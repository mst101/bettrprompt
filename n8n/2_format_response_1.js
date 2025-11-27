const apiResponse = $input.first().json;

// Anthropic response: top-level content array with { type: 'text', text: '...' }
const firstContent = Array.isArray(apiResponse.content)
    ? apiResponse.content[0]
    : null;
let messageContent = firstContent?.text || null;

if (messageContent && typeof messageContent === 'string') {
    messageContent = messageContent
        .replace(/```json\s*/g, '')
        .replace(/```\s*/g, '')
        .trim();
}

let data;
try {
    data = messageContent ? JSON.parse(messageContent) : null;
} catch (e) {
    return [
        {
            json: {
                success: false,
                data: null,
                error: {
                    message: 'Failed to parse LLM response',
                    details: e.message,
                    raw: messageContent,
                },
            },
        },
    ];
}

return [
    {
        json: {
            success: true,
            data,
            error: null,
        },
    },
];
