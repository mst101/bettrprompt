// Extract data
const webhookData = $('Webhook Trigger').first().json.body || {};
const refData = $('Load Reference Documents').first().json;
const uc = webhookData.user_context;

// Get docs (personality only if data provided)
const docs = {
  framework: refData.framework_taxonomy,
  questions: refData.question_bank,
  personality: uc?.personality?.personality_type ? refData.personality_calibration : {content: 'N/A - no personality'}
};

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
  return p.length ? '\n\n# Context\n' + p.map(x => `- ${x}`).join('\n') + '\n\nUse for framework/questions.' : '';
};

const getLang = (lang) => {
  const l = lang || 'en-GB';
  if (l.startsWith('en-US')) return '\n\n# Lang: US English';
  if (l.startsWith('en-')) return '\n\n# Lang: UK English';
  return `\n\n# Lang: ${l}`;
};

const forced = webhookData.forced_framework_code;
const fwInstr = forced ? `\n\n## FORCED FRAMEWORK: ${forced}\nMUST use ${forced}. Generate ${forced}-specific questions.` : '';

const systemPrompt = `JSON API: analyse tasks, classify, select framework, align traits, generate questions.

## DOCS
${docs.framework.content || 'N/A'}

${docs.personality.content || 'N/A'}

${docs.questions.content || 'N/A'}

---

## TASK
1. Classify task (use pre-analysis if provided)
2. Identify cognitive needs
${forced ? `3. Use ${forced} framework` : '3. Select best framework'}
4. Task-Trait Alignment (if personality data):
   - AMPLIFY aligned traits
   - COUNTERBALANCE opposing traits
   - NEUTRAL unrelated traits
5. Skip questions covered by pre-analysis
6. Generate tailored questions${forced ? ` for ${forced}` : ''}

## OUTPUT (JSON only, no markdown)
\`\`\`json
{"task_classification":{"primary_category":"CODE","secondary_category":null,"complexity":"moderate","classification_reasoning":"Brief","content_type":null},"cognitive_requirements":{"primary":["LOGICAL_THINKING"],"secondary":[],"reasoning":"Why"},"selected_framework":{"name":"Name","code":"UPPERCASE_CODE","components":["C1"],"rationale":"Why"},"alternative_frameworks":[{"name":"Alt","code":"ALT","when_to_use_instead":"When"}],"personality_tier":"full|partial|none","task_trait_alignment":{"amplified":[{"trait":"User's actual trait","requirement_aligned":"REQ","reason":"Why"}],"counterbalanced":[{"trait":"User's actual trait","requirement_opposed":"REQ","reason":"Why","injection":"Text"}],"neutral":[{"trait":"User's actual trait","reason":"Why"}]},"personality_adjustments_preview":["AMPLIFIED: Desc","COUNTERBALANCED: Desc"],"clarifying_questions":[{"id":"Q1","question":"Text","purpose":"Why","required":true}],"question_rationale":"Brief"}
\`\`\`${getLang(uc?.location?.language)}`;

// Build personality info
const hasP = !!uc?.personality?.personality_type;
let pInfo = 'No personality. Skip alignment.';
if (hasP) {
  const p = uc.personality;
  pInfo = `Type: ${p.personality_type}`;
  if (p.trait_percentages) {
    const t = p.trait_percentages;
    const type = p.personality_type.split('-');
    const traits = [
      `${type[0][0]} (${t.mind}%)`,
      `${type[0][1]} (${t.energy}%)`,
      `${type[0][2]} (${t.nature}%)`,
      `${type[0][3]} (${t.tactics}%)`,
      `${type[1]} (${t.identity}%)`
    ].filter((x,i) => [t.mind,t.energy,t.nature,t.tactics,t.identity][i] !== undefined);
    pInfo += `\nTraits: ${traits.join(', ')}\nAnalyse ONLY these. Mark: AMPLIFIED/COUNTERBALANCED/NEUTRAL.`;
  } else {
    pInfo += `\nPercentages: Use 65% default\nPerform alignment with defaults.`;
  }
}

// Pre-analysis section
const preCtx = webhookData.pre_analysis_context;
let preSection = '';
if (preCtx && typeof preCtx === 'object' && Object.keys(preCtx).length) {
  preSection = '\n\n**Pre-Analysis:**';
  for (const [key, item] of Object.entries(preCtx)) {
    if (item?.question) {
      preSection += `\n\n**${item.question}**\n${item.answer_label || item.answer}`;
    }
  }
  preSection += '\n\n**Avoid duplicates:** Skip questions covered above. Ask only NEW info.';
  preSection += '\n\n**Trait alignment:** If audience=self → full optimisation. If external → note "for others".';
}

let userMsg = `Task: ${webhookData.task_description || 'No task'}${preSection}\n\n**Personality:**\n${pInfo}`;
if (forced) userMsg += `\n\n**FORCED:** ${forced}\nUse this framework.${buildCtx(uc)}`;

return [{json:{system:systemPrompt,messages:[{role:'user',content:userMsg}]}}];
