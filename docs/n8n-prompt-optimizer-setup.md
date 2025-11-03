# n8n Prompt Optimiser Workflow Setup

This guide will help you create the n8n workflow for the MVP Phase 1 Prompt Optimiser feature.

## Prerequisites

- n8n is accessible at `https://n8n.localhost` (via Caddy/Docker/Sail)
- Credentials: `admin` / `password` (as configured in `.env`)
- An LLM API key (OpenAI, Anthropic, etc.)

## Workflow Overview

The workflow receives a request from Laravel, processes it through an LLM, and returns an optimised prompt.

**Flow:**
```
Webhook (POST)
  → Code Node (validate input)
  → HTTP Request (call LLM API)
  → Code Node (format response)
  → Respond to Webhook
```

## Step-by-Step Setup

### 1. Access n8n Dashboard

1. Open your browser to `https://n8n.localhost`
2. Accept the self-signed certificate (one-time browser warning)
3. Log in with username: `admin`, password: `password`
4. Click "Create new workflow"

### 2. Add Webhook Node (Trigger)

1. Click the "+" button and search for "Webhook"
2. Select "Webhook" node
3. Configure:
   - **HTTP Method**: POST
   - **Path**: `prompt-optimizer`
   - **Authentication**: None (we handle this in Laravel)
   - **Respond**: "Using 'Respond to Webhook' Node"

4. Note the **Production URL** shown in n8n's UI (it may show `http://localhost:5678/webhook/prompt-optimizer`)

   **Important:** Laravel uses the internal Docker URL (`http://n8n:5678/webhook/prompt-optimizer`) as configured in your `.env` file's `N8N_INTERNAL_URL`. The URL shown in n8n's UI is for reference only.

### 3. Add Code Node (Input Validation)

1. Click "+" after the Webhook node
2. Search for "Code" and select it
3. Rename to: "Validate Input"
4. Set **Mode**: "Run Once for All Items"
5. Add this code:

```javascript
// Validate and extract input from webhook
const items = $input.all();
const body = items[0].json.body;

// Validate required fields
if (!body.personality_type) {
  throw new Error('Missing personality_type');
}
if (!body.task_description) {
  throw new Error('Missing task_description');
}

// Prepare data for LLM
const personalityType = body.personality_type;
const taskDescription = body.task_description;
const traitPercentages = body.trait_percentages || {};

// Build context about personality type
const personalityContext = `You are helping someone with personality type ${personalityType} optimise an AI prompt.`;

// Build the system prompt
const systemPrompt = `You are an expert at crafting AI prompts tailored to personality types based on the 16personalities.com framework.

Given a personality type and a task description, create an optimised prompt that:
1. Takes into account the personality traits and communication style
2. Uses appropriate prompt frameworks (SMART, RICE, COAST, etc.)
3. Includes relevant context and constraints
4. Specifies desired output format
5. Is clear, specific, and actionable

Return ONLY the optimised prompt text, without any preamble or explanation.`;

// Build the user message
const userMessage = `Personality Type: ${personalityType}
${Object.keys(traitPercentages).length > 0 ? `Trait Percentages: ${JSON.stringify(traitPercentages)}` : ''}

Task Description:
${taskDescription}

Create an optimised AI prompt for this task that is tailored to this personality type.`;

return [
  {
    json: {
      prompt_run_id: body.prompt_run_id,
      personality_type: personalityType,
      task_description: taskDescription,
      trait_percentages: traitPercentages,
      system_prompt: systemPrompt,
      user_message: userMessage
    }
  }
];
```

### 4. Add Code Node (LLM API Call)

**IMPORTANT**: We use a Code node instead of HTTP Request to avoid JSON syntax issues.

1. Click "+" and search for "Code"
2. Rename to: "Call LLM API"
3. Set **Mode**: "Run Once for All Items"
4. Add this code:

**Option A: Using Anthropic Claude** (Recommended)

```javascript
const items = $input.all();
const data = items[0].json;

// Replace with your Anthropic API key
const apiKey = 'YOUR_ANTHROPIC_API_KEY';

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
  throw new Error(`Anthropic API Error: ${JSON.stringify(result)}`);
}

return [{ json: result }];
```

**Option B: Using OpenAI**

```javascript
const items = $input.all();
const data = items[0].json;

// Replace with your OpenAI API key
const apiKey = 'YOUR_OPENAI_API_KEY';

const response = await fetch('https://api.openai.com/v1/chat/completions', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': `Bearer ${apiKey}`
  },
  body: JSON.stringify({
    model: 'gpt-4o',
    messages: [
      {
        role: 'system',
        content: data.system_prompt
      },
      {
        role: 'user',
        content: data.user_message
      }
    ],
    temperature: 0.7,
    max_tokens: 2000
  })
});

const result = await response.json();

if (!response.ok) {
  throw new Error(`OpenAI API Error: ${JSON.stringify(result)}`);
}

return [{ json: result }];
```

