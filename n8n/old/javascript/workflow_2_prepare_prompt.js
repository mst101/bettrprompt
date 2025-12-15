// Collect all data from the workflow
const webhookData = $('Webhook Trigger').first().json.body || {};

// Get framework template from embedded static data
const referenceData = $('Load Reference Documents').first().json;
let frameworkCode = webhookData.analysis_data.selected_framework.code;

// Try exact match first
let frameworkTemplateContent = referenceData.framework_templates[frameworkCode];

// If not found, try common variations
if (!frameworkTemplateContent) {
  // Try with _TAXONOMY suffix (e.g., BLOOMS → BLOOMS_TAXONOMY)
  const withTaxonomy = frameworkCode + '_TAXONOMY';
  if (referenceData.framework_templates[withTaxonomy]) {
    frameworkTemplateContent = referenceData.framework_templates[withTaxonomy];
    frameworkCode = withTaxonomy;
  }
}

// If still not found, try case-insensitive partial match
if (!frameworkTemplateContent) {
  const searchCode = frameworkCode.toUpperCase();
  for (const [key, value] of Object.entries(referenceData.framework_templates)) {
    if (key.toUpperCase().startsWith(searchCode)) {
      frameworkTemplateContent = value;
      frameworkCode = key;
      console.log(`Framework code fuzzy-matched: ${webhookData.analysis_data.selected_framework.code} → ${key}`);
      break;
    }
  }
}

if (!frameworkTemplateContent) {
  throw new Error(`Framework template not found for code: ${webhookData.analysis_data.selected_framework.code} (tried: ${frameworkCode}). Available codes: ${Object.keys(referenceData.framework_templates).sort().join(', ')}`);
}

const frameworkTemplateDoc = {
  success: true,
  content: frameworkTemplateContent,
  framework_code: frameworkCode
};
// Get personality calibration from embedded static data
const personalityDoc = referenceData.personality_calibration;

// Extract the analysis data from Workflow 1
const analysisData = webhookData.analysis_data || {};
const questionAnswers = webhookData.question_answers || [];
const preAnalysisContext = webhookData.pre_analysis_context || null;
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
    userContextString = '\n\n# User Context\n' + parts.map(p => `- ${p}`).join('\n') + '\n\nUse this context when optimising the prompt (e.g., use local currency in examples, recommend appropriate tools, adjust complexity level for experience).';
  }
}


// Build the system prompt
const systemPrompt = `You are an expert prompt engineering assistant. Your task is to construct an optimised, ready-to-use prompt based on the framework selected in the analysis phase, applying Task-Trait Alignment adjustments (amplification and counterbalancing) as specified.

You have access to two reference documents:

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

## COUNTERBALANCE INJECTION

When counterbalancing specified: add explicit requirements in prompt, include in quality criteria.

## OUTPUT FORMAT

You MUST respond with valid JSON only (no markdown code blocks, no extra text).

IMPORTANT: The optimised_prompt field will contain newlines. You MUST properly escape them as \\n in the JSON output. All newlines in string values must be escaped.

Use this exact structure:

\`\`\`json
{
  "optimised_prompt": "The complete prompt text, ready for copy/paste. This should be a fully self-contained prompt that includes all context, requirements, and instructions. It should be usable immediately without any additional information.",

  "metadata": {
    "framework_used": {
      "name": "Framework Name",
      "code": "FRAMEWORK_CODE",
      "components": ["Component1", "Component2"],
      "explanation": "How the framework was applied"
    },

    "task_trait_alignment": {
      "amplified": [
        {
          "trait": "High N (64%)",
          "requirement_aligned": "VISION",
          "how_applied": "Description of how this was leveraged in the prompt"
        }
      ],
      "counterbalanced": [
        {
          "trait": "High T (84%)",
          "requirement_opposed": "EMPATHY",
          "reason": "Why counterbalancing was needed",
          "injections_added": [
            "Specific text that was added to the prompt"
          ]
        }
      ],
      "neutral": [
        {
          "trait": "High I (65%)",
          "reason": "Why no adjustment was made"
        }
      ]
    },

    "personality_adjustments_summary": [
      "AMPLIFIED: Brief description",
      "COUNTERBALANCED: Brief description"
    ],

    "model_recommendations": [
      {
        "rank": 1,
        "model": "Model Name",
        "model_id": "model-id-string",
        "rationale": "Why this model is recommended, considering counterbalance complexity"
      },
      {
        "rank": 2,
        "model": "Alternative Model",
        "model_id": "model-id-string",
        "rationale": "Why this is a good alternative"
      },
      {
        "rank": 3,
        "model": "Budget Option",
        "model_id": "model-id-string",
        "rationale": "Cost-effective option if applicable"
      }
    ],

    "iteration_suggestions": [
      "Suggestion for how to refine the prompt if needed",
      "Another potential adjustment"
    ]
  }
}
\`\`\``;

