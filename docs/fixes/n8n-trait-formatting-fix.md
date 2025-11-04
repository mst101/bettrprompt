# Fix: n8n Trait Percentages Formatting

## Problem

The "Call LLM API" node was failing with "Bad request - please check your parameters" because the `user_message` had poorly formatted trait percentages.

### Before (Ugly Format)
```
Personality Type: INTP-A
Trait Percentages: {"mind":65,"energy":64,"nature":84,"tactics":57,"identity":84}

Task Description:
I want to build a work-from-home office.

Create an optimised AI prompt for this task that is tailored to this personality type.
```

**Issues:**
- Trait percentages as raw JSON with escaped quotes
- No spaces, hard to read
- Not human-friendly for LLM processing

## Solution

Format trait percentages in a human-readable format with proper labels.

### After (Clean Format)
```
Personality Type: INTP-A

Trait Percentages:
  - Mind (Introversion/Extraversion): 65%
  - Energy (Intuitive/Observant): 64%
  - Nature (Thinking/Feeling): 84%
  - Tactics (Judging/Prospecting): 57%
  - Identity (Assertive/Turbulent): 84%

Task Description:
I want to build a work-from-home office.

Create an optimised AI prompt for this task that is tailored to this personality type.
```

**Benefits:**
- ✅ Human-readable format
- ✅ Descriptive labels for each trait
- ✅ Clean percentage formatting
- ✅ Better for LLM understanding

## How to Update Your n8n Workflow

### Step 1: Open Your Workflow

1. Go to https://n8n.localhost
2. Open your "Prompt Optimiser" workflow
3. Click on the **"Validate Input"** code node

### Step 2: Replace the Code

Find this section (around line 88-95):

**OLD CODE:**
```javascript
// Build the user message
const userMessage = `Personality Type: ${personalityType}
${Object.keys(traitPercentages).length > 0 ? `Trait Percentages: ${JSON.stringify(traitPercentages)}` : ''}

Task Description:
${taskDescription}

Create an optimised AI prompt for this task that is tailored to this personality type.`;
```

**NEW CODE:**
```javascript
// Build the user message with formatted trait percentages
let traitInfo = '';
if (Object.keys(traitPercentages).length > 0) {
  const traitLabels = {
    mind: 'Mind (Introversion/Extraversion)',
    energy: 'Energy (Intuitive/Observant)',
    nature: 'Nature (Thinking/Feeling)',
    tactics: 'Tactics (Judging/Prospecting)',
    identity: 'Identity (Assertive/Turbulent)'
  };

  const formattedTraits = Object.entries(traitPercentages)
    .map(([key, value]) => {
      const label = traitLabels[key as keyof typeof traitLabels] || key;
      return `  - ${label}: ${value}%`;
    })
    .join('\n');

  traitInfo = `\nTrait Percentages:\n${formattedTraits}\n`;
}

const userMessage = `Personality Type: ${personalityType}${traitInfo}
Task Description:
${taskDescription}

Create an optimised AI prompt for this task that is tailored to this personality type.`;
```

### Step 3: Save and Test

1. Click **Save** in n8n
2. Test the workflow by submitting a new prompt optimization request
3. Verify the LLM API call succeeds

## Complete Updated Code Node

Here's the full "Validate Input" code node with the fix:

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

// Build the system prompt
const systemPrompt = `You are an expert at crafting AI prompts tailored to personality types based on the 16personalities.com framework.

Given a personality type and a task description, create an optimised prompt that:
1. Takes into account the personality traits and communication style
2. Uses appropriate prompt frameworks (SMART, RICE, COAST, etc.)
3. Includes relevant context and constraints
4. Specifies desired output format
5. Is clear, specific, and actionable

Return ONLY the optimised prompt text, without any preamble or explanation.`;

// Build the user message with formatted trait percentages
let traitInfo = '';
if (Object.keys(traitPercentages).length > 0) {
  const traitLabels = {
    mind: 'Mind (Introversion/Extraversion)',
    energy: 'Energy (Intuitive/Observant)',
    nature: 'Nature (Thinking/Feeling)',
    tactics: 'Tactics (Judging/Prospecting)',
    identity: 'Identity (Assertive/Turbulent)'
  };

  const formattedTraits = Object.entries(traitPercentages)
    .map(([key, value]) => {
      const label = traitLabels[key as keyof typeof traitLabels] || key;
      return `  - ${label}: ${value}%`;
    })
    .join('\n');

  traitInfo = `\nTrait Percentages:\n${formattedTraits}\n`;
}

const userMessage = `Personality Type: ${personalityType}${traitInfo}
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

## Why This Fixes the "Bad Request" Error

The LLM APIs (Anthropic Claude, OpenAI) expect clean, well-formatted text content. The raw JSON format with escaped quotes can cause parsing issues or be interpreted as malformed input.

The new format:
- Uses proper line breaks and indentation
- Presents data in a natural, readable way
- Avoids special characters that might need escaping
- Makes it easier for the LLM to understand the context

## TypeScript Fix

**Issue:** You may see this TypeScript error in n8n:
```
Element implicitly has an 'any' type because expression of type 'string'
can't be used to index type '{ mind: string; energy: string; ... }'.
```

**Cause:** `Object.entries()` returns keys typed as `string`, but TypeScript needs to know they're specifically the keys from `traitLabels`.

**Solution:** Use a type assertion:
```javascript
const label = traitLabels[key as keyof typeof traitLabels] || key;
```

The `as keyof typeof traitLabels` tells TypeScript that `key` is one of the valid keys (`'mind'`, `'energy'`, `'nature'`, `'tactics'`, or `'identity'`).

## Testing

After updating, test with a request that includes trait percentages:

```bash
curl -X POST https://n8n.localhost/webhook/prompt-optimizer \
  -H "Content-Type: application/json" \
  -k \
  -d '{
    "prompt_run_id": 1,
    "personality_type": "INTP-A",
    "trait_percentages": {
      "mind": 65,
      "energy": 64,
      "nature": 84,
      "tactics": 57,
      "identity": 84
    },
    "task_description": "I want to build a work-from-home office."
  }'
```

You should now receive a successful response with an optimised prompt!

## Files Updated

- ✅ `docs/n8n-prompt-optimizer-setup.md` - Updated documentation with correct code
- ✅ `docs/fixes/n8n-trait-formatting-fix.md` - This fix documentation

Date Fixed: 2025-11-04
