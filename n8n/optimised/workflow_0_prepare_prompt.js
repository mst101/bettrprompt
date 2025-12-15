// Extract webhook data
const webhookData = $input.first().json.body || {};
const uc = webhookData.user_context;

// Build context helpers
const buildContext = (uc) => {
  if (!uc) return '';
  const p = [];

  if (uc.location?.country) {
    p.push(`Location: ${uc.location.country}${uc.location.city ? ` (${uc.location.city})` : ''}`);
    if (uc.location.timezone) p.push(`TZ: ${uc.location.timezone}`);
    if (uc.location.currency) p.push(`Currency: ${uc.location.currency}`);
  }

  if (uc.professional?.job_title || uc.professional?.industry) {
    const pr = uc.professional;
    const pp = [];
    if (pr.job_title) pp.push(pr.job_title);
    if (pr.industry) pp.push(`in ${pr.industry}`);
    if (pr.experience_level) pp.push(`(${pr.experience_level})`);
    if (pp.length) p.push(`Prof: ${pp.join(' ')}`);
    if (pr.company_size) p.push(`Co: ${pr.company_size}`);
  }

  if (uc.team?.size || uc.team?.role) {
    const t = uc.team;
    const tp = [];
    if (t.size) tp.push(`${t.size} team`);
    if (t.role) tp.push(t.role);
    if (t.work_mode) tp.push(`(${t.work_mode})`);
    if (tp.length) p.push(`Team: ${tp.join(', ')}`);
  }

  if (uc.preferences?.budget) p.push(`Budget: ${uc.preferences.budget}`);
  if (uc.preferences?.primary_programming_language) p.push(`Lang: ${uc.preferences.primary_programming_language}`);

  return p.length ? '\n\n# Context\n' + p.map(x => `- ${x}`).join('\n') : '';
};

const getLangInstr = (lang) => {
  const l = lang || 'en-GB';
  if (l.startsWith('en-US')) return '\n\n# Lang: US English\nUse US spelling/formats';
  if (l.startsWith('en-')) return '\n\n# Lang: UK English\nUse UK spelling/formats';
  return `\n\n# Lang: ${l}\nAdapt for locale`;
};

const ctx = buildContext(uc) + getLangInstr(uc?.location?.language);

const systemPrompt = `Clarity checker for prompt tool. Determine if task needs clarification.

# Needs Clarification If Missing:
- Subject/topic (WHAT)
- Audience (WHO)
- Purpose (WHY)
- Detail level (HOW MUCH)

# Clear Task Signs:
- Specific technical requirements
- Clear subject + deliverable
- 10+ meaningful words

# Always Return Context:
- subject: Main topic
- audience: self/team/management/external
- purpose: High-level goal
- detail_level: summary/moderate/comprehensive

# Question Types:
- choice: Categorical (needs options array)
- yes_no: Binary (needs options array)
- text: Open-ended (no options)

# Output:

**If clear (infer context):**
\`\`\`json
{"needs_clarification":false,"pre_analysis_context":{"subject":"[inferred]","audience":"self","purpose":"[inferred]","detail_level":"moderate"},"reasoning":"Sufficient context"}
\`\`\`

**If ambiguous (ask 2-3 max):**
\`\`\`json
{"needs_clarification":true,"questions":[{"id":"subject","type":"text","question":"Main subject?"},{"id":"audience","type":"choice","question":"Primary audience?","options":[{"value":"self","label":"Myself"},{"value":"team","label":"Team"},{"value":"management","label":"Management"},{"value":"external","label":"External"}]}],"reasoning":"Needs [aspects]"}
\`\`\`

Return JSON only${ctx}`;

const userMessage = `Task: ${webhookData.task_description || 'No task'}`;

return [{json:{system:systemPrompt,messages:[{role:'user',content:userMessage}]}}];
