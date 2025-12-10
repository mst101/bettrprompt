# Quick Start: n8n Workflow Debug Program

A simple debug program to inspect n8n workflow input, JavaScript code, and output without running the full n8n system.

## 30-Second Setup

1. **Start your development server:**
   ```bash
   composer dev
   ```

2. **Visit the debug interface:**
   ```
   https://app.localhost/workflow/1
   ```

3. **Load example files:**
   - Click "Load Input File" → Select `examples/workflow_1_input_example.json`
   - Click "Load JavaScript" → Select `examples/workflow_1_prepare_prompt_example.js`

4. **Execute the workflow:**
   - Click "Execute" button
   - View results in the right column

## Using the Web Interface

### Step 1: Load Input
```
1. Click "Load Input File"
2. Select your JSON file (e.g., workflow_1_input.json)
3. Input appears in the left column
```

### Step 2: Load or Edit JavaScript
```
1. Click "Load JavaScript" to load from file, OR
2. Paste/edit code directly in the middle column
3. Code is automatically saved when you load a file
```

### Step 3: Execute
```
1. Click "Execute" button
2. Output appears in the right column with:
   - System Prompt
   - Messages array
   - Full output (collapsible)
```

## Using the CLI

### Save input from file:
```bash
node debug-workflow.js --workflow 1 --input workflow_1_input.json
```

### Save JavaScript from file:
```bash
node debug-workflow.js --workflow 1 --javascript workflow_1_prepare_prompt.js
```

### Execute the workflow:
```bash
node debug-workflow.js --workflow 1 --execute
```

### Check status:
```bash
node debug-workflow.js --workflow 1 --show
```

## File Structure

Files are saved to: `storage/app/debug/`

```
storage/app/debug/
├── workflow_0_input.json              # Pre-analysis input
├── workflow_0_prepare_prompt.js       # Pre-analysis JavaScript
├── workflow_0_output.json             # Pre-analysis output
├── workflow_1_input.json              # Framework analysis input
├── workflow_1_prepare_prompt.js       # Framework analysis JavaScript
├── workflow_1_output.json             # Framework analysis output
├── workflow_2_input.json              # Prompt generation input
├── workflow_2_prepare_prompt.js       # Prompt generation JavaScript
└── workflow_2_output.json             # Prompt generation output
```

## Example Workflow Input

The input should match your actual webhook payload structure:

```json
{
  "body": {
    "task_description": "I want to learn to ride a bicycle",
    "pre_analysis_context": { ... },
    "user_context": { ... }
  },
  "headers": { ... }
}
```

See: `examples/workflow_1_input_example.json`

## Example JavaScript Code

```javascript
// Access webhook input
const webhookData = $('Webhook Trigger').first().json.body || {};

// Access reference documents
const referenceData = $('Load Reference Documents').first().json;

// Build output
const system = "You are an AI assistant...";
const messages = [
  { role: "user", content: "Task: " + webhookData.task_description }
];

// Return results
return { system, messages };
```

See: `examples/workflow_1_prepare_prompt_example.js`

## Available Workflows

- **Workflow 0:** Pre-analysis (clarifying questions)
- **Workflow 1:** Framework analysis (select best framework)
- **Workflow 2:** Prompt generation (create optimised prompt)

Access each at: `https://app.localhost/workflow/0`, `https://app.localhost/workflow/1`, `https://app.localhost/workflow/2`

## Features

✅ Load input from JSON files
✅ Edit JavaScript code directly in the interface
✅ Execute JavaScript with mock n8n objects
✅ View formatted output (system prompt + messages)
✅ Save/load progress automatically
✅ Download workflow output as JSON
✅ CLI tool for automation

## Troubleshooting

### "Node.js not found"
Install Node.js: `node --version` should return a version number

### "Cannot find module"
Some JavaScript features may not be available. Use only standard JavaScript.

### Output is empty
Make sure both input and JavaScript are loaded, then click Execute.

### Files not showing in web interface
Files are saved automatically to `storage/app/debug/`

## More Information

See the full documentation:
- `DEBUG.md` - Complete reference guide
- `examples/README.md` - Examples for each workflow
- `examples/workflow_1_input_example.json` - Sample input
- `examples/workflow_1_prepare_prompt_example.js` - Sample code

## API Endpoints

Direct API access (for advanced usage):

```bash
# Save input
curl -X POST https://app.localhost/api/debug/workflow_1/input \
  -H "Content-Type: application/json" \
  -d @input.json

# Save JavaScript
curl -X POST https://app.localhost/api/debug/workflow_1/javascript \
  -H "Content-Type: application/json" \
  -d '{"code": "..."}'

# Execute workflow
curl -X POST https://app.localhost/api/debug/workflow_1/execute
```

## Next Steps

1. Try the examples to understand the structure
2. Adapt the examples for your specific workflows
3. Use the debug program to refine prompts before deploying to n8n
4. Check the output formatting and adjust JavaScript as needed
