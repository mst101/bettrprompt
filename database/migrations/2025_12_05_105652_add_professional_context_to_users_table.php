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
            $table->string('job_title')->nullable()->after('language_manually_set');
            $table->string('industry')->nullable()->after('job_title');
            $table->enum('experience_level', ['entry', 'mid', 'senior', 'expert'])->nullable()->after('industry');
            $table->enum('company_size', ['solo', 'small', 'medium', 'large', 'enterprise'])->nullable()->after('experience_level');

            // Indexes for common queries
            $table->index('industry');
            $table->index('experience_level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['industry']);
            $table->dropIndex(['experience_level']);
            $table->dropColumn([
                'job_title',
                'industry',
                'experience_level',
                'company_size',
            ]);
        });
    }
};
