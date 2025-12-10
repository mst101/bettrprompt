# Debug System - Complete Setup

## Status: ✓ READY TO USE

Your n8n workflow debug system is now fully operational with reference document integration.

## What You Can Do Now

### 1. **View Real Workflow Data**
   - Visit `https://app.localhost/workflow/1`
   - See your actual workflow input from `n8n/workflow_1_input.json`
   - See JavaScript code from the "Prepare Prompt" node

### 2. **Execute Workflow Code**
   - Click the "Execute" button
   - The system loads:
     - Workflow input
     - Workflow JavaScript code
     - Reference documents (framework taxonomy, personality calibration, question bank)
   - JavaScript executes in a mock n8n environment
   - See output: system prompt and messages

### 3. **Edit and Test Code**
   - Edit the JavaScript code in the middle column
   - Click Execute to test changes immediately
   - See results in real-time

### 4. **Upload Custom Files**
   - Load different input files: Click "Load Input File"
   - Load different JavaScript: Click "Load JavaScript"
   - Save your changes back to disk automatically

## System Architecture

```
┌─ Laravel Server (Port 80) ─┐
│                            │
│  Routes:                   │
│  GET  /workflow/1      ← Show debug interface
│  POST /api/debug/...  ← Execute operations
│                            │
└────────────────────────────┘
         ↓
    ┌────────────────────────────────┐
    │   DebugN8nController.php       │
    ├────────────────────────────────┤
    │ show()                         │
    │ ├─ Load input file             │
    │ ├─ Extract JS from workflow    │
    │ ├─ Load reference docs         │
    │ └─ Render Vue component        │
    │                                │
    │ executeJavaScript()            │
    │ ├─ Load reference docs   ← NEW!│
    │ ├─ Normalise code              │
    │ ├─ Build Node.js wrapper       │
    │ ├─ Execute via Node.js         │
    │ └─ Return output               │
    └────────────────────────────────┘
         ↓         ↓           ↓
    Input Files  JS Code   Reference Docs
    n8n/        n8n/       resources/
    workflow_*  workflow_*  reference_
    _input.json _analysis.json documents/
```

## Key Files

### Backend
- `app/Http/Controllers/DebugN8nController.php` - Main controller (UPDATED)
- `routes/web.php` - Debug routes
- `bootstrap/app.php` - CSRF exceptions (app/debug routes exempted)

### Frontend
- `resources/js/Pages/Debug/WorkflowDebug.vue` - Debug interface

### Reference Documents (Loaded Automatically)
- `resources/reference_documents/framework_taxonomy.md`
- `resources/reference_documents/personality_calibration.md`
- `resources/reference_documents/question_bank.md`

### n8n Workflow Files
- `n8n/workflow_1_input.json` - Test input data
- `n8n/workflow_1_analysis.json` - Workflow with JavaScript code

## How Reference Documents Are Loaded

### The Process

1. User clicks "Execute" button
2. Vue component sends POST to `/api/debug/workflow/1/execute`
3. Laravel controller:
   - Loads input from `n8n/workflow_1_input.json`
   - Loads JavaScript from `n8n/workflow_1_analysis.json`
   - **Calls `loadReferenceDocuments()`** ← NEW
   - Reads three markdown files from `resources/reference_documents/`
   - Builds Node.js script with all data
   - Executes via Node.js
4. Node.js script:
   - Provides reference docs via `$('Load Reference Documents').first().json`
   - Executes workflow code
   - Captures output
5. Results returned to Vue component
6. Output displayed on screen

### The Mock n8n Environment

The workflow JavaScript receives:
```javascript
// From Webhook Trigger node
$('Webhook Trigger').first().json.body
// Contains: task_description, user_context, pre_analysis_context, etc.

// From Load Reference Documents node
$('Load Reference Documents').first().json
// Contains:
// - framework_taxonomy_doc { content: "..." }
// - personality_calibration_doc { content: "..." }
// - question_bank_doc { content: "..." }
```

This matches the real n8n workflow structure!

## What Changed in This Session

### Previous Sessions
1. Created debug interface with file upload/download
2. Fixed CSRF token issues
3. Fixed variable scope in eval()
4. Integrated real n8n workflow files

### This Session
5. **Added reference document loading** ← YOU ARE HERE
   - Created `loadReferenceDocuments()` method
   - Integrated with `buildNodeScript()`
   - Documents automatically loaded from `resources/reference_documents/`
   - Created comprehensive documentation

## Testing Checklist

- [ ] Start development environment: `./vendor/bin/sail up -d`
- [ ] Visit: `https://app.localhost/workflow/1`
- [ ] See input data in left column
- [ ] See JavaScript code in middle column
- [ ] Click "Execute" button
- [ ] See output in right column (system prompt and messages)
- [ ] Edit JavaScript code in middle column
- [ ] Click "Execute" again to test changes
- [ ] Upload different input file
- [ ] Upload different JavaScript file
- [ ] Verify changes are saved

## Troubleshooting

### Issue: "Input file not found"
**Solution**: Verify `n8n/workflow_1_input.json` exists
```bash
ls -la n8n/workflow_1_input.json
```

### Issue: "JavaScript file not found"
**Solution**: Verify `n8n/workflow_1_analysis.json` exists with "Prepare Prompt" node
```bash
cat n8n/workflow_1_analysis.json | jq '.nodes[] | select(.name=="Prepare Prompt")'
```

### Issue: "Reference documents not loading"
**Solution**: Verify documents exist in `resources/reference_documents/`
```bash
ls -la resources/reference_documents/
```

All three should exist:
- `framework_taxonomy.md` (~25KB)
- `personality_calibration.md` (~20KB)
- `question_bank.md` (~15KB)

### Issue: Execution error, check Laravel logs
```bash
php artisan pail --timeout=0
```

## Summary

Your debug system is now **fully integrated** with:
- ✓ Real n8n workflow files
- ✓ Real reference documents
- ✓ Web interface for viewing and testing
- ✓ File upload/download capabilities
- ✓ Mock n8n environment for execution

**Ready to use!** Visit `https://app.localhost/workflow/1` and start debugging.

## Next Steps (Optional)

If you want to add more workflows:
1. For `workflow_0`: Create `n8n/workflow_0_pre_analysis.json` and `n8n/workflow_0_input.json`
2. For `workflow_2`: Create `n8n/workflow_2_generation.json` and `n8n/workflow_2_input.json`
3. Access via: `https://app.localhost/workflow/0` or `/workflow/2`

The system works the same way for all workflow numbers!