// Build the user message with all the context
let userMessage = `## ANALYSIS FROM WORKFLOW 1\n\n`;
userMessage += `Task: ${analysisData.task_classification?.primary_category} (${analysisData.task_classification?.complexity})
`;
userMessage += `Framework: ${analysisData.selected_framework?.name}
`;
userMessage += `Personality: ${analysisData.personality_tier || 'none'}

`;

if (analysisData.task_trait_alignment) {
    userMessage += `**Task-Trait Alignment Analysis:**\n\n\`\`\`json\n${JSON.stringify(analysisData.task_trait_alignment, null, 2)}\n\`\`\`\n\n`;
    userMessage += `IMPORTANT: Apply the amplifications and counterbalancing as specified above. For each counterbalanced trait, ensure the injection text is explicitly included in the generated prompt.\n\n`;
}

userMessage += `---\n\n## ORIGINAL TASK\n\n${webhookData.original_task_description || 'No task description provided'}\n\n`;

if (webhookData.user_context?.personality?.personality_type) {
    userMessage += `## PERSONALITY DATA\n\n`;
    userMessage += `Type: ${webhookData.user_context.personality.personality_type}\n`;
    if (webhookData.user_context.personality.trait_percentages) {
        userMessage += `Percentages: ${JSON.stringify(webhookData.user_context.personality.trait_percentages)}\n`;
    }
    userMessage += `\n`;
}

// Add pre-analysis context if available
if (preAnalysisContext && Object.keys(preAnalysisContext).length > 0) {
    userMessage += `## PRE-ANALYSIS CONTEXT\n\n`;
    userMessage += `Context gathered during pre-analysis:\n\n\`\`\`json\n`;
    userMessage += JSON.stringify(preAnalysisContext, null, 2) + `\n\`\`\`\n\n`;
}

userMessage += `## USER'S ANSWERS TO CLARIFYING QUESTIONS\n\n`;
if (questionAnswers && questionAnswers.length > 0) {
    questionAnswers.forEach((qa, index) => {
        userMessage += `**Q${index + 1}: ${qa.question}**\n`;
        userMessage += `A: ${qa.answer}\n\n`;
    });
} else {
    userMessage += `No question answers provided.\n\n`;
}

userMessage += `---\n\n`;
userMessage += `Now construct the optimised prompt. Remember to:\n`;
userMessage += `1. Use the ${analysisData.selected_framework?.name || 'selected'} framework structure\n`;
userMessage += `2. Incorporate all user answers naturally into the prompt\n`;
userMessage += `3. Apply AMPLIFICATION for aligned traits (leverage strengths)\n`;
userMessage += `4. Apply COUNTERBALANCING for opposed traits (inject explicit requirements)\n`;
userMessage += `5. Make the prompt self-contained and immediately usable\n`;
userMessage += `6. Recommend models considering the complexity of counterbalancing needed${userContextString}`;

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