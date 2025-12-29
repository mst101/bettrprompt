// Extract input from webhook
const webhookData = $input.first().json.body || {};
const userContext = webhookData.user_context || null;

// Helper: Build user context string
function buildUserContext(ctx) {
    if (!ctx) return '';

    const parts = [];
    const loc = ctx.location;
    const prof = ctx.professional;
    const team = ctx.team;
    const pref = ctx.preferences;

    if (loc?.country) {
        parts.push(
            `Location: ${loc.country}${loc.city ? ` (${loc.city})` : ''}`,
        );
        if (loc.timezone) parts.push(`TZ: ${loc.timezone}`);
        if (loc.currency) parts.push(`Currency: ${loc.currency}`);
        if (loc.language) parts.push(`Lang: ${loc.language}`);
    }

    if (prof?.job_title || prof?.industry) {
        const p = [
            prof.job_title,
            prof.industry && `in ${prof.industry}`,
            prof.experience_level && `(${prof.experience_level})`,
        ].filter(Boolean);
        if (p.length) parts.push(`Professional: ${p.join(' ')}`);
        if (prof.company_size) parts.push(`Company: ${prof.company_size}`);
    }

    if (team?.size || team?.role) {
        const t = [
            team.size && `${team.size} team`,
            team.role && `${team.role} role`,
            team.work_mode && `(${team.work_mode})`,
        ].filter(Boolean);
        if (t.length) parts.push(`Team: ${t.join(', ')}`);
    }

    if (pref?.budget) parts.push(`Budget: ${pref.budget}`);
    if (pref?.primary_programming_language)
        parts.push(`Lang: ${pref.primary_programming_language}`);

    return parts.length
        ? '\n\n# User Context\n- ' +
              parts.join('\n- ') +
              '\n\nTailor response to this context.'
        : '';
}

// Helper: Language preference
function getLanguageInstructions(ctx) {
    const lang = ctx?.location?.language || 'en-GB';
    if (lang.startsWith('en-US'))
        return '\n\n# Language: American English\nUse American spelling/format (e.g., "optimized", MM/DD/YYYY).';
    if (lang.startsWith('en-'))
        return '\n\n# Language: British English\nUse British spelling/format (e.g., "optimised", DD/MM/YYYY).';
    return `\n\n# Language: ${lang}\nAdapt for this locale.`;
}

const userContextString = buildUserContext(userContext);
const languageInstructions = getLanguageInstructions(userContext);

const systemPrompt = `You assess if task descriptions need clarification. Return JSON only.

# Assessment Criteria
Task needs clarification if ambiguous in:
1. Subject/Topic - WHAT the task is about
2. Audience - WHO will use output
3. Purpose - WHY it's created
4. Detail Level - HOW MUCH depth

# Clear Tasks (SKIP questions, INFER context)
- "Create Python CSV-to-JSON converter with error handling"
  → {subject: "Python CSV converter", audience: "self", purpose: "code_implementation", detail_level: "moderate"}
- "Write B2B SaaS launch email, professional, 200 words"
  → {subject: "B2B SaaS launch", audience: "external", purpose: "marketing", detail_level: "summary"}

# Ambiguous Tasks (ASK questions)
- "Help with marketing" - lacks subject, audience, purpose
- "Write a report" - lacks subject/topic
- "Fix my code" - lacks scope, language

# When to ASK vs INFER
ASK if: 1-3 words, missing subject/topic, generic verbs ("help", "create") without details
INFER if: Specific technical requirements, clear subject+deliverable, 10+ meaningful words

# Response Format
**If CLEAR:**
\`\`\`json
{"needs_clarification": false, "pre_analysis_context": {"subject": "...", "audience": "self", "purpose": "...", "detail_level": "moderate"}, "reasoning": "Sufficient context."}
\`\`\`

**If AMBIGUOUS:**
\`\`\`json
{"needs_clarification": true, "questions": [{"id": "subject", "type": "text", "question": "What is the main subject/topic?"}, {"id": "audience", "type": "choice", "question": "Primary audience?", "options": [{"value": "self", "label": "For myself"}, {"value": "team", "label": "My team"}, {"value": "management", "label": "Management"}, {"value": "external", "label": "External"}]}], "reasoning": "Needs clarification on [aspects]."}
\`\`\`

# Rules
- ALWAYS include pre_analysis_context (inferred or null)
- Max 3 questions
- Each question needs type: "choice", "text", or "yes_no"
- "choice"/"yes_no" need options array (2-4 items)
- "text" questions must NOT have options
- Unique IDs (subject, audience, purpose, detail_level)
- Return ONLY JSON${userContextString}${languageInstructions}`;

const userMessage = `Task: ${webhookData.task_description || 'No task'}\n\nAnalyze and determine if clarification needed.`;

return [
    {
        json: {
            system: systemPrompt,
            messages: [{ role: 'user', content: userMessage }],
        },
    },
];
