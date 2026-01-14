<?php

namespace App\Services;

use App\Models\Question;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;

class QuestionMarkdownGeneratorService
{
    /**
     * Generate the markdown file from the database.
     */
    public function generateMarkdown(): string
    {
        $content = $this->buildMarkdownContent();
        $path = resource_path('reference_documents/question_bank.md');

        // Ensure directory exists
        if (! File::isDirectory(dirname($path))) {
            File::makeDirectory(dirname($path), 0755, true);
        }

        File::put($path, $content);

        return $path;
    }

    /**
     * Build the complete markdown content.
     */
    protected function buildMarkdownContent(): string
    {
        $sections = [];

        $sections[] = $this->buildHeader();
        $sections[] = $this->buildUniversalQuestionsSection();
        $sections[] = $this->buildFrameworkSpecificSections();
        $sections[] = $this->buildCategorySpecificSections();
        $sections[] = $this->buildFrameworkSelectionSection();
        $sections[] = $this->buildFooter();

        return implode("\n\n---\n\n", $sections);
    }

    /**
     * Build the header with metadata.
     */
    protected function buildHeader(): string
    {
        $version = Carbon::now()->format('Y.m.d.His');
        $timestamp = Carbon::now()->format('l, j F Y \a\t H:i:s');
        $totalQuestions = Question::active()->count();
        $totalVariants = Question::active()->withCount('variants')->get()->sum('variants_count');

        return <<<MD
# Question Bank Reference Document

**Version:** {$version}
**Generated:** {$timestamp}
**Total Questions:** {$totalQuestions}
**Total Personality Variants:** {$totalVariants}

**Generation Source:** Database (questions & question_variants tables)

## Purpose

This document contains all clarifying questions organised by task category and framework. Use this to generate appropriate questions after classifying the user's task. Questions should be selected based on task category, adjusted for personality (if provided), and limited to the appropriate quantity.
MD;
    }

    /**
     * Build universal questions section.
     */
    protected function buildUniversalQuestionsSection(): string
    {
        $questions = Question::where('category', 'universal')->active()->orderBy('display_order')->get();

        $md = [];
        $md[] = '## Universal Questions';
        $md[] = '';
        $md[] = 'These questions are relevant across most task categories. Select 2-3 from this pool for every task.';
        $md[] = '';
        $md[] = '### Core Universal Questions';
        $md[] = '';
        $md[] = '| ID | Question | Purpose | Cognitive Reqs | Priority |';
        $md[] = '|----|----------|---------|----------------|----------|';

        foreach ($questions as $question) {
            $cogReqs = $question->cognitive_requirements
                ? implode(', ', array_map(fn ($r) => "`$r`", $question->cognitive_requirements))
                : '';
            $md[] = "| {$question->id} | {$question->question_text} | {$question->purpose} | {$cogReqs} | {$question->priority} |";
        }

        // Add personality variants section
        $md[] = '';
        $md[] = '### Personality-Adjusted Universal Questions';
        $md[] = '';
        $md[] = 'For each universal question, select the phrasing that matches the user\'s personality (or use neutral if no personality data):';

        foreach ($questions as $question) {
            if ($question->variants->isNotEmpty()) {
                $md[] = '';
                $md[] = "#### {$question->id}: {$question->purpose} (Personality Variants)";
                $md[] = '';
                $md[] = '| Personality Pattern | Phrasing |';
                $md[] = '|---------------------|----------|';

                foreach ($question->variants as $variant) {
                    $md[] = "| {$variant->personality_pattern} | \"{$variant->phrasing}\" |";
                }
            }
        }

        return implode("\n", $md);
    }

    /**
     * Build framework-specific sections.
     */
    protected function buildFrameworkSpecificSections(): string
    {
        $frameworks = [
            'co_star' => 'CO-STAR Tasks (Content with Tone/Style Requirements)',
            'react' => 'ReAct Tasks (Agentic/Tool-Using Workflows)',
            'self_refine' => 'Self-Refine Tasks (Quality-Critical Iterative Work)',
            'step_back' => 'Step-Back Tasks (Principle-Based Reasoning)',
            'skeleton_of_thought' => 'Skeleton-of-Thought Tasks (Structured Parallel Content)',
            'meta_prompting' => 'Meta Prompting Tasks (Prompt Optimisation)',
        ];

        $sections = [];

        foreach ($frameworks as $frameworkId => $frameworkTitle) {
            $questions = Question::where('framework', $frameworkId)
                ->where('is_conditional', false)
                ->active()
                ->orderBy('display_order')
                ->get();

            if ($questions->isEmpty()) {
                continue;
            }

            $md = [];
            $md[] = '## Framework-Specific Questions';
            $md[] = '';
            $md[] = "### $frameworkTitle";
            $md[] = '';
            $md[] = '| ID | Question | Purpose | Cognitive Reqs | Priority |';
            $md[] = '|----|----------|---------|----------------|----------|';

            foreach ($questions as $question) {
                $cogReqs = $question->cognitive_requirements
                    ? implode(', ', array_map(fn ($r) => "`$r`", $question->cognitive_requirements))
                    : '';
                $md[] = "| {$question->id} | {$question->question_text} | {$question->purpose} | {$cogReqs} | {$question->priority} |";
            }

            // Add personality variants if available
            $variantQuestions = $questions->filter(fn ($q) => $q->variants->isNotEmpty());
            if ($variantQuestions->isNotEmpty()) {
                $md[] = '';
                $md[] = '#### Personality-Adjusted Phrasing for '.str_replace('Tasks', '', $frameworkTitle);
                $md[] = '';
                $md[] = '| Trait | Question Adaptation |';
                $md[] = '|-------|---------------------|';

                foreach ($variantQuestions as $question) {
                    foreach ($question->variants as $variant) {
                        $md[] = "| {$variant->personality_pattern} | \"{$variant->phrasing}\" |";
                    }
                }
            }

            // Add conditional questions
            $conditionalQuestions = Question::where('framework', $frameworkId)
                ->where('is_conditional', true)
                ->active()
                ->get();

            if ($conditionalQuestions->isNotEmpty()) {
                $md[] = '';
                $md[] = '#### Conditional Questions for '.str_replace('Tasks', '', $frameworkTitle);
                $md[] = '';

                foreach ($conditionalQuestions as $question) {
                    $md[] = "- If {$question->condition_text}: \"{$question->question_text}\"";
                }
            }

            $sections[] = implode("\n", $md);
        }

        return implode("\n\n---\n\n", $sections);
    }

