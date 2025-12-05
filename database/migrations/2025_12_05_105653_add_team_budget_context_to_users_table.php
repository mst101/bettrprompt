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
        Schema::table('users', function (Blueprint $table) {
            $table->enum('team_size', ['solo', 'small', 'medium', 'large'])->nullable()->after('company_size');
            $table->enum('team_role', ['individual', 'lead', 'manager', 'director', 'executive'])->nullable()->after('team_size');
            $table->enum('budget_consciousness', ['free_only', 'free_first', 'mixed', 'premium_ok', 'enterprise'])->nullable()->after('team_role');
            $table->enum('work_mode', ['office', 'hybrid', 'remote', 'freelance'])->nullable()->after('budget_consciousness');

            // Indexes for common queries
            $table->index('team_role');
            $table->index('budget_consciousness');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['team_role']);
            $table->dropIndex(['budget_consciousness']);
            $table->dropColumn([
                'team_size',
                'team_role',
                'budget_consciousness',
                'work_mode',
            ]);
        });
    }
};
