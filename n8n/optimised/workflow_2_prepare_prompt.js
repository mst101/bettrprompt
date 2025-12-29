// Collect workflow data
const webhookData = $('Webhook Trigger').first().json.body || {};
const referenceData = $('Load Reference Documents').first().json;

// Get framework template
let frameworkCode = webhookData.analysis_data.selected_framework.code;
let frameworkTemplateContent = referenceData.framework_templates[frameworkCode];

// Try variations if not found
if (!frameworkTemplateContent) {
    const withTaxonomy = frameworkCode + '_TAXONOMY';
    if (referenceData.framework_templates[withTaxonomy]) {
        frameworkTemplateContent =
            referenceData.framework_templates[withTaxonomy];
        frameworkCode = withTaxonomy;
    }
}

// Try case-insensitive partial match
if (!frameworkTemplateContent) {
    const searchCode = frameworkCode.toUpperCase();
    for (const [key, value] of Object.entries(
        referenceData.framework_templates,
    )) {
        if (key.toUpperCase().startsWith(searchCode)) {
            frameworkTemplateContent = value;
            frameworkCode = key;
            console.log(
                `Framework fuzzy-matched: ${webhookData.analysis_data.selected_framework.code} → ${key}`,
            );
            break;
        }
    }
}

if (!frameworkTemplateContent) {
    throw new Error(
        `Framework not found: ${webhookData.analysis_data.selected_framework.code}. Available: ${Object.keys(referenceData.framework_templates).sort().join(', ')}`,
    );
}

const frameworkTemplateDoc = {
    success: true,
    content: frameworkTemplateContent,
    framework_code: frameworkCode,
};

const personalityDoc = referenceData.personality_calibration;
const analysisData = webhookData.analysis_data || {};
const questionAnswers = webhookData.question_answers || [];
const preAnalysisContext = webhookData.pre_analysis_context || null;
const userContext = webhookData.user_context || null;

// Helper: Build user context
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
              '\n\nUse context when optimizing (local currency, tools, complexity level).'
        : '';
}

// Helper: Language preference
function getLanguageInstructions(ctx) {
    const lang = ctx?.location?.language || 'en-GB';
    if (lang.startsWith('en-US'))
        return '\n\n# Language: American English\nUse American spelling/format.';
    if (lang.startsWith('en-'))
        return '\n\n# Language: British English\nUse British spelling/format.';
    return `\n\n# Language: ${lang}\nAdapt for this locale.`;
}

const userContextString = buildUserContext(userContext);
const languageInstructions = getLanguageInstructions(userContext);

// Build system prompt
const systemPrompt = `You construct optimized prompts using selected frameworks and Task-Trait Alignment. Return JSON only.

## FRAMEWORK TEMPLATE
${frameworkTemplateDoc.content || 'Not available'}

## PERSONALITY CALIBRATION
${personalityDoc.content || 'Not available'}

---

## TASK
1. Use framework template
2. Apply amplification (leverage strengths) and counterbalancing (inject requirements)
3. Incorporate user answers
4. Generate model recommendations

## COUNTERBALANCING
When specified: add explicit requirements in prompt, include in quality criteria.

## OUTPUT FORMAT
Return valid JSON only (no markdown, no extra text). Escape newlines as \\n in strings.

{
  "optimised_prompt": "Complete prompt text, ready for copy/paste. Fully self-contained with all context, requirements, instructions. Usable immediately without additional information.",

  "metadata": {
    "framework_used": {
      "name": "Name",
      "code": "CODE",
      "components": ["C1", "C2"],
      "explanation": "How applied"
    },

    "task_trait_alignment": {
      "amplified": [{"trait": "High N (64%)", "requirement_aligned": "VISION", "how_applied": "How leveraged in prompt"}],
      "counterbalanced": [{"trait": "High T (84%)", "requirement_opposed": "EMPATHY", "reason": "Why needed", "injections_added": ["Specific text added"]}],
      "neutral": [{"trait": "High I (65%)", "reason": "Why no adjustment"}]
    },

    "personality_adjustments_summary": ["AMPLIFIED: desc", "COUNTERBALANCED: desc"],

    "model_recommendations": [
      {"rank": 1, "model": "Name", "model_id": "id", "rationale": "Why recommended, considering counterbalance complexity"},
      {"rank": 2, "model": "Alt", "model_id": "id", "rationale": "Good alternative"},
      {"rank": 3, "model": "Budget", "model_id": "id", "rationale": "Cost-effective"}
    ],

    "iteration_suggestions": ["Refinement suggestion", "Potential adjustment"]
  }
}`;

// Build user message
let userMessage = `## ANALYSIS FROM WORKFLOW 1\n\n`;
userMessage += `Task: ${analysisData.task_classification?.primary_category} (${analysisData.task_classification?.complexity})\n`;
userMessage += `Framework: ${analysisData.selected_framework?.name}\n`;
userMessage += `Personality: ${analysisData.personality_tier || 'none'}\n\n`;

if (analysisData.task_trait_alignment) {
    userMessage += `**Task-Trait Alignment:**\n${JSON.stringify(analysisData.task_trait_alignment, null, 2)}\n\n`;
    userMessage += `Apply amplifications and counterbalancing as specified. For counterbalanced traits, include injection text explicitly in prompt.\n\n`;
}

userMessage += `---\n\n## ORIGINAL TASK\n\n${webhookData.original_task_description || 'No task'}\n\n`;

if (webhookData.user_context?.personality?.personality_type) {
    userMessage += `## PERSONALITY\n\nType: ${webhookData.user_context.personality.personality_type}\n`;
    if (webhookData.user_context.personality.trait_percentages) {
        userMessage += `Percentages: ${JSON.stringify(webhookData.user_context.personality.trait_percentages)}\n`;
    }
    userMessage += '\n';
}

if (preAnalysisContext && Object.keys(preAnalysisContext).length > 0) {
    userMessage += `## PRE-ANALYSIS CONTEXT\n\n${JSON.stringify(preAnalysisContext, null, 2)}\n\n`;
}

userMessage += `## USER ANSWERS\n\n`;
if (questionAnswers?.length > 0) {
    questionAnswers.forEach((qa, i) => {
        userMessage += `**Q${i + 1}: ${qa.question}**\nA: ${qa.answer}\n\n`;
    });
} else {
    userMessage += `No answers provided.\n\n`;
}

userMessage += `---\n\nConstruct optimized prompt:\n`;
userMessage += `1. Use ${analysisData.selected_framework?.name || 'selected'} framework\n`;
userMessage += `2. Incorporate all answers naturally\n`;
userMessage += `3. Apply AMPLIFICATION (leverage strengths)\n`;
userMessage += `4. Apply COUNTERBALANCING (inject explicit requirements)\n`;
userMessage += `5. Make self-contained and immediately usable\n`;
userMessage += `6. Recommend models considering counterbalance complexity${userContextString}`;

return [
    {
        json: {
            system: systemPrompt,
            messages: [{ role: 'user', content: userMessage }],
        },
    },
];
