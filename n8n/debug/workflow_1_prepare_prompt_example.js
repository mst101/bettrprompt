// Example: Prepare Prompt Node JavaScript for Workflow 1 (Framework Analysis)

// Collect all data from the workflow
var webhookData = $('Webhook Trigger').first().json.body || {};

// Get static reference documents (no HTTP requests - instant access)
var referenceData = $('Load Reference Documents').first().json;
var frameworkDoc = referenceData.framework_taxonomy_doc;
var questionDoc = referenceData.question_bank_doc;

// Extract user context
var userContext = webhookData.user_context || {};
var preAnalysisContext = webhookData.pre_analysis_context || {};

// Extract personality information
var personality = userContext.personality || {};
var personalityType = personality.personality_type || 'Unknown';

// Extract pre-analysis answers
var skillLevel = preAnalysisContext.current_skill_level?.answer_label || 'Not specified';
var purpose = preAnalysisContext.purpose?.answer_label || 'Not specified';
var environment = preAnalysisContext.learning_environment?.answer_label || 'Not specified';

// Build the system prompt
var system = `You are an expert framework selector for AI prompts. Your role is to:

1. Analyze the learning task provided
2. Consider the user's personality type (${personalityType})
3. Select the most appropriate prompt framework from the available options
4. Provide structured questions to refine the framework

User Context:
- Skill Level: ${skillLevel}
- Learning Purpose: ${purpose}
- Learning Environment: ${environment}
- Location: ${userContext.location?.city || 'Unknown'}, ${userContext.location?.country || 'Unknown'}
- Personality Type: ${personalityType}

Respond with a JSON structure containing:
{
  "selected_framework": {
    "name": "Framework Name",
    "code": "FRAMEWORK_CODE",
    "components": ["component1", "component2"],
    "rationale": "Why this framework is best for this task"
  },
  "framework_questions": ["Question 1", "Question 2", "Question 3"]
}`;

// Build the messages array
var messages = [
  {
    role: "user",
    content: `Please analyse this learning task and select the best framework:\n\nTask: ${webhookData.task_description}\n\nUser's Learning Context:\n- Current Skill Level: ${skillLevel}\n- Primary Motivation: ${purpose}\n- Learning Environment: ${environment}`
  }
];
