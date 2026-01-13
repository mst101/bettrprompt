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
        Schema::create('visitors', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');

            // Attribution tracking
            $table->string('utm_source')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->nullable();
            $table->string('utm_term')->nullable();
            $table->string('utm_content')->nullable();
            $table->string('referrer')->nullable();
            $table->string('landing_page')->nullable();

            // Device/Browser information
            $table->string('user_agent')->nullable();
            $table->ipAddress('ip_address')->nullable();

            // Visit tracking
            $table->timestamp('first_visit_at');
            $table->timestamp('last_visit_at');
            $table->integer('visit_count')->default(1);
            $table->timestamp('converted_at')->nullable(); // When user_id was set
            $table->foreignId('referred_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->index('referred_by_user_id');

            // Personality data (before conversion to user)
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
            $table->json('trait_percentages')->nullable();
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

            $table->timestamps();

            // Indexes for performance
            $table->index(['user_id', 'first_visit_at']);
            $table->index('converted_at');
            $table->index('country_code');
            $table->index('timezone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('visitors')) {
            Schema::table('visitors', function (Blueprint $table) {
                $table->dropIndex(['user_id', 'first_visit_at']);
                $table->dropIndex(['converted_at']);
                $table->dropIndex(['country_code']);
                $table->dropIndex(['timezone']);
                $table->dropIndex(['referred_by_user_id']);
            });
        }

        Schema::dropIfExists('visitors');
    }
};
