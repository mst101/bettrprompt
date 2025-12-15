// Extract data
const webhookData = $('Webhook Trigger').first().json.body || {};
const refData = $('Load Reference Documents').first().json;
let fwCode = webhookData.analysis_data.selected_framework.code;

// Get framework template (with fallbacks)
let fwContent = refData.framework_templates[fwCode];
if (!fwContent && refData.framework_templates[fwCode + '_TAXONOMY']) {
  fwContent = refData.framework_templates[fwCode + '_TAXONOMY'];
  fwCode += '_TAXONOMY';
}
if (!fwContent) {
  const search = fwCode.toUpperCase();
  for (const [k, v] of Object.entries(refData.framework_templates)) {
    if (k.toUpperCase().startsWith(search)) {
      fwContent = v;
      fwCode = k;
      break;
    }
  }
}
if (!fwContent) throw new Error(`Framework ${fwCode} not found. Available: ${Object.keys(refData.framework_templates).sort().join(', ')}`);

const docs = {
  framework: fwContent,
  personality: refData.personality_calibration
};

const analysisData = webhookData.analysis_data || {};
const qAnswers = webhookData.question_answers || [];
const preCtx = webhookData.pre_analysis_context;
const uc = webhookData.user_context;

// Build context helper
const buildCtx = (uc) => {
  if (!uc) return '';
  const p = [];
  if (uc.location?.country) {
    p.push(`Loc: ${uc.location.country}${uc.location.city ? ` (${uc.location.city})` : ''}`);
    if (uc.location.timezone) p.push(`TZ: ${uc.location.timezone}`);
    if (uc.location.currency) p.push(`Cur: ${uc.location.currency}`);
  }
  if (uc.professional?.job_title || uc.professional?.industry) {
    const pr = uc.professional;
    const pp = [pr.job_title, pr.industry ? `in ${pr.industry}` : '', pr.experience_level ? `(${pr.experience_level})` : ''].filter(Boolean);
    if (pp.length) p.push(`Prof: ${pp.join(' ')}`);
    if (pr.company_size) p.push(`Co: ${pr.company_size}`);
  }
  if (uc.team?.size || uc.team?.role) {
    const t = uc.team;
    const tp = [t.size ? `${t.size} team` : '', t.role, t.work_mode ? `(${t.work_mode})` : ''].filter(Boolean);
    if (tp.length) p.push(`Team: ${tp.join(', ')}`);
  }
  if (uc.preferences?.budget) p.push(`Budget: ${uc.preferences.budget}`);
  if (uc.preferences?.primary_programming_language) p.push(`Lang: ${uc.preferences.primary_programming_language}`);
  return p.length ? '\n\n# Context\n' + p.map(x => `- ${x}`).join('\n') + '\n\nUse in examples.' : '';
};

const getLang = (lang) => {
  const l = lang || 'en-GB';
  if (l.startsWith('en-US')) return '\n\n# Lang: US English';
  if (l.startsWith('en-')) return '\n\n# Lang: UK English';
  return `\n\n# Lang: ${l}`;
};

const systemPrompt = `Prompt engineer: build optimised prompt using framework + trait alignment.

## DOCS
${docs.framework || 'N/A'}

${docs.personality || 'N/A'}

---

## TASK
1. Use framework template
2. Apply amplification + counterbalancing
3. Incorporate answers
4. Recommend models

## OUTPUT (valid JSON, escape \\n)
\`\`\`json
{"optimised_prompt":"Complete prompt text","metadata":{"framework_used":{"name":"Name","code":"CODE","components":["C1"],"explanation":"How"},"task_trait_alignment":{"amplified":[{"trait":"T (64%)","requirement_aligned":"REQ","how_applied":"Desc"}],"counterbalanced":[{"trait":"T (84%)","requirement_opposed":"REQ","reason":"Why","injections_added":["Text"]}],"neutral":[{"trait":"T (65%)","reason":"Why"}]},"personality_adjustments_summary":["AMPLIFIED: Desc","COUNTERBALANCED: Desc"],"model_recommendations":[{"rank":1,"model":"Model","model_id":"id","rationale":"Why"}],"iteration_suggestions":["Suggestion"]}}
\`\`\`${getLang(uc?.location?.language)}`;

// Build message
let msg = `## ANALYSIS
Task: ${analysisData.task_classification?.primary_category} (${analysisData.task_classification?.complexity})
Framework: ${analysisData.selected_framework?.name}
Personality: ${analysisData.personality_tier || 'none'}

`;

if (analysisData.task_trait_alignment) {
  msg += `**Trait Alignment:**\n\n\`\`\`json\n${JSON.stringify(analysisData.task_trait_alignment, null, 2)}\n\`\`\`\n\nIMPORTANT: Apply amplifications/counterbalancing. Include injection text.\n\n`;
}

msg += `---\n\n## TASK\n${webhookData.original_task_description || 'No task'}\n\n`;

if (uc?.personality?.personality_type) {
  msg += `## PERSONALITY\nType: ${uc.personality.personality_type}\n`;
  if (uc.personality.trait_percentages) msg += `Percentages: ${JSON.stringify(uc.personality.trait_percentages)}\n`;
  msg += `\n`;
}

if (preCtx && Object.keys(preCtx).length) {
  msg += `## PRE-ANALYSIS\n\`\`\`json\n${JSON.stringify(preCtx, null, 2)}\n\`\`\`\n\n`;
}

msg += `## ANSWERS\n`;
if (qAnswers?.length) {
  qAnswers.forEach((qa, i) => msg += `**Q${i + 1}: ${qa.question}**\nA: ${qa.answer}\n\n`);
} else {
  msg += `None.\n\n`;
}

msg += `---\n\nBuild prompt:\n1. Use ${analysisData.selected_framework?.name || 'selected'} framework\n2. Incorporate answers\n3. Apply AMPLIFICATION (leverage)\n4. Apply COUNTERBALANCING (inject)\n5. Self-contained\n6. Recommend models${buildCtx(uc)}`;

return [{json:{system:systemPrompt,messages:[{role:'user',content:msg}]}}];
