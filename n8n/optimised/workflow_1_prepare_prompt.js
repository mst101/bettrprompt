// Collect workflow data
const webhookData = $('Webhook Trigger').first().json.body || {};
const referenceData = $('Load Reference Documents').first().json;

// Get reference documents
const frameworkDoc = referenceData.framework_taxonomy;
const questionDoc = referenceData.question_bank;

// Personality calibration only if personality data exists
let personalityDoc = referenceData.personality_calibration;
if (!webhookData.user_context?.personality?.personality_type) {
    personalityDoc = { content: 'Not applicable - no personality data' };
}

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
              '\n\nUse this context for framework/question recommendations.'
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

// Check for forced framework
const forcedFrameworkCode = webhookData.forced_framework_code || null;
const frameworkInstructions = forcedFrameworkCode
    ? `\n## FORCED FRAMEWORK: ${forcedFrameworkCode}\nYou MUST use ${forcedFrameworkCode} as selected_framework. Generate questions specific to it. Do NOT select a different framework.\n`
    : '\n## Select the most appropriate framework from the taxonomy.\n';

// Build system prompt
const systemPrompt = `You analyze requests, classify tasks, identify cognitive requirements, select frameworks, perform Task-Trait Alignment, and generate questions. Return JSON only.

## FRAMEWORK TAXONOMY
${frameworkDoc.content || 'Not available'}

## PERSONALITY CALIBRATION
${personalityDoc.content || 'Not available'}

## QUESTION BANK
${questionDoc.content || 'Not available'}

---

## TASK
1. Classify task (primary/secondary category from taxonomy)
   - If pre-analysis context provided: use task + context for classification
2. Identify cognitive requirements
${frameworkInstructions}
4. Perform Task-Trait Alignment (if personality data):
   - AMPLIFY aligned traits (leverage strengths)
   - COUNTERBALANCE opposing traits (inject requirements)
   - NEUTRAL for unrelated traits
5. Exclude questions based on pre-analysis context:
   - Skip "what type" if subject known
   - Skip "why" if purpose known
   - Skip "how detailed" if detail level known
6. Generate tailored questions${forcedFrameworkCode ? ' for ' + forcedFrameworkCode : ''}
7. Apply personality calibration to phrasing/quantity

## OUTPUT FORMAT
Return ONLY valid JSON (no markdown, no extra text):

\`\`\`json
{
  "task_classification": {
    "primary_category": "CODE",
    "secondary_category": null,
    "complexity": "simple|moderate|complex",
    "classification_reasoning": "Brief explanation",
    "content_type": "For CREATION_CONTENT only"
  },
  "cognitive_requirements": {
    "primary": ["REQ_CODE"],
    "secondary": ["REQ_CODE"],
    "reasoning": "Why these apply"
  },
  "selected_framework": {
    "name": "Name",
    "code": "FRAMEWORK_CODE",
    "components": ["C1", "C2"],
    "rationale": "Why best for task"
  },
  "alternative_frameworks": [{"name": "Alt", "code": "CODE", "when_to_use_instead": "Condition"}],
  "personality_tier": "full|partial|none",
  "task_trait_alignment": {
    "NOTE": "ONLY include user's actual traits, not hypothetical",
    "amplified": [{"trait": "Actual trait from user data", "requirement_aligned": "CODE", "reason": "Why helps"}],
    "counterbalanced": [{"trait": "Actual trait", "requirement_opposed": "CODE", "reason": "Why blind spot", "injection": "Text to add"}],
    "neutral": [{"trait": "Actual trait", "reason": "Why irrelevant"}]
  },
  "personality_adjustments_preview": ["AMPLIFIED: desc", "COUNTERBALANCED: desc"],
  "clarifying_questions": [{"id": "Q1", "question": "Text", "purpose": "Why matters", "required": true}],
  "question_rationale": "Brief explanation"
}
\`\`\``;

// Build personality info
const hasPersonality =
    !!webhookData.user_context?.personality?.personality_type;
let personalityInfo =
    'No personality data. Skip Task-Trait Alignment, use neutral defaults.';

if (hasPersonality) {
    const p = webhookData.user_context.personality;
    personalityInfo = `Type: ${p.personality_type}`;

    if (p.trait_percentages) {
        const typeLetters = p.personality_type.split('-');
        const mainType = typeLetters[0];
        const identity = typeLetters[1];
        const t = p.trait_percentages;
        const traits = [];

        if (t.mind !== undefined) traits.push(`${mainType[0]} (${t.mind}%)`);
        if (t.energy !== undefined)
            traits.push(`${mainType[1]} (${t.energy}%)`);
        if (t.nature !== undefined)
            traits.push(`${mainType[2]} (${t.nature}%)`);
        if (t.tactics !== undefined)
            traits.push(`${mainType[3]} (${t.tactics}%)`);
        if (t.identity !== undefined)
            traits.push(`${identity} (${t.identity}%)`);

        personalityInfo += `\nTraits: ${traits.join(', ')}\nAnalyze ONLY these traits. For each: AMPLIFIED, COUNTERBALANCED, or NEUTRAL.`;
    } else {
        personalityInfo +=
            '\nPercentages: Not provided (use 65% default)\nPerform Task-Trait Alignment with defaults.';
    }
}

// Build pre-analysis section
const preAnalysisContext = webhookData.pre_analysis_context || null;
let preAnalysisSection = '';

if (preAnalysisContext && Object.keys(preAnalysisContext).length > 0) {
    preAnalysisSection =
        '\n\n**Pre-Analysis Context:**\nContext from pre-analysis:';

    for (const [key, item] of Object.entries(preAnalysisContext)) {
        if (item?.question) {
            preAnalysisSection += `\n\n**${item.question}**\n${item.answer_label || item.answer || ''}`;
        }
    }

    preAnalysisSection +=
        '\n\n**Avoid Duplicate Questions:**\n- DO NOT ask about information already provided\n- Generate ONLY new questions not covered above';
    preAnalysisSection +=
        '\n\n**For Task-Trait Alignment:**\n- If audience="self" → Full personality optimization\n- If audience=external → Still apply but note "creating for others"';
}

// Build user message
let userMessage = `Task: ${webhookData.task_description || 'No task'}${preAnalysisSection}\n\n**Personality:** ${personalityInfo}`;

if (forcedFrameworkCode) {
    userMessage += `\n\n**FORCED FRAMEWORK:** ${forcedFrameworkCode}\nMUST use this. Generate specific questions for it.${userContextString}`;
}

return [
    {
        json: {
            system: systemPrompt,
            messages: [{ role: 'user', content: userMessage }],
        },
    },
];
