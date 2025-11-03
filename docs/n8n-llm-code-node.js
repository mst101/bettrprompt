// Code Node: Call Anthropic API
// This replaces the HTTP Request node to avoid JSON syntax issues

const items = $input.all();
const data = items[0].json;

// Get your Anthropic API key from n8n environment or hardcode it here
const apiKey = 'YOUR_ANTHROPIC_API_KEY'; // Replace with your actual key

const response = await fetch('https://api.anthropic.com/v1/messages', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'x-api-key': apiKey,
    'anthropic-version': '2023-06-01'
  },
  body: JSON.stringify({
    model: 'claude-3-5-sonnet-20241022',
    max_tokens: 2000,
    system: data.system_prompt,
    messages: [
      {
        role: 'user',
        content: data.user_message
      }
    ]
  })
});

const result = await response.json();

if (!response.ok) {
  throw new Error(`API Error: ${JSON.stringify(result)}`);
}

return [{ json: result }];
