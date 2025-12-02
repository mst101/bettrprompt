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
                api_usage: null,
                error: {
                    message: 'Failed to parse LLM response',
                    details: e.message,
                    raw_response: apiResponse,
                },
            },
        },
    ];
}

// Extract API usage data
const apiUsage = {
    model: apiResponse.model || 'claude-haiku-4-5',
    input_tokens: apiResponse.usage?.input_tokens || 0,
    output_tokens: apiResponse.usage?.output_tokens || 0,
};

return [
    {
        json: {
            success: true,
            data: analysisData,
            original_input: originalInput,
            api_usage: apiUsage,
            error: null,
        },
    },
];