    /**
     * Build category-specific sections.
     */
    protected function buildCategorySpecificSections(): string
    {
        $categories = [
            'decision' => 'DECISION Tasks',
            'strategy' => 'STRATEGY Tasks',
            'analysis' => 'ANALYSIS Tasks',
            'creation_content' => 'CREATION_CONTENT Tasks',
            'creation_technical' => 'CREATION_TECHNICAL Tasks',
            'ideation' => 'IDEATION Tasks',
            'problem_solving' => 'PROBLEM_SOLVING Tasks',
            'learning' => 'LEARNING Tasks',
            'persuasion' => 'PERSUASION Tasks',
            'feedback' => 'FEEDBACK Tasks',
            'research' => 'RESEARCH Tasks',
            'goal_setting' => 'GOAL_SETTING Tasks',
        ];

        $sections = [];

        foreach ($categories as $categoryId => $categoryTitle) {
            $questions = Question::where('category', $categoryId)
                ->where('is_universal', false)
                ->where('is_conditional', false)
                ->active()
                ->orderBy('display_order')
                ->get();

            if ($questions->isEmpty()) {
                continue;
            }

            $md = [];
            $md[] = '## Category-Specific Questions';
            $md[] = '';
            $md[] = "### $categoryTitle";
            $md[] = '';
            $md[] = '| ID | Question | Purpose | Cognitive Reqs | Priority |';
            $md[] = '|----|----------|---------|----------------|----------|';

            foreach ($questions as $question) {
                $cogReqs = $question->cognitive_requirements
                    ? implode(', ', array_map(fn ($r) => "`$r`", $question->cognitive_requirements))
                    : '';
                $md[] = "| {$question->id} | {$question->question_text} | {$question->purpose} | {$cogReqs} | {$question->priority} |";
            }

            // Add conditional questions
            $conditionalQuestions = Question::where('category', $categoryId)
                ->where('is_conditional', true)
                ->active()
                ->get();

            if ($conditionalQuestions->isNotEmpty()) {
                $md[] = '';
                $md[] = '#### Conditional Questions';
                $md[] = '';

                foreach ($conditionalQuestions as $question) {
                    $md[] = "- If {$question->condition_text}: \"{$question->question_text}\"";
                }
            }

            $sections[] = implode("\n", $md);
        }

        return implode("\n\n---\n\n", $sections);
    }

    /**
     * Build framework selection section.
     */
    protected function buildFrameworkSelectionSection(): string
    {
        $questions = Question::where('category', 'framework_selection')->active()->orderBy('display_order')->get();

        if ($questions->isEmpty()) {
            return '';
        }

        $md = [];
        $md[] = '## Framework Selection Questions';
        $md[] = '';
        $md[] = 'When task category is unclear or multiple frameworks could apply, ask:';
        $md[] = '';
        $md[] = '| ID | Question | Purpose | Guides Selection |';
        $md[] = '|----|----------|---------|------------------|';

        foreach ($questions as $question) {
            $md[] = "| {$question->id} | {$question->question_text} | {$question->purpose} | {$question->purpose} |";
        }

        return implode("\n", $md);
    }

    /**
     * Build footer with algorithm.
     */
    protected function buildFooter(): string
    {
        return <<<'MD'
## Question Selection Algorithm

```
1. Identify task category (primary and secondary if applicable)

2. Check for framework-specific indicators:
   - Tone/style critical? → CO-STAR questions
   - Tool-using/research? → ReAct questions
   - Quality-critical/iterative? → Self-Refine questions
   - Principle-based reasoning? → Step-Back questions
   - Parallel structure possible? → Skeleton-of-Thought questions
   - Prompt creation task? → Meta Prompting questions

3. Start with Universal Questions:
   - Select 2-3 from U1-U6 based on task nature
   - Use personality-adjusted phrasing if available

4. Add Framework-Specific Questions (if framework identified):
   - Select from the identified framework pool
   - Priority order: High → Medium → Low
   - Stop when sufficient clarity achieved

5. Add Category-Specific Questions:
   - Select from the primary category pool
   - Base quantity on complexity:
     Simple: 2-3 category questions
     Moderate: 3-5 category questions
     Complex: 5-7 category questions

6. Apply Personality Adjustments:
   - Adjust total count (see personality_calibration.md)
   - Adjust phrasing for personality patterns
   - If no personality: use neutral phrasing

7. Add Framework Fit Verification if uncertain:
   - For Skeleton-of-Thought: Check interdependence
   - For ReAct: Verify tool availability
   - For Self-Refine: Confirm iteration acceptable

8. Add Conditional Questions if triggered by context

9. Sequence questions logically:
   - Context/scope questions first
   - Goal/success questions second
   - Constraints third
   - Specific details last

10. Cap total questions:
    - Simple task + clear framework: 4-5 questions
    - Moderate task: 5-8 questions
    - Complex task + framework uncertainty: 8-12 questions
```
MD;
    }
}
