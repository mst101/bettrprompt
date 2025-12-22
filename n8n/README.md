# n8n Workflows

This directory contains n8n workflow JSON files for the BettrPrompt application.

## Workflows

### 1. Framework Selector.json

**Purpose**: Analyses a user's task description and personality type to select the optimal prompt engineering framework
and generate clarifying questions.

**Webhook Endpoint**: `POST /webhook/framework-selector`

**Input Payload**:

```json
{
    "prompt_run_id": 123,
    "personality_type": "INTJ-A",
    "task_description": "Write a function to...",
    "trait_percentages": {
        "mind": 60,
        "energy": 75,
        "nature": 80,
        "tactics": 70,
        "identity": 65
    }
}
```

**Success Response** (HTTP 200):

```json
{
    "prompt_run_id": 123,
    "selected_framework": "CRISPE",
    "framework_reasoning": "CRISPE is ideal for this technical documentation task because...",
    "framework_questions": [
        "What is the expected input format?",
        "Are there any performance constraints?",
        "Should the function handle edge cases?"
    ],
    "model_used": "claude-3-5-haiku-20241022",
    "tokens_used": {
        "input": 1250,
        "output": 320,
        "total": 1570
    }
}
```

**Error Response** (HTTP 500):

```json
[
    {
        "prompt_run_id": 123,
        "success": false,
        "error": "Overloaded",
        "details": {
            "description": "Overloaded",
            "http_code": "529",
            "error_type": "overloaded_error",
            "api_message": "Overloaded",
            "node_name": "Call Claude Haiku",
            "time": "06/11/2025, 10:08:35"
        }
    }
]
```

### 2. Final Prompt Optimizer.json

**Purpose**: Takes the selected framework, clarifying answers, and task details to generate the final optimised AI
prompt.

**Webhook Endpoint**: `POST /webhook/final-prompt-builder`

**Input Payload**:

```json
{
    "prompt_run_id": 123,
    "personality_type": "INTJ-A",
    "task_description": "Write a function to...",
    "trait_percentages": {
        "mind": 60,
        "energy": 75,
        "nature": 80,
        "tactics": 70,
        "identity": 65
    },
    "selected_framework": "CRISPE",
    "framework_reasoning": "CRISPE is ideal because...",
    "framework_questions": [
        "What is the expected input format?",
        "Are there any performance constraints?",
        "Should the function handle edge cases?"
    ],
    "clarifying_answers": [
        "JSON format with id and name fields",
        "Must process 1000+ items per second",
        "Yes, handle null values and empty arrays"
    ]
}
```

**Success Response** (HTTP 200):

```json
{
    "prompt_run_id": 123,
    "optimized_prompt": "You are an expert software engineer...\n\n[Full optimised prompt text]",
    "personality_type": "INTJ-A",
    "model_used": "claude-3-7-sonnet-20250219",
    "tokens_used": {
        "input": 2500,
        "output": 850,
        "total": 3350
    }
}
```

**Error Response** (HTTP 500):

```json
[
    {
        "prompt_run_id": 123,
        "success": false,
        "error": "Overloaded",
        "details": {
            "description": "Overloaded",
            "http_code": "529",
            "error_type": "overloaded_error",
            "api_message": "Overloaded",
            "node_name": "Call Claude Sonnet",
            "time": "06/11/2025, 10:08:35"
        }
    }
]
```

## Workflow Nodes

### Framework Selector Workflow

1. **Webhook** - Receives POST requests from Laravel
2. **Validate Input** - Validates required fields (personality_type, task_description, prompt_run_id)
3. **Load Framework Matrix** - Loads the comprehensive framework selection guide
4. **Build LLM Prompt** - Constructs system and user messages for Claude
5. **Call Claude Haiku** - Makes API call to Anthropic Claude API
    - **continueOnFail: true** - Allows workflow to continue even if API call fails
