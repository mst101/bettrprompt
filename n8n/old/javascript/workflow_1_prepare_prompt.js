// Collect all data from the workflow
const webhookData = $('Webhook Trigger').first().json.body || {};

// Get static reference documents (no HTTP requests - instant access)
const referenceData = $('Load Reference Documents').first().json;
const frameworkDoc = referenceData.framework_taxonomy;
const questionDoc = referenceData.question_bank;

// Personality calibration is always available, but only used if personality_type exists
let personalityDoc = referenceData.personality_calibration;
if (!webhookData.user_context?.personality?.personality_type) {
    personalityDoc = { content: 'Not applicable - no personality data provided' };
}

// Get user context for workflow optimisation
const userContext = webhookData.user_context || null;

// Determine language preference and build language instructions
let languageCode = userContext?.location?.language || 'en-GB';
let languageInstructions = '';
if (languageCode.startsWith('en-US')) {
  languageInstructions = '\n\n# Language Preference: American English\nRespond using American English conventions: use American spelling (e.g., "optimized" not "optimised"), American date formats (MM/DD/YYYY), and American terminology.';
} else if (languageCode.startsWith('en-')) {
  languageInstructions = '\n\n# Language Preference: British English\nRespond using British English conventions: use British spelling (e.g., "optimised" not "optimized"), ISO date formats (DD/MM/YYYY), and British terminology.';
} else {
  languageInstructions = `\n\n# Language Preference: ${languageCode}\nRespond appropriately for users from this locale, adapting terminology and conventions as needed.`;
}

// Build user context string for user message
let userContextString = '';
if (userContext) {
  const parts = [];

  // Location context
  if (userContext.location?.country) {
    parts.push(`Location: ${userContext.location.country}${userContext.location.city ? ` (${userContext.location.city})` : ''}`);
    if (userContext.location.timezone) parts.push(`Timezone: ${userContext.location.timezone}`);
    if (userContext.location.currency) parts.push(`Currency: ${userContext.location.currency}`);
    if (userContext.location.language) parts.push(`Language: ${userContext.location.language}`);
  }

  // Professional context
  if (userContext.professional?.job_title || userContext.professional?.industry) {
    const prof = userContext.professional;
    const profParts = [];
    if (prof.job_title) profParts.push(prof.job_title);
    if (prof.industry) profParts.push(`in ${prof.industry}`);
    if (prof.experience_level) profParts.push(`(${prof.experience_level} level)`);
    if (profParts.length > 0) parts.push(`Professional: ${profParts.join(' ')}`);
    if (prof.company_size) parts.push(`Company size: ${prof.company_size}`);
  }

  // Team context
  if (userContext.team?.size || userContext.team?.role) {
    const team = userContext.team;
    const teamParts = [];
    if (team.size) teamParts.push(`${team.size} team`);
    if (team.role) teamParts.push(`${team.role} role`);
    if (team.work_mode) teamParts.push(`(${team.work_mode})`);
    if (teamParts.length > 0) parts.push(`Team: ${teamParts.join(', ')}`);
  }

  // Preferences context
  if (userContext.preferences?.budget) {
    parts.push(`Budget preference: ${userContext.preferences.budget}`);
  }
  if (userContext.preferences?.primary_programming_language) {
    parts.push(`Primary language: ${userContext.preferences.primary_programming_language}`);
  }

  if (parts.length > 0) {
    userContextString = '\n\n# User Context\n' + parts.map(p => `- ${p}`).join('\n') + '\n\nUse this context when making framework recommendations and generating questions (e.g., suggest local tools, use appropriate currency, consider technical level).';
  }
}


// Check if a forced framework was provided
const forcedFrameworkCode = webhookData.forced_framework_code || null;
let frameworkInstructions = '';

if (forcedFrameworkCode) {
    frameworkInstructions = `
## FORCED FRAMEWORK SELECTION

**CRITICAL:** The user has selected a specific framework to use: **${forcedFrameworkCode}**

You MUST:
1. Use ${forcedFrameworkCode} as the selected_framework (look up its details from the Framework Taxonomy)
2. Generate clarifying questions that are SPECIFIC to ${forcedFrameworkCode}
3. Ensure the rationale explains why ${forcedFrameworkCode} is suitable for this task
4. Suggest alternative frameworks OTHER than ${forcedFrameworkCode}
5. Recalculate task_classification and cognitive_requirements considering ${forcedFrameworkCode}'s strengths
6. Update personality adjustments based on how ${forcedFrameworkCode} leverages personality traits

Do NOT select a different framework - you must use ${forcedFrameworkCode}.
`;
} else {
    frameworkInstructions = `
## FRAMEWORK SELECTION

3. **Select the most appropriate framework** for the task from the Framework Taxonomy
`;
}

