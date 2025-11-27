const apiResponse = $input.first().json;
const originalInput = $('Prepare Prompt').first().json.originalInput;

let analysisData;
try {
    // Extract text from Anthropic API response
    let content = apiResponse.content?.[0]?.text || '';

    if (!content) {
        throw new Error('No content in API response');
    }

    // Remove any markdown code blocks if present
    content = content
        .replace(/```json\n?/g, '')
        .replace(/```\n?/g, '')
        .trim();

    // Try to parse JSON
    analysisData = JSON.parse(content);
} catch (e) {
    return [
        {
            json: {
                success: false,
                data: null,
                original_input: originalInput,
                error: {
                    message: 'Failed to parse LLM response',
                    details: e.message,
                    raw_response: apiResponse,
                },
            },
        },
    ];
}

return [
    {
        json: {
            success: true,
            data: analysisData,
            original_input: originalInput,
            error: null,
        },
    },
];
