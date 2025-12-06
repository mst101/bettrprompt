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
                'INTJ-A', 'INTJ-T', 'INTP-A', 'INTP-T',
                'ENTJ-A', 'ENTJ-T', 'ENTP-A', 'ENTP-T',
                'INFJ-A', 'INFJ-T', 'INFP-A', 'INFP-T',
                'ENFJ-A', 'ENFJ-T', 'ENFP-A', 'ENFP-T',
            ])->nullable();
            $table->json('trait_percentages')->nullable();
            $table->enum('ui_complexity', ['simple', 'advanced'])->default('simple');

            $table->timestamps();

            // Indexes for performance
            $table->index(['user_id', 'first_visit_at']);
            $table->index('converted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visitors');
    }
};
