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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('google_id')->nullable()->unique();
            $table->string('name');
            $table->string('email')->unique();
            $table->boolean('is_admin')->default(false);
            $table->string('referral_code', 10)->unique()->nullable();
            $table->foreignId('referred_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->index('referred_by_user_id');
            $table->string('avatar')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();
            $table->enum('personality_type', [
                'INTJ-A', 'INTJ-T', 'INTP-A', 'INTP-T',
                'ENTJ-A', 'ENTJ-T', 'ENTP-A', 'ENTP-T',
                'INFJ-A', 'INFJ-T', 'INFP-A', 'INFP-T',
                'ENFJ-A', 'ENFJ-T', 'ENFP-A', 'ENFP-T',
            ])->nullable(); // e.g., INTJ-A, ENFP-T (A=Assertive, T=Turbulent)
            $table->json('trait_percentages')->nullable(); // Store trait percentages
            $table->enum('ui_complexity', ['simple', 'advanced'])->default('advanced');

            // Geolocation data
            $table->string('country_code', 2)->nullable();
            $table->string('country_name')->nullable();
            $table->string('region')->nullable();
            $table->string('city')->nullable();
            $table->string('timezone')->nullable();
            $table->string('currency_code', 3)->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('language_code', 5)->nullable();
            $table->timestamp('location_detected_at')->nullable();
            $table->boolean('location_manually_set')->default(false);
            $table->boolean('language_manually_set')->default(false);

            // Professional context
            $table->string('job_title')->nullable();
            $table->string('industry')->nullable();
            $table->enum('experience_level', ['entry', 'mid', 'senior', 'expert'])->nullable();
            $table->enum('company_size', ['solo', 'small', 'medium', 'large', 'enterprise'])->nullable();

            // Team and budget context
            $table->enum('team_size', ['solo', 'small', 'medium', 'large'])->nullable();
            $table->enum('team_role', ['individual', 'lead', 'manager', 'director', 'executive'])->nullable();
            $table->enum('budget_consciousness', ['free_only', 'free_first', 'mixed', 'premium_ok', 'enterprise'])->nullable();
            $table->enum('work_mode', ['office', 'hybrid', 'remote', 'freelance'])->nullable();

            // Tool preferences
            $table->json('preferred_tools')->nullable();
            $table->string('primary_programming_language')->nullable();
            $table->unsignedTinyInteger('profile_completion_percentage')->default(0);
            $table->timestamp('profile_last_updated_at')->nullable();

            $table->rememberToken();
            $table->timestamps();

            // Indexes for common queries
            $table->index('country_code');
            $table->index('timezone');
            $table->index('industry');
            $table->index('experience_level');
            $table->index('team_role');
            $table->index('budget_consciousness');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropIndex(['country_code']);
                $table->dropIndex(['timezone']);
                $table->dropIndex(['industry']);
                $table->dropIndex(['experience_level']);
                $table->dropIndex(['team_role']);
                $table->dropIndex(['budget_consciousness']);
                $table->dropIndex(['referred_by_user_id']);
            });
        }

        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
