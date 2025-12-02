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
            // Add structured pre-analysis context (JSON)
            $table->json('pre_analysis_context')->nullable()->after('pre_analysis_answers');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prompt_runs', function (Blueprint $table) {
            $table->dropColumn('pre_analysis_context');
        });
    }
};
