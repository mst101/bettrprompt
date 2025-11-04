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
            $table->string('selected_framework')->nullable()->after('task_description');
            $table->text('framework_reasoning')->nullable()->after('selected_framework');
            $table->json('framework_questions')->nullable()->after('framework_reasoning');
            $table->json('clarifying_answers')->nullable()->after('framework_questions');
            $table->string('workflow_stage')->default('submitted')->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prompt_runs', function (Blueprint $table) {
            $table->dropColumn([
                'workflow_stage',
                'clarifying_answers',
                'framework_questions',
                'framework_reasoning',
                'selected_framework',
            ]);
        });
    }
};
