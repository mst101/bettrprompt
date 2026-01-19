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
            $table->string('avatar')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();
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
            ])->nullable(); // 32 personality types (16 base × 2 identities: A=Assertive, T=Turbulent)
            $table->json('trait_percentages')->nullable(); // Store trait percentages
            $table->enum('ui_complexity', ['simple', 'advanced'])->default('advanced');
            $table->enum('question_display_mode', ['one-at-a-time', 'show-all'])
                ->default('one-at-a-time');

            // Geolocation data
            $table->string('country_code', 2)->nullable();
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
            $table->enum('budget_consciousness',
                ['free_only', 'free_first', 'mixed', 'premium_ok', 'enterprise'])->nullable();
            $table->enum('work_mode', ['office', 'hybrid', 'remote', 'freelance'])->nullable();

            // Tool preferences
            $table->json('preferred_tools')->nullable();
            $table->string('primary_programming_language')->nullable();
            $table->unsignedTinyInteger('profile_completion_percentage')->default(0);
            $table->timestamp('profile_last_updated_at')->nullable();

            // Stripe billing
            $table->string('stripe_id')->nullable()->index();
            $table->string('pm_type')->nullable();
            $table->string('pm_last_four', 4)->nullable();
            $table->timestamp('trial_ends_at')->nullable();

            // Subscription tracking
            $table->string('subscription_tier', 20)->default('free');
            $table->timestamp('subscription_ends_at')->nullable();

            // Usage tracking for free tier
            $table->unsignedInteger('monthly_prompt_count')->default(0);
            $table->timestamp('prompt_count_reset_at')->nullable();

            // Privacy encryption fields
            $table->boolean('privacy_enabled')->default(false);
            $table->text('encrypted_dek')->nullable();
            $table->text('recovery_dek')->nullable();
            $table->timestamp('dek_created_at')->nullable();

            // Location prompts
            $table->boolean('location_prompt_dismissed')->default(false);

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
                $table->dropIndex(['stripe_id']);
                $table->dropIndex(['subscription_tier']);
            });
        }

        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
