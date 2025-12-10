# n8n Workflow Debug Examples

This directory contains example input and JavaScript files for debugging n8n workflows.

## Workflow 0: Pre-Analysis

**Files:**

- `workflow_0_input_example.json` - Sample input for pre-analysis workflow
- `workflow_0_prepare_prompt_example.js` - Sample JavaScript to prepare prompts

**Purpose:** Gathers clarifying questions to better understand the learning task

## Workflow 1: Framework Analysis

**Files:**

- `workflow_1_input_example.json` - Sample input for framework analysis workflow
- `workflow_1_prepare_prompt_example.js` - Sample JavaScript to prepare prompts

**Purpose:** Selects the best framework for the task based on user personality and context

## Workflow 2: Prompt Generation

**Files:**

- `workflow_2_input_example.json` - Sample input for prompt generation workflow
- `workflow_2_prepare_prompt_example.js` - Sample JavaScript to prepare prompts

**Purpose:** Generates an optimised AI prompt using the selected framework

## Using These Examples

### Quick Start

1. **Copy an example to your workflow:**
   ```bash
   cp debug/workflow_1_input_example.json workflow_1_input.json
   cp debug/workflow_1_prepare_prompt_example.js workflow_1_prepare_prompt.js
   ```

2. **Load in the debug interface:**
    - Visit `https://app.localhost/workflow/1`
    - Click "Load Input File" and select `workflow_1_input.json`
    - Click "Load JavaScript" and select `workflow_1_prepare_prompt.js`

3. **Execute the workflow:**
    - Click "Execute" button
    - View the results in the output column

### Command Line Usage

```bash
# Load the input example
node debug-workflow.js --workflow 1 --input debug/workflow_1_input_example.json

# Load the JavaScript example
node debug-workflow.js --workflow 1 --javascript debug/workflow_1_prepare_prompt_example.js

# Execute the workflow
node debug-workflow.js --workflow 1 --execute

# View results
node debug-workflow.js --workflow 1 --show
```

## Example Output Structure

After execution, the output will contain:

```json
{
    "system": "System prompt text...",
    "messages": [
        {
            "role": "user",
            "content": "User message content..."
        },
        {
            "role": "assistant",
            "content": "Assistant response..."
        }
    ]
}
```

## Customising Examples

1. **Modify the input:**
    - Edit the JSON file to change task description, personality type, location, etc.
    - Save and reload in the debug interface

2. **Modify the JavaScript:**
    - Edit the `.js` file to change how the prompt is built
    - The `$('Node Name').first().json` syntax accesses workflow node data
    - Variables `system` and `messages` are returned as output

3. **Test different scenarios:**
    - Create multiple input files for different personality types
    - Test with different learning tasks and contexts
    - Verify the JavaScript handles edge cases

## Available Mock Objects

In the JavaScript code, you have access to:

### $('Node Name') - Access workflow node data

```javascript
// Webhook input
const webhookData = $('Webhook Trigger').first().json.body;

// Reference documents
const referenceData = $('Load Reference Documents').first().json;
const frameworkDoc = referenceData.framework_taxonomy_doc;
const questionDoc = referenceData.question_bank_doc;
```

### Variables to return

```javascript
// Required: System prompt for the AI model
const system = "You are...";

// Required: Messages array with user/assistant messages
const messages = [
    { role: "user", content: "..." },
    { role: "assistant", content: "..." }
];

return { system, messages };
```

## Troubleshooting

### "Cannot read property 'body' of undefined"

Make sure your input JSON has the correct structure with `body` property

### "system is not defined"

Your JavaScript must define and return `system` variable

### "messages is not defined"

Your JavaScript must define and return `messages` array with objects containing `role` and `content`

### JavaScript syntax errors

Check the browser console (F12) for detailed error messages

## Next Steps

1. Test the examples to understand the workflow structure
2. Adapt examples for your specific use case
3. Use the debug interface to refine prompts before deploying to n8n