### 5. Add Code Node (Format Response)

1. Click "+" and search for "Code"
2. Rename to: "Format Response"
3. Set **Mode**: "Run Once for All Items"
4. Add this code:

**For OpenAI:**
```javascript
// Extract the optimised prompt from OpenAI response
const items = $input.all();
const response = items[0].json;

let optimisedPrompt = '';

if (response.choices && response.choices[0]?.message?.content) {
  optimisedPrompt = response.choices[0].message.content.trim();
} else {
  throw new Error('Invalid response from LLM');
}

// Get the original data from the first node
const webhookData = $node["Validate Input"].json;

return [
  {
    json: {
      optimized_prompt: optimisedPrompt,
      prompt_run_id: webhookData.prompt_run_id,
      personality_type: webhookData.personality_type,
      model_used: response.model || 'gpt-4o',
      tokens_used: response.usage?.total_tokens || 0
    }
  }
];
```

**For Anthropic:**
```javascript
// Extract the optimised prompt from Anthropic response
const items = $input.all();
const response = items[0].json;

let optimisedPrompt = '';

if (response.content && response.content[0]?.text) {
  optimisedPrompt = response.content[0].text.trim();
} else {
  throw new Error('Invalid response from LLM');
}

// Get the original data from the first node
const webhookData = $node["Validate Input"].json;

return [
  {
    json: {
      optimized_prompt: optimisedPrompt,
      prompt_run_id: webhookData.prompt_run_id,
      personality_type: webhookData.personality_type,
      model_used: response.model || 'claude-3-5-sonnet-20241022',
      tokens_used: response.usage?.input_tokens + response.usage?.output_tokens || 0
    }
  }
];
```

### 6. Add Respond to Webhook Node

1. Click "+" and search for "Respond to Webhook"
2. Configure:
   - **Response Code**: 200
   - **Response Body**: `={{ $json }}`

### 7. Save and Activate

1. Click "Save" at the top right
2. Name your workflow: "Prompt Optimiser"
3. Toggle the workflow to **Active** (switch in top right)

## Testing the Workflow

### Test in n8n

1. Click "Test workflow" button
2. Click "Listen for test event" on the Webhook node
3. Use this curl command to test (from your host machine):

```bash
curl -X POST https://n8n.localhost/webhook-test/prompt-optimizer \
  -H "Content-Type: application/json" \
  -k \
  -d '{
    "prompt_run_id": 1,
    "personality_type": "INTJ-A",
    "task_description": "Write a technical blog post about microservices architecture"
  }'
```

**Note:** The `-k` flag allows curl to accept the self-signed certificate.

4. Check that you receive a response with `optimized_prompt`

### Test from Laravel

Once the workflow is active, test from your application:

1. Start the development server:
   ```bash
   composer dev
   ```

2. Navigate to `https://app.localhost/prompt-optimizer`
3. Accept the self-signed certificate if prompted
4. Fill in the form and submit
5. Check that the optimised prompt is displayed

## Troubleshooting

### Webhook Not Found
- Ensure the workflow is **Active** (not just saved)
- Check the webhook path matches: `/webhook/prompt-optimizer`

### LLM API Errors
- Verify your API key is correct
- Check you have sufficient credits/quota
- Review rate limits

### Laravel Connection Issues
- Verify `N8N_INTERNAL_URL` in `.env` is `http://n8n:5678` (for Docker internal communication)
- Check Docker containers are running: `./vendor/bin/sail ps`

### Response Format Issues
- Check the "Format Response" code matches your LLM provider
- Verify the response structure in n8n's execution logs

## Webhook URL Reference

**For Laravel (internal Docker network):**
```
http://n8n:5678/webhook/prompt-optimizer
```
This is configured in `.env` as `N8N_INTERNAL_URL=http://n8n:5678`

**For external testing (via Caddy HTTPS):**
```
https://n8n.localhost/webhook/prompt-optimizer
```
Use `-k` flag with curl to accept self-signed certificate.

**For direct access (bypassing Caddy):**
```
http://localhost:5678/webhook/prompt-optimizer
```
Only works if n8n port 5678 is exposed.

## Next Steps

1. Add error handling nodes (optional)
2. Add logging/monitoring (optional)
3. Consider adding a queue for long-running requests
4. Implement feedback collection for prompt quality
5. Build analytics on prompt effectiveness

## Advanced: Adding Error Handling

To make the workflow more robust, you can add an "If" node after the LLM request to check for errors and send appropriate responses:

1. Add "If" node after HTTP Request
2. Check if `{{ $json.error }}` exists
3. Route errors to a separate "Respond to Webhook" with error message
4. Route successes to the "Format Response" node

This ensures Laravel always gets a proper response even if the LLM fails.
