# Workflow Debug Solution - Real n8n Integration

The debug system now loads real n8n workflow files but simplifies them for execution.

## How It Works

### 1. File Loading
When you visit `https://app.localhost/workflow/1`:
- **Input**: Loads from `n8n/workflow_1_input.json` (or falls back to `storage/app/debug/`)
- **JavaScript**: Extracts from the "Prepare Prompt" node in `n8n/workflow_1_analysis.json`

### 2. Automatic Normalization
The complex n8n workflow code is automatically converted:
- `const` → `var` (for eval() compatibility)
- `let` → `var` (for eval() compatibility)
- Return statements are removed
- Variables are properly captured

### 3. Execution
The JavaScript runs with mock n8n objects:
- `$('Webhook Trigger').first().json.body` - webhook input
- `$('Load Reference Documents').first().json` - reference docs

### 4. Output
The debug system captures:
- `systemPrompt` variable → displayed as "system"
- `userMessage` variable → wrapped in "messages" array

## Testing Real n8n Workflows

The system automatically finds and uses your real workflow files:

```
n8n/workflow_1_analysis.json
n8n/workflow_1_input.json
```

No manual setup needed - just visit:
```
https://app.localhost/workflow/1
```

## What Gets Displayed

### Left Column (Input)
Shows the webhook payload from `n8n/workflow_1_input.json`:
```json
{
  "body": {
    "task_description": "...",
    "pre_analysis_context": {...},
    "user_context": {...}
  }
}
```

### Middle Column (JavaScript)
Shows the "Prepare Prompt" node code extracted from the n8n workflow.
You can edit this directly to test changes.

### Right Column (Output)
Shows the captured output:
- `system`: The system prompt
- `messages`: Array with user message

## Limitations & Solutions

### The n8n workflow code is complex
The real n8n workflows have long, complex code with many variables and complex logic.

**Solution**: The debug system:
1. Automatically converts to compatible format
2. Removes the return statement (keeping all variable assignments)
3. Captures the final `systemPrompt` and `userMessage` variables

### Some variables might be undefined
If the workflow code relies on reference documents that aren't provided, they'll be empty objects.

**Solution**: The mock environment provides:
- `$('Webhook Trigger').first().json` - your input data
- `$('Load Reference Documents').first().json` - empty object (can be populated)

## Example

When you visit `https://app.localhost/workflow/1`:

1. The system reads `n8n/workflow_1_analysis.json`
2. Finds the "Prepare Prompt" node
3. Extracts its JavaScript code
4. Converts `const`/`let` to `var`
5. Loads `n8n/workflow_1_input.json` as input
6. Executes the code with mock `$` object
7. Captures the `systemPrompt` and `userMessage` variables
8. Displays them as `system` and `messages`

## For Development

If you want to test changes without modifying the real n8n workflows:
1. Edit the JavaScript in the middle column
2. Click "Execute"
3. See the output in real-time

The changes are automatically saved to `storage/app/debug/` for future use.

## Files Used

- **Input**: `n8n/workflow_X_input.json` (primary) or `storage/app/debug/workflow_X_input.json` (fallback)
- **Code**: Extracted from `n8n/workflow_X_analysis.json` "Prepare Prompt" node (primary) or `storage/app/debug/workflow_X_prepare_prompt.js` (fallback)
- **Output**: Saved to `storage/app/debug/workflow_X_output.json`