6. **Check Response Status** - Validates response (checks for errors and HTTP 200)
    - **Success path**: Proceeds to Parse Response
    - **Error path**: Proceeds to Format Error Response
7. **Parse Response** - Extracts framework, reasoning, and questions from LLM response
8. **Format Error Response** - Formats error details into structured JSON
9. **Respond to Webhook** - Returns success response to Laravel (HTTP 200)
10. **Respond with Error** - Returns error response to Laravel (HTTP 500)

### Final Prompt Optimizer Workflow

1. **Webhook** - Receives POST requests from Laravel
2. **Validate Input** - Validates required fields and extracts all context data
3. **Build LLM Prompt** - Constructs comprehensive prompt with framework, Q&A, and personality context
4. **Call Claude Sonnet** - Makes API call to Anthropic Claude API (uses Sonnet for higher quality)
    - **continueOnFail: true** - Allows workflow to continue even if API call fails
5. **Check Response Status** - Validates response (checks for errors and HTTP 200)
    - **Success path**: Proceeds to Format Response
    - **Error path**: Proceeds to Format Error Response
6. **Format Response** - Extracts optimised prompt text and token usage
7. **Format Error Response** - Formats error details into structured JSON
8. **Respond to Webhook** - Returns success response to Laravel (HTTP 200)
9. **Respond with Error** - Returns error response to Laravel (HTTP 500)

## Error Handling

Both workflows include comprehensive error handling:

- **API Failures**: If the Claude API returns an error (rate limit, overload, auth failure, etc.), the workflow captures
  the error and returns it to Laravel
- **Continue On Fail**: The API call nodes have `continueOnFail: true`, which means n8n will pass the error to
  the next node instead of stopping execution
- **Error Detection**: The "Check Response Status" IF node checks:
    - `$json.error` is empty (no n8n error object)
    - `$json.statusCode` equals 200 (HTTP success)
- **Error Formatting**: The "Format Error Response" node extracts:
    - Error message from n8n's error object
    - HTTP status code
    - Anthropic API error type and description
    - Any additional error details

## Importing Workflows

To import a workflow into n8n:

1. Log in to your n8n instance (https://n8n.bettrprompt.ai)
2. Click "Workflows" in the left sidebar
3. Click "Add Workflow" → "Import from File"
4. Select the JSON file from this directory
5. Review and activate the workflow
6. Configure any required credentials (Anthropic API key)

## Testing Workflows

From Laravel Tinker:

```php
php artisan tinker

$client = app(\App\Services\N8nClient::class);
$result = $client->triggerWorkflow('framework-selector', [
    'prompt_run_id' => 1,
    'personality_type' => 'INTJ',
    'task_description' => 'Write a function to calculate fibonacci numbers'
]);

dd($result);
```

## Required Credentials

### Anthropic API (Header Auth)

The "Call Claude Haiku" node requires authentication credentials:

1. In n8n, go to **Credentials** → **Add Credential**
2. Select **Header Auth**
3. Configure:
    - **Name**: "Anthropic API"
    - **Header Name**: `x-api-key`
    - **Header Value**: Your Anthropic API key (starts with `sk-ant-`)
4. Save the credential
5. Update the "Call Claude Haiku" node to use this credential

## Deployment

See `/deployment/n8n-installation-guide.md` for instructions on setting up n8n in production.

## Framework Matrix

The workflow uses a comprehensive prompt engineering framework selection matrix that includes:

- **10 framework categories**: Structured Clarity, Iterative Refinement, Decision-Making, Reasoning & Analysis, etc.
- **30+ frameworks**: CRISPE, RELIC, RTF, RACEF, Chain of Thought, SCAMPER, BAB, SMART, RICE, etc.
- **Personality alignment**: Matches framework strengths to 16Personalities types
- **Task type recommendations**: Technical, Strategic, Creative, Marketing, Analysis, Customer Engagement, etc.

The matrix is embedded directly in the workflow code for fast access.
