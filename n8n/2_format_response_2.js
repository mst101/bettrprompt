const llmResponse = $input.first().json;
const originalInput = $('Prepare Prompt').first().json.originalInput;

let generationData;
try {
    // The response might be in different formats depending on the node version
    let content = llmResponse.text || llmResponse.response?.text || llmResponse;

    if (typeof content === 'string') {
        // Remove any markdown code blocks if present
        content = content
            .replace(/```json\n?/g, '')
            .replace(/```\n?/g, '')
            .trim();
        generationData = JSON.parse(content);
    } else if (typeof content === 'object') {
        generationData = content;
    } else {
        throw new Error('Unexpected response format');
    }
} catch (e) {
    return [
        {
            json: {
                success: false,
                optimised_prompt: null,
                metadata: null,
                original_input: originalInput,
                error: {
                    message: 'Failed to parse LLM response',
                    details: e.message,
                    raw_response: llmResponse,
                },
            },
        },
    ];
}

return [
    {
        json: {
            success: true,
            optimised_prompt: generationData.optimised_prompt,
            metadata: generationData.metadata,
            original_input: originalInput,
            error: null,
        },
    },
];
