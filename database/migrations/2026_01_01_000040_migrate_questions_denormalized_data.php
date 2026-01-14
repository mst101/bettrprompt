<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('questions')) {
            return;
        }

        if (
            ! Schema::hasColumn('questions', 'category')
            && ! Schema::hasColumn('questions', 'framework')
            && ! Schema::hasColumn('questions', 'cognitive_requirements')
        ) {
            return;
        }

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

        $frameworkMap = [
            'co_star' => 'CO_STAR',
            'react' => 'REACT',
            'self_refine' => 'SELF_REFINE',
            'step_back' => 'STEP_BACK',
            'skeleton_of_thought' => 'SKELETON_OF_THOUGHT',
            'meta_prompting' => 'META_PROMPTING',
            'atomic_prompting' => 'ATOMIC_PROMPTING',
        ];

        DB::table('questions')->get()->each(function ($question) use ($categoryMap, $frameworkMap) {
            $categoryCode = $question->category
                ? ($categoryMap[$question->category] ?? strtoupper(str_replace(' ', '_', $question->category)))
                : null;
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

        if (! Schema::hasTable('question_cognitive_requirements')) {
            return;
        }

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
                            'requirement_level' => 'primary',
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
        if (Schema::hasTable('questions')) {
            DB::table('questions')->update([
                'task_category_code' => null,
                'framework_code' => null,
            ]);
        }

        if (Schema::hasTable('question_cognitive_requirements')) {
            DB::table('question_cognitive_requirements')->truncate();
        }
    }
};
