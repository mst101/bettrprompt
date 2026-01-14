<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Map old lowercase category values to uppercase codes
        $categoryMap = [
            'universal' => null, // Universal questions have no category
            'co_star' => 'CO_STAR',
            'decision' => 'DECISION',
            'strategy' => 'STRATEGY',
            'analysis' => 'ANALYSIS',
            'creation_content' => 'CREATION_CONTENT',
            'creation_technical' => 'CREATION_TECHNICAL',
            'ideation' => 'IDEATION',
            'problem_solving' => 'PROBLEM_SOLVING',
            'optimization' => 'OPTIMIZATION',
            'learning' => 'LEARNING',
            'persuasion' => 'PERSUASION',
            'feedback' => 'FEEDBACK',
            'research' => 'RESEARCH',
            'goal_setting' => 'GOAL_SETTING',
        ];

        // Map old lowercase framework values to uppercase codes
        $frameworkMap = [
            'co_star' => 'CO_STAR',
            'react' => 'REACT',
            'self_refine' => 'SELF_REFINE',
            'step_back' => 'STEP_BACK',
            'skeleton_of_thought' => 'SKELETON_OF_THOUGHT',
            'meta_prompting' => 'META_PROMPTING',
            'atomic_prompting' => 'ATOMIC_PROMPTING',
        ];

        // Migrate task_category_code and framework_code from old string columns
        DB::table('questions')->get()->each(function ($question) use ($categoryMap, $frameworkMap) {
            $categoryCode = $categoryMap[$question->category] ?? strtoupper(str_replace(' ', '_', $question->category));
            $frameworkCode = $question->framework
                ? ($frameworkMap[$question->framework] ?? strtoupper(str_replace(' ', '_', $question->framework)))
                : null;

            DB::table('questions')
                ->where('id', $question->id)
                ->update([
                    'task_category_code' => $categoryCode,
                    'framework_code' => $frameworkCode,
                ]);
        });

        // Migrate cognitive_requirements JSONB to junction table
        DB::table('questions')
            ->whereNotNull('cognitive_requirements')
            ->get()
            ->each(function ($question) {
                $requirements = json_decode($question->cognitive_requirements, true);

                if (is_array($requirements) && count($requirements) > 0) {
                    foreach ($requirements as $reqCode) {
                        DB::table('question_cognitive_requirements')->insertOrIgnore([
                            'question_id' => $question->id,
                            'cognitive_requirement_code' => $reqCode,
                            'requirement_level' => 'primary', // Default to primary
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Clear new columns
        DB::table('questions')->update([
            'task_category_code' => null,
            'framework_code' => null,
        ]);

        // Clear junction table
        DB::table('question_cognitive_requirements')->truncate();
    }
};
