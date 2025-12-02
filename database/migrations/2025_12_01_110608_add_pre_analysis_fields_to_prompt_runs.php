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
        Schema::table('prompt_runs', function (Blueprint $table) {
            $table->json('pre_analysis_questions')->nullable()->after('task_description');
            $table->json('pre_analysis_answers')->nullable()->after('pre_analysis_questions');
            $table->text('pre_analysis_reasoning')->nullable()->after('pre_analysis_answers');
            $table->boolean('pre_analysis_skipped')->default(false)->after('pre_analysis_reasoning');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prompt_runs', function (Blueprint $table) {
            $table->dropColumn([
                'pre_analysis_questions',
                'pre_analysis_answers',
                'pre_analysis_reasoning',
                'pre_analysis_skipped',
            ]);
        });
    }
};
