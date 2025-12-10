# n8n Workflow Debug Program

This debug program allows you to inspect n8n workflow inputs, JavaScript code, and outputs without running the full n8n system.

## Quick Start

### 1. Save Webhook Input

Copy your webhook input data to a JSON file, then load it:

```bash
node debug-workflow.js --workflow 1 --input path/to/input.json
```

Or paste the data directly into the web interface at: `https://app.localhost/workflow/1`

### 2. Save JavaScript Code

Save the JavaScript from the "Prepare Prompt" node to a file:

```bash
node debug-workflow.js --workflow 1 --javascript path/to/code.js
```

Or paste it directly into the web interface.

### 3. Execute the Workflow

Once you have both input and JavaScript, execute it:

```bash
node debug-workflow.js --workflow 1 --execute
```

The output will be displayed in the terminal and saved to:
- `storage/app/debug/workflow_1_output.json`

### 4. View Results

Visit the web interface to see formatted results:

```
https://app.localhost/workflow/1
```

The interface shows:
- **Input Data** (left column) - The webhook input
- **JavaScript Code** (middle column) - Editable code
- **Output** (right column) - Formatted results with `system` and `messages`

## Web Interface Features

### Load Files
- Click "Load Input File" to upload a JSON file
- Click "Load JavaScript" to upload a `.js` file
- Files are saved to `storage/app/debug/workflow_X_*.json|js`

### Edit Code
- Edit JavaScript directly in the middle column
- Click "Execute" to run the code with the loaded input

### View Output
- The output column shows:
  - **System Prompt** - The system message for the AI model
  - **Messages** - Array of messages with role and content
  - **Full Output** - Complete output in a collapsible section

## Example Workflow Input

Save this as `workflow_1_input.json`:

```json
[
  {
    "headers": {
      "host": "n8n:5678",
      "user-agent": "GuzzleHttp/7",
      "content-type": "application/json"
    },
    "params": {},
    "query": {},
    "body": {
      "task_description": "I want to learn to ride a bicycle",
      "pre_analysis_context": {
        "current_skill_level": {
          "question": "What is your current cycling experience?",
          "answer": "beginner",
          "answer_label": "Complete beginner (never ridden)"
        }
      },
      "user_context": {
        "location": {
          "country": "United Kingdom",
          "country_code": "GB",
          "region": "England",
          "city": "London",
          "timezone": "Europe/London",
          "currency": "GBP",
          "language": "en-GB"
        },
        "personality": {
          "personality_type": "ENTP-A",
          "trait_percentages": {}
        }
      }
    }
  }
]
```

## Example JavaScript Code

Save this as `workflow_1_prepare_prompt.js`:

```javascript
// Collect all data from the workflow
var webhookData = $('Webhook Trigger').first().json.body || {};

// Get static reference documents
var referenceData = $('Load Reference Documents').first().json;
var frameworkDoc = referenceData.framework_taxonomy_doc;
var questionDoc = referenceData.question_bank_doc;

// Build the prompt
var system = "You are an AI assistant that helps create optimized prompts.";

var messages = [
  {
    role: "user",
    content: `Task: ${webhookData.task_description}`
  }
];

// IMPORTANT: Use 'var' not 'const' or 'let'
// Do NOT use 'return' - variables are automatically captured
```

**Important:**
- Always use `var` for variable declarations (not `const` or `let`)
- The debug system automatically captures `system` and `messages` variables
- Do not use `return` statements

## Command Reference

### Save Input
```bash
node debug-workflow.js --workflow 1 --input input.json
```
Saves input to `storage/app/debug/workflow_1_input.json`

### Save JavaScript
```bash
node debug-workflow.js --workflow 1 --javascript code.js
```
Saves code to `storage/app/debug/workflow_1_prepare_prompt.js`

### Execute Workflow
```bash
node debug-workflow.js --workflow 1 --execute
```
Executes the JavaScript with the saved input
Output saved to `storage/app/debug/workflow_1_output.json`

### Show Status
```bash
node debug-workflow.js --workflow 1 --show
```
Displays which debug files exist for the workflow

### Multiple Workflows
Use `--workflow 0`, `--workflow 2`, etc. to work with different workflow stages:
- Workflow 0: Pre-analysis
- Workflow 1: Framework analysis
- Workflow 2: Prompt generation

## File Structure

```
storage/app/debug/
├── workflow_0_input.json           # Pre-analysis input
├── workflow_0_prepare_prompt.js    # Pre-analysis JavaScript
├── workflow_0_output.json          # Pre-analysis output
├── workflow_1_input.json           # Framework analysis input
├── workflow_1_prepare_prompt.js    # Framework analysis JavaScript
├── workflow_1_output.json          # Framework analysis output
├── workflow_2_input.json           # Prompt generation input
├── workflow_2_prepare_prompt.js    # Prompt generation JavaScript
└── workflow_2_output.json          # Prompt generation output
```

## API Endpoints

### GET /workflow_{number}
Display the debug interface for a workflow

```
GET /workflow_1
GET /workflow_2
```

### POST /api/debug/workflow_{number}/input
Save webhook input data

```bash
curl -X POST https://app.localhost/api/debug/workflow_1/input \
  -H "Content-Type: application/json" \
  -d @input.json
```

### POST /api/debug/workflow_{number}/javascript
Save JavaScript code

```bash
curl -X POST https://app.localhost/api/debug/workflow_1/javascript \
  -H "Content-Type: application/json" \
  -d '{"code": "..."}'
```

### POST /api/debug/workflow_{number}/execute
Execute the JavaScript and return output

```bash
curl -X POST https://app.localhost/api/debug/workflow_1/execute
```

Response:
```json
{
  "success": true,
  "output": {
    "system": "You are...",
    "messages": [
      { "role": "user", "content": "..." }
    ]
  }
}
```

## Troubleshooting

### "Input file not found"
Make sure the path is correct and the file exists:
```bash
ls -la path/to/input.json
```

### "Failed to parse file"
Ensure the JSON is valid. Check with:
```bash
node -e "console.log(JSON.parse(require('fs').readFileSync('input.json', 'utf8')))"
```

### "Both input and JavaScript code are required"
You need both files loaded before executing. Check status:
```bash
node debug-workflow.js --workflow 1 --show
```

### "Execution failed"
Check the browser console and terminal for errors. Make sure:
1. Node.js is installed: `node --version`
2. JavaScript syntax is correct
3. Mock objects ($) are used correctly

### "Output file not found in web interface"
Execute the workflow from the CLI or button to generate output:
```bash
node debug-workflow.js --workflow 1 --execute
```

## Development Notes

The debug system simulates the n8n workflow environment by providing:
- `$` - Mock object for accessing workflow data
- `$('Webhook Trigger').first().json` - Webhook input data
- `$('Load Reference Documents').first().json` - Reference documents

The JavaScript is executed in Node.js, so it has access to:
- All standard JavaScript features
- Node.js built-in objects (though these are usually not needed)
- The mock n8n environment

Output is captured and saved to JSON files, making it easy to inspect the results.