// Build the combined prompt
const systemPrompt = `You are an API that returns JSON. You do not write conversational text. You ONLY output valid JSON.

Your task is to analyse user requests, classify them into task categories, identify cognitive requirements, select the most appropriate prompt framework, perform Task-Trait Alignment analysis, and generate tailored clarifying questions.

You have access to three reference documents:

## FRAMEWORK TAXONOMY
${frameworkDoc.content || 'Not available'}

## PERSONALITY CALIBRATION
${personalityDoc.content || 'Not available'}

## QUESTION BANK
${questionDoc.content || 'Not available'}

---

## YOUR TASK

1. **Classify the user's task** into a primary category (and secondary if applicable) from the taxonomy:
   - If pre-analysis context provided: Use task description + pre-analysis context together for classification
   - Pre-analysis clarifies subject/purpose/detail → factor this into your understanding
   - Example: "I want to fly" + subject="commercial air travel" + purpose="career" → LEARNING task
   - If no pre-analysis: Classify based on task description alone
2. **Identify cognitive requirements** for the task (from the Task Cognitive Requirements section)
${frameworkInstructions}
4. **Perform Task-Trait Alignment analysis** (if personality data provided):
   - Identify which traits ALIGN with task requirements → Mark for AMPLIFICATION
   - Identify which traits OPPOSE task requirements → Mark for COUNTERBALANCING
   - Identify which traits are UNRELATED → Mark as NEUTRAL
5. **Exclude questions based on pre-analysis context** (if provided):
   - If subject/topic already known → SKIP all "what type" questions
   - If purpose/motivation already known → SKIP all "why" questions
   - If detail level already known → SKIP all "how detailed" questions
6. **Generate clarifying questions** tailored to the task${forcedFrameworkCode ? ' AND the forced framework (' + forcedFrameworkCode + ')' : ''} and personality (if provided)
7. **Apply personality calibration** to question phrasing and quantity (if personality data provided)

## TASK-TRAIT ALIGNMENT

Analyze user's actual traits (provided in personality data):
- **AMPLIFY** aligned traits (leverage strengths)
- **COUNTERBALANCE** opposing traits (inject requirements to cover blind spots)
- **NEUTRAL** for unrelated traits

## IMPORTANT RULES

- If NO personality data is provided, skip Task-Trait Alignment and use neutral defaults
- Always explain your classification reasoning
- Select questions that are essential for generating a high-quality prompt
- Sequence questions logically (context → goals → constraints → specifics)
- Respect the question quantity guidelines based on task complexity${forcedFrameworkCode ? '\n- Generate questions SPECIFIC to ' + forcedFrameworkCode + ' framework requirements' : ''}

## CRITICAL: OUTPUT FORMAT

You are a JSON API. Your response MUST:
- Start with { and end with }
- Contain ONLY valid JSON
- NO explanatory text before or after the JSON
- NO markdown code blocks
- NO conversational language like "Great!" or "Here's..."

Return this exact JSON structure:

\`\`\`json
{
  "task_classification": {
    "primary_category": "CATEGORY_CODE",
    "secondary_category": null,
    "complexity": "simple | moderate | complex",
    "classification_reasoning": "Brief explanation",
    "content_type": "For CREATION_CONTENT only, e.g. customer_email"
  },
  "cognitive_requirements": {
    "primary": ["REQUIREMENT_CODE", "REQUIREMENT_CODE"],
    "secondary": ["REQUIREMENT_CODE"],
    "reasoning": "Why these requirements apply to this task"
  },
  "selected_framework": {
    "name": "Framework Name",
    "code": "FRAMEWORK_CODE",  // IMPORTANT: Use UPPERCASE with underscores. Examples: CRISPE, BLOOMS_TAXONOMY, CHAIN_OF_THOUGHT, SIX_THINKING_HATS
    "components": ["Component1", "Component2"],
    "rationale": "Why this framework is best for this task"
  },
  "alternative_frameworks": [
    {
      "name": "Alternative Name",
      "code": "ALT_CODE",
      "when_to_use_instead": "Condition"
    }
  ],
  "personality_tier": "full | partial | none",
  "task_trait_alignment": {
    "NOTE": "ONLY include traits from the user's actual personality. DO NOT make up traits.",
    "amplified": [
      {
        "trait": "One of the user's ACTUAL traits from the list provided",
        "requirement_aligned": "OBJECTIVE",
        "reason": "Why THIS SPECIFIC trait helps with this task"
      }
    ],
    "counterbalanced": [
      {
        "trait": "Another of the user's ACTUAL traits from the list provided",
        "requirement_opposed": "EMPATHY",
        "reason": "Why THIS SPECIFIC trait may create a blind spot",
        "injection": "Specific requirement to add to the prompt"
      }
    ],
    "neutral": [
      {
        "trait": "Another of the user's ACTUAL traits from the list provided",
        "reason": "Why THIS SPECIFIC trait is not relevant to the task"
      }
    ]
  },
  "personality_adjustments_preview": [
    "AMPLIFIED: Description of what will be leveraged",
    "COUNTERBALANCED: Description of what will be injected"
  ],
  "clarifying_questions": [
    {
      "id": "Q1",
      "question": "The question text",
      "purpose": "Why this question matters",
      "required": true
    }
  ],
  "question_rationale": "Brief explanation of question selection"
}
\`\`\``;

