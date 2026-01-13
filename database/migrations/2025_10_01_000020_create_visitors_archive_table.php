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
        Schema::create('visitors_archive', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('utm_source')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->nullable();
            $table->string('utm_term')->nullable();
            $table->string('utm_content')->nullable();
            $table->text('referrer')->nullable();
            $table->text('landing_page')->nullable();
            $table->text('user_agent')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamp('first_visit_at')->nullable();
            $table->timestamp('last_visit_at')->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->uuid('referred_by_user_id')->nullable();
            $table->string('personality_type')->nullable();
            $table->json('trait_percentages')->nullable();
            $table->string('ui_complexity')->nullable();
            $table->integer('ui_step_number')->nullable();
            $table->string('country_code', 2)->nullable();
            $table->string('country_name')->nullable();
            $table->string('region')->nullable();
            $table->string('city')->nullable();
            $table->string('timezone')->nullable();
            $table->string('currency_code', 3)->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('language_code')->nullable();
            $table->timestamp('location_detected_at')->nullable();
            $table->timestamps();

            // Archive metadata
            $table->timestamp('archived_at')->useCurrent();
            $table->string('archive_tier'); // 'tier_1' or 'tier_2'
            $table->text('archive_reason')->nullable();

            // Indexes for queries
            $table->index('archived_at');
            $table->index('user_id');
            $table->index('archive_tier');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visitors_archive');
    }
};
