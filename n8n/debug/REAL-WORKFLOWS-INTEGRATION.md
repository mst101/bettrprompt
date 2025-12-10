# Real n8n Workflows Integration

The debug system now **loads and displays your real n8n workflow files** from the `n8n/` directory.

## What's Working

✓ **File Detection**: System automatically finds `n8n/workflow_X_analysis.json` and `n8n/workflow_X_input.json`

✓ **Code Extraction**: Extracts the "Prepare Prompt" JavaScript code from real n8n workflows

✓ **Input Loading**: Uses actual test input data from your workflow files

✓ **Code Display**: Shows extracted code in the web interface for inspection

✓ **Input Display**: Shows workflow input data in the web interface

## Viewing Your Workflows

Visit the debug interface to **see your real workflow code and input**:

```
https://app.localhost/workflow/1
```

**Left Column**: Your actual workflow input from `n8n/workflow_1_input.json`
**Middle Column**: Extracted JavaScript from the Prepare Prompt node
**Right Column**: Output (empty until you execute code)

## Execution Limitation

The real n8n workflow JavaScript code is very complex and depends on:
- Reference documents from n8n
- Helper functions that require full n8n context
- Complex conditional logic and string operations

**Direct execution via `eval()` has limitations** because:
- The code references variables that need the full n8n context
- Complex nested structures that need proper scoping
- Escaped characters that are handled differently in eval()

### This is Normal and Expected

The real n8n workflows are designed to run **inside n8n**, not in a simplified test environment. Using the debug system to **view and understand the code** is valuable, but **execution via eval() is a simplified test mode**, not a replacement for actual n8n execution.

## Recommended Workflow

### For Prototyping New Code
```
1. Visit https://app.localhost/workflow/1
2. See your real workflow input and code
3. Create simplified test code in the middle column
4. Use var system = "..." and var messages = [...]
5. Click Execute to test your custom logic
```

### For Running Production Workflows
```
1. Keep workflows in n8n/
2. Run them directly in your n8n instance
3. Use the debug system to view/understand the code
4. Use the debug system to prototype improvements
```

### For Hybrid Development
```
1. View real workflow code in debug interface
2. Prototype improvements as simplified code
3. Test in debug system to verify logic
4. Copy working code back to n8n when ready
```

## What You Can Do Now

### View Your Workflows
- See the exact JavaScript code in your workflows
- Understand how systemPrompt and userMessage are built
- Review your test input data

### Test Custom Code
- Create simplified versions of your workflow logic
- Test and refine custom JavaScript
- Iterate quickly without re-deploying to n8n

### Copy Back to n8n
- Take working code from debug system
- Paste into n8n workflow nodes
- Deploy to production

## Example: Simple Test Code

Instead of trying to execute the full 16KB n8n workflow code, create simpler test code:

```javascript
var webhookData = $('Webhook Trigger').first().json.body || {};

var system = "You are analyzing a task.";

var messages = [
  {
    role: "user",
    content: "Task: " + (webhookData.task_description || "Not provided")
  }
];
```

This simple code will execute and work correctly in the debug system.

## For Complex Workflows

If you want to test the real n8n JavaScript, the proper way is:
1. Run it inside n8n (it works perfectly there)
2. Use n8n's debugging tools
3. View test results in n8n UI

The debug system is designed for **inspection and prototyping**, not as a full replacement for n8n execution.

## File Locations

Your real workflow files:
- `n8n/workflow_0_pre_analysis.json`
- `n8n/workflow_1_analysis.json`
- `n8n/workflow_2_generation.json`

Your input test data:
- `n8n/workflow_1_input.json` (contains actual test data)
- `n8n/workflow_1_input_1.json`
- `n8n/workflow_1_input_2.json`

Debug system storage:
- `storage/app/debug/workflow_X_input.json`
- `storage/app/debug/workflow_X_prepare_prompt.js`
- `storage/app/debug/workflow_X_output.json`

## Summary

The debug system now:
- ✓ Loads and displays your real n8n workflow code
- ✓ Shows your real test input data
- ✓ Allows you to view and understand your workflows
- ✓ Lets you prototype custom code and test it quickly
- ✓ Automatically extracts code from n8n workflow files

What it doesn't do:
- ✗ Run the full complex n8n workflows via eval() (by design)
- ✗ Replace n8n execution (not intended)
- ✗ Provide all the n8n runtime context

For production use, keep running your workflows in n8n. Use the debug system for inspection, learning, and prototyping.
