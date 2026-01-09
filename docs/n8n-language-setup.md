# n8n Workflow Language Setup

This guide explains how to update the three n8n workflows to use the user's language preference for Claude responses.

## Overview

The Laravel backend now passes `user_context.location.language` to all three n8n workflows. This field contains the user's chosen language (from the LanguageSwitcher) and should override the geolocation-detected language.

## Language Values

The backend sends language codes based on the supported locales. Examples:
- `en-US` → "American English"
- `en-GB` → "British English"
- `fr` → "French"
- `de` → "German"
- `es` → "Spanish"

## Updating Workflow 0 (Pre-Analysis)

1. Open the n8n workflow: `workflow_0` (or the one with ID from `N8N_WORKFLOW_0_ID`)
2. Find the Claude API node in the workflow
3. In the Claude system prompt, add:
   ```
   Respond in the user's language: {{$json.user_context.location.language}}.

   All output including error messages must be in {{$json.user_context.location.language}}.
   ```
4. Test with a French user (visit `/fr/prompt-builder`)
5. Verify the response comes back in French

## Updating Workflow 1 (Analysis)

1. Open the n8n workflow: `workflow_1` (or the one with ID from `N8N_WORKFLOW_1_ID`)
2. Find the Claude API node(s) in the workflow
3. Update the system prompt(s) to include:
   ```
   Respond in the user's language: {{$json.user_context.location.language}}.

   All output including error messages must be in {{$json.user_context.location.language}}.
   ```
4. If there are multiple Claude nodes, update all of them
5. Test with a German user (visit `/de/prompt-builder`)
6. Verify the response comes back in German

## Updating Workflow 2 (Generation)

1. Open the n8n workflow: `workflow_2` (or the one with ID from `N8N_WORKFLOW_2_ID`)
2. Find the Claude API node(s) in the workflow
3. Update the system prompt(s) to include:
   ```
   Respond in the user's language: {{$json.user_context.location.language}}.

   All output including error messages must be in {{$json.user_context.location.language}}.
   ```
4. If there are multiple Claude nodes, update all of them
5. Test with a Spanish user (visit `/es/prompt-builder`)
6. Verify the prompt is generated in Spanish

## Important Notes

- **Payload Location**: The language code is at `user_context.location.language` in the webhook payload
- **Full Language Names**: The language code is a locale code (e.g., `fr`, `en-US`). Claude will understand these well
- **Error Messages**: Make sure error messages are also generated in the specified language
- **Testing**: Always test with at least one non-English language to verify it works

## Debugging

If responses still come in English:

1. Check the n8n webhook logs to verify `user_context.location.language` is present
2. Verify the Claude prompt includes the language instruction
3. Check if the user's `language_code` in the database was updated (test by checking the visitor/user record after language switch)
4. Confirm the language switch API call succeeded (check browser console)

## Example Workflow Node Update

**Before:**
```
You are an AI assistant helping users optimize their prompts.
Generate insightful questions...
```

**After:**
```
You are an AI assistant helping users optimize their prompts.

Respond in the user's language: {{$json.user_context.location.language}}.
All output including error messages must be in {{$json.user_context.location.language}}.

Generate insightful questions...
```