const hasPersonality = !!webhookData.user_context?.personality?.personality_type;
let personalityInfo =
    'No personality data provided. Skip Task-Trait Alignment and use neutral defaults.';

if (hasPersonality) {
    const personality = webhookData.user_context.personality;
    personalityInfo = `Type: ${personality.personality_type}`;
    if (personality.trait_percentages) {
        // Extract trait letters from personality type and percentages
        // personality_type format: "INTP-A" where each letter corresponds to a trait
        const typeLetters = personality.personality_type.split('-');
        const mainType = typeLetters[0]; // e.g., "INTP"
        const identity = typeLetters[1]; // e.g., "A" or "T"
        const traits = personality.trait_percentages;
        const traitDescriptions = [];

        // Map each percentage to its corresponding letter from the personality type
        if (traits.mind !== undefined) {
            traitDescriptions.push(`${mainType[0]} (${traits.mind}%)`); // I or E
        }
        if (traits.energy !== undefined) {
            traitDescriptions.push(`${mainType[1]} (${traits.energy}%)`); // S or N
        }
        if (traits.nature !== undefined) {
            traitDescriptions.push(`${mainType[2]} (${traits.nature}%)`); // T or F
        }
        if (traits.tactics !== undefined) {
            traitDescriptions.push(`${mainType[3]} (${traits.tactics}%)`); // J or P
        }
        if (traits.identity !== undefined) {
            traitDescriptions.push(`${identity} (${traits.identity}%)`); // A or T
        }

        personalityInfo += `\nTraits: ${traitDescriptions.join(', ')}`;
        personalityInfo += `\nAnalyze ONLY these traits (not hypothetical ideal traits).`;
        personalityInfo += `\nFor each: AMPLIFIED (helps task), COUNTERBALANCED (opposes task), or NEUTRAL.`;
    } else {
        personalityInfo += `\nPercentages: Not provided (use 65% default for each trait)`;
        personalityInfo += `\nPerform Task-Trait Alignment with default percentages.`;
    }
}


// Pre-analysis context should ALWAYS be provided (either from answers or inferred by workflow_0)
const preAnalysisContext = webhookData.pre_analysis_context || null;
let preAnalysisSection = '';

if (preAnalysisContext && typeof preAnalysisContext === 'object' && Object.keys(preAnalysisContext).length > 0) {
    preAnalysisSection = '\n\n**Pre-Analysis Context:**\nContext gathered during pre-analysis:';

    // Loop through all pre-analysis context entries and display question/answer pairs
    for (const [key, contextItem] of Object.entries(preAnalysisContext)) {
        if (contextItem && typeof contextItem === 'object' && contextItem.question) {
            preAnalysisSection += `\n\n**${contextItem.question}**`;

            // Display answer_label if available, otherwise display answer
            const displayValue = contextItem.answer_label || contextItem.answer;
            if (displayValue) {
                preAnalysisSection += `\n${displayValue}`;
            }
        }
    }

    preAnalysisSection += '\n\n**CRITICAL - Avoid Duplicate ClarifyingQuestions:**';
    preAnalysisSection += '\n- DO NOT ask questions that overlap with the information already provided above';
    preAnalysisSection += '\n- For example, if we already know the audience/skill level, do NOT ask about it again';
    preAnalysisSection += '\n- Generate ONLY questions that request NEW information not covered in the pre-analysis context';
    preAnalysisSection += '\n\n**IMPORTANT for Task-Trait Alignment:**';
    preAnalysisSection += '\n- If audience is "self" → Apply full personality optimisation';
    preAnalysisSection += '\n- If audience is external (team/management/external) → Still apply personality adjustments but note "creating for others"';
    preAnalysisSection += '\n- Audience context should inform counterbalancing (e.g., external stakeholders may need different communication style)';
}

let userMessage = `Analyse this task and generate clarifying questions:

**Task Description:**
${webhookData.task_description || 'No task provided'}${preAnalysisSection}

**Personality Data:**\n${personalityInfo}`;

if (forcedFrameworkCode) {
    userMessage += `\n\n**FORCED FRAMEWORK:** ${forcedFrameworkCode}`;
    userMessage += `\nYou MUST use this framework as the selected_framework. Look it up in the Framework Taxonomy and generate questions specific to it.${userContextString}`;
}

return [
    {
        json: {
            system: systemPrompt,
            messages: [
                {
                    role: 'user',
                    content: userMessage,
                },
            ]
        },
    },
];
