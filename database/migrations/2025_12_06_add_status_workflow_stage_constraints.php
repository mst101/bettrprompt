<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Add constraints to enforce valid status and workflow_stage values.
     * Database-level integrity ensures no invalid values can be inserted
     * regardless of how data is written to the database.
     */
    public function up(): void
    {
        // Get the database driver to use appropriate constraint syntax
        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            // PostgreSQL supports check constraints natively
            DB::statement('ALTER TABLE prompt_runs
                ADD CONSTRAINT check_status_values
                CHECK (status IN (\'pending\', \'processing\', \'completed\', \'failed\'))');

            DB::statement('ALTER TABLE prompt_runs
                ADD CONSTRAINT check_workflow_stage_values
                CHECK (workflow_stage IN (\'submitted\', \'analysis_complete\', \'answering_questions\', \'generating_prompt\', \'completed\', \'failed\'))');
        } elseif ($driver === 'sqlite') {
            // SQLite supports check constraints
            DB::statement('ALTER TABLE prompt_runs
                ADD CONSTRAINT check_status_values
                CHECK (status IN (\'pending\', \'processing\', \'completed\', \'failed\'))');

            DB::statement('ALTER TABLE prompt_runs
                ADD CONSTRAINT check_workflow_stage_values
                CHECK (workflow_stage IN (\'submitted\', \'analysis_complete\', \'answering_questions\', \'generating_prompt\', \'completed\', \'failed\'))');
        }
        // MySQL does not support check constraints well prior to 8.0.16,
        // and even then they don't enforce strictly. Rely on application-level validation.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE prompt_runs DROP CONSTRAINT IF EXISTS check_status_values');
            DB::statement('ALTER TABLE prompt_runs DROP CONSTRAINT IF EXISTS check_workflow_stage_values');
        } elseif ($driver === 'sqlite') {
            // SQLite doesn't support dropping constraints easily
            // This is a limitation of SQLite
        }
    }
};
