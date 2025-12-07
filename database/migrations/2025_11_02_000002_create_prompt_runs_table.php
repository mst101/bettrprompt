<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('prompt_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('visitor_id')->constrained('visitors')->onDelete('set null');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('parent_id')->nullable()->constrained('prompt_runs')->onDelete('cascade');
            $table->enum('personality_type', [
                // Analysts (NT)
                'INTJ-A', 'INTJ-T', 'INTP-A', 'INTP-T',
                'ENTJ-A', 'ENTJ-T', 'ENTP-A', 'ENTP-T',
                // Diplomats (NF)
                'INFJ-A', 'INFJ-T', 'INFP-A', 'INFP-T',
                'ENFJ-A', 'ENFJ-T', 'ENFP-A', 'ENFP-T',
                // Sentinels (SJ)
                'ISTJ-A', 'ISTJ-T', 'ISFJ-A', 'ISFJ-T',
                'ESTJ-A', 'ESTJ-T', 'ESFJ-A', 'ESFJ-T',
                // Explorers (SP)
                'ISTP-A', 'ISTP-T', 'ISFP-A', 'ISFP-T',
                'ESTP-A', 'ESTP-T', 'ESFP-A', 'ESFP-T',
            ])->nullable(); // 32 MBTI personality types (16 base × 2 identities: A=Assertive, T=Turbulent)
            $table->json('trait_percentages')->nullable(); // Store trait percentages
            $table->text('task_description');
            $table->json('pre_analysis_questions')->nullable();
            $table->json('pre_analysis_answers')->nullable();
            $table->json('pre_analysis_context')->nullable();
            $table->text('pre_analysis_reasoning')->nullable();
            $table->boolean('pre_analysis_skipped')->default(false);
            $table->json('framework_questions')->nullable();
            $table->json('clarifying_answers')->nullable();
            $table->integer('current_question_index')->default(0);
            $table->text('optimized_prompt')->nullable();
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->enum('workflow_stage', [
                'submitted', 'generating_pre_analysis', 'pre_analysis_questions', 'analysis_complete', 'answering_questions', 'generating_prompt',
                'completed', 'failed',
            ])->default('submitted');
            $table->text('error_message')->nullable();
            $table->timestamp('completed_at')->nullable();

            // Prompt Builder specific fields (from workflow 1 analysis)
            $table->json('task_classification')->nullable(); // {primary_category, secondary_category, complexity, classification_reasoning}
            $table->json('cognitive_requirements')->nullable(); // {primary[], secondary[], reasoning}
            $table->json('selected_framework')->nullable(); // {name, code, components[], rationale}
            $table->json('alternative_frameworks')->nullable(); // [{name, code, when_to_use_instead}]
            $table->enum('personality_tier', ['full', 'partial', 'none'])->nullable();
            $table->json('task_trait_alignment')->nullable(); // {amplified[], counterbalanced[], neutral[]}
            $table->json('personality_adjustments_preview')->nullable(); // string[]
            $table->text('question_rationale')->nullable(); // Why these questions were chosen

            // Prompt Builder specific fields (from workflow 2 generation)
            $table->json('framework_used')->nullable(); // {name, code, components[], explanation}
            $table->json('personality_adjustments_summary')->nullable(); // string[] of adjustment descriptions
            $table->json('model_recommendations')->nullable(); // [{rank, model, model_id, rationale}]
            $table->json('iteration_suggestions')->nullable(); // string[] of suggestions for improving the prompt

            // API usage tracking
            $table->json('pre_analysis_api_usage')->nullable(); // {model, input_tokens, output_tokens} from workflow 0
            $table->json('analysis_api_usage')->nullable(); // {model, input_tokens, output_tokens} from workflow 1
            $table->json('generation_api_usage')->nullable(); // {model, input_tokens, output_tokens} from workflow 2

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prompt_runs');
    }
};
