# Quick Start Guide - Prompt Optimiser MVP

Get the Prompt Optimiser up and running in 5 minutes.

## 1. Start the Services

```bash
# Start Docker containers (PostgreSQL, Redis, n8n)
./vendor/bin/sail up -d

# Start Laravel development stack
composer dev
```

This runs:
- Laravel server: http://localhost
- n8n dashboard: http://localhost:5678
- Queue worker and log viewer

## 2. Set Up n8n Workflow

### Option A: Import Pre-built Workflow (Fastest)

1. Open n8n dashboard: http://localhost:5678
2. Log in: username `admin`, password `password`
3. Click the "..." menu (top right) → Import from File
4. Select `docs/n8n-workflow-template.json`
5. Click on the "Call LLM API" node
6. Add your API credentials:
   - For **Anthropic**: Add Header Auth credential
     - Name: `x-api-key`
     - Value: `your_anthropic_api_key`
   - For **OpenAI**: Update the node configuration (see detailed guide)
7. Click **Save** (top right)
8. Toggle workflow to **Active**

### Option B: Build Manually

Follow the detailed step-by-step guide in `docs/n8n-prompt-optimizer-setup.md`

## 3. Test the Application

1. Navigate to: http://localhost/prompt-optimizer
2. Log in or register an account
3. Fill in the form:
   - **Personality Type**: Select from dropdown (e.g., INTJ-A)
   - **Task Description**: "Write a technical blog post about microservices"
   - Click "Optimise Prompt"
4. View your optimised prompt result

## 4. View History

- Click "View History" to see all your past prompt optimisations
- Click "View" on any entry to see the full optimised prompt

## Available Routes

- `/prompt-optimizer` - Create new optimised prompt
- `/prompt-optimizer/{id}` - View specific result
- `/prompt-optimizer-history` - View all your history

## Troubleshooting

### n8n workflow not responding

```bash
# Check n8n container is running
./vendor/bin/sail ps

# Check n8n logs
./vendor/bin/sail logs n8n
```

### Database errors

```bash
# Run migrations
./vendor/bin/sail artisan migrate
```

### Frontend not loading

```bash
# Rebuild assets
npm run build

# Or run in dev mode with hot reload
npm run dev
```

### LLM API errors

- Check your API key is valid
- Verify you have credits/quota
- Review rate limits for your plan

## Configuration Files

- **Backend routes**: `routes/web.php`
- **Controller**: `app/Http/Controllers/PromptOptimizerController.php`
- **Model**: `app/Models/PromptRun.php`
- **Frontend**: `resources/js/Pages/PromptOptimizer/`
- **n8n config**: `.env` (lines 67-103)

## Next Steps

1. Customise the LLM prompt in the n8n workflow
2. Add more personality type details
3. Implement feedback collection
4. Add prompt templates for common tasks
5. Build analytics dashboard

## Need Help?

See the detailed guides:
- `docs/n8n-prompt-optimizer-setup.md` - Full n8n workflow setup
- `docs/overview/mvp-phase1.md` - Project requirements
- `docs/overview/ai-buddy-overview.md` - Feature overview
