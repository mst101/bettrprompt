# n8n Workflow Debug Program - Setup Complete ✓

Your debug program is ready to use! Here's what has been installed:

## Files Created

### Core System Files

- **Controller**: `app/Http/Controllers/DebugN8nController.php`
- **Routes**: Updated `routes/web.php` with debug routes
- **Vue Component**: `resources/js/Pages/Debug/WorkflowDebug.vue`
- **CLI Tool**: `debug-workflow.js`

### Documentation

- **Quick Start**: `QUICKSTART-DEBUG.md`
- **Full Documentation**: `DEBUG.md`
- **Examples**: `examples/workflow_1_input_example.json` + `workflow_1_prepare_prompt_example.js`
- **This File**: `SETUP-COMPLETE.md`

### Storage

- **Directory**: `storage/app/debug/` (automatically created and ready)

## Key Features

✅ **Web Interface** (`https://app.localhost/workflow/{number}`)

- Load input files (JSON)
- Edit JavaScript code
- View formatted output
- Save/load progress automatically

✅ **Command Line Tool** (`node debug-workflow.js`)

- Save input from files
- Save JavaScript from files
- Execute workflows
- Check status

✅ **Full Mock n8n Environment**

- Mock `$` object for accessing workflow data
- Mock `Webhook Trigger` node data
- Mock `Load Reference Documents` node data
- JavaScript execution with Node.js

## Quick Start (30 seconds)

```bash
# 1. Start development server
composer dev

# 2. Visit the debug interface in your browser
# https://app.localhost/workflow/1

# 3. Load example files and execute
```

Or use the CLI:

```bash
# Save input
node debug-workflow.js --workflow 1 --input debug/workflow_1_input_example.json

# Save JavaScript
node debug-workflow.js --workflow 1 --javascript debug/workflow_1_prepare_prompt_example.js

# Execute
node debug-workflow.js --workflow 1 --execute
```

## Available Routes

### Page Routes (View Debug Interface)

- `GET /workflow/0` - Pre-analysis workflow
- `GET /workflow/1` - Framework analysis workflow
- `GET /workflow/2` - Prompt generation workflow

### API Routes (Backend Operations)

- `POST /api/debug/workflow/{number}/input` - Save webhook input
- `POST /api/debug/workflow/{number}/javascript` - Save JavaScript code
- `POST /api/debug/workflow/{number}/execute` - Execute workflow

## File Storage

All debug files are stored in: `storage/app/debug/`

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

## Example Usage

### 1. Web Interface

```
1. Go to: https://app.localhost/workflow/1
2. Click "Load Input File" → Select examples/workflow_1_input_example.json
3. Click "Load JavaScript" → Select examples/workflow_1_prepare_prompt_example.js
4. Click "Execute"
5. View results in the right column
```

### 2. Command Line

```bash
node debug-workflow.js --workflow 1 --input debug/workflow_1_input_example.json
node debug-workflow.js --workflow 1 --javascript debug/workflow_1_prepare_prompt_example.js
node debug-workflow.js --workflow 1 --execute
```

### 3. Using Your Own Files

```bash
node debug-workflow.js --workflow 1 --input your_input.json
node debug-workflow.js --workflow 1 --javascript your_code.js
node debug-workflow.js --workflow 1 --execute
```

## How It Works

### Input Format

The input should be your webhook payload:

```json
{
    "body": {
        "task_description": "...",
        "pre_analysis_context": {
            ...
        },
        "user_context": {
            ...
        }
    },
    "headers": {
        ...
    }
}
```

### JavaScript Format

Your JavaScript code should use the mock `$` object:

```javascript
const webhookData = $('Webhook Trigger').first().json.body;
const referenceData = $('Load Reference Documents').first().json;

const system = "You are...";
const messages = [{ role: "user", content: "..." }];

return { system, messages };
```

### Output Format

The output contains:

```json
{
    "system": "System prompt text...",
    "messages": [
        {
            "role": "user",
            "content": "User message..."
        },
        {
            "role": "assistant",
            "content": "Assistant response..."
        }
    ]
}
```

## Documentation Files

- **QUICKSTART-DEBUG.md** - 30-second quick start guide
- **DEBUG.md** - Complete reference documentation
- **examples/README.md** - Examples for all workflows
- **examples/workflow_1_input_example.json** - Sample input data
- **examples/workflow_1_prepare_prompt_example.js** - Sample JavaScript code

## Troubleshooting

### Issue: "Cannot find workflow/1" (404 error)

**Solution**: Make sure you've run `composer dev` and visit `https://app.localhost/workflow/1` (note: /workflow/1, not
/workflow_1)

### Issue: "Node.js not found" when executing

**Solution**: Check Node.js is installed: `node --version`

### Issue: Output is empty in web interface

**Solution**: Both input and JavaScript must be loaded. Check the file list with:
`node debug-workflow.js --workflow 1 --show`

### Issue: JavaScript syntax errors

**Solution**: Check the browser console (F12 → Console tab) for detailed error messages

## Next Steps

1. ✓ Review the QUICKSTART-DEBUG.md file
2. ✓ Try the example files to understand the structure
3. ✓ Adapt the examples for your specific workflows
4. ✓ Use the debug program to refine prompts before deploying to n8n
5. ✓ Commit changes to version control

## Integration with Existing Workflows

The debug program works independently and doesn't interfere with your existing n8n setup:

- It reads from `storage/app/debug/` files only
- It doesn't modify any existing database or files
- It can be used alongside running n8n workflows
- Perfect for testing prompt changes before deployment

## Security Notes

⚠️ **Development Only**: This is a development tool. Don't expose the routes in production.

The routes are accessible without authentication by default. If needed, add middleware:

```php
Route::get('/workflow/{workflowNumber}', [...])
    ->middleware('auth'); // Add authentication if needed
```

## Support

For issues or questions:

1. Check DEBUG.md for detailed documentation
2. Check examples/README.md for example usage
3. Review the example files in examples/
4. Check browser console (F12) for JavaScript errors

## What's Next?

Once you're familiar with the debug program:

1. Test your actual n8n workflow inputs
2. Refine the "Prepare Prompt" JavaScript
3. Verify the output formatting
4. Deploy to n8n with confidence

Good luck! 🚀
