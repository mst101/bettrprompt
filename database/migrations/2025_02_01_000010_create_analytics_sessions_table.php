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
        Schema::create('analytics_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary(); // The analytics_session_id

            // Identity
            $table->uuid('visitor_id')->nullable()->index();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            // Timing
            $table->timestamp('started_at')->index();
            $table->timestamp('ended_at')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();

            // Navigation
            $table->string('entry_page', 255)->nullable();
            $table->string('exit_page', 255)->nullable();
            $table->unsignedSmallInteger('page_count')->default(0);
            $table->unsignedSmallInteger('event_count')->default(0);

            // Attribution (captured at session start)
            $table->string('utm_source', 100)->nullable();
            $table->string('utm_medium', 100)->nullable();
            $table->string('utm_campaign', 100)->nullable();
            $table->string('utm_term', 100)->nullable();
            $table->string('utm_content', 100)->nullable();
            $table->string('referrer', 500)->nullable();

            // Device (captured at session start)
            $table->string('device_type', 20)->nullable();

            // Outcomes
            $table->boolean('is_bounce')->default(true); // False after 2nd page view
            $table->boolean('converted')->default(false);
            $table->string('conversion_type', 50)->nullable(); // registered, subscribed_pro, etc.

            // Prompt activity
            $table->unsignedSmallInteger('prompts_started')->default(0);
            $table->unsignedSmallInteger('prompts_completed')->default(0);

            $table->timestamps();

            // Indexes
            $table->index(['visitor_id', 'started_at']);
            $table->index('ended_at');
            $table->index(['converted', 'started_at']);

            $table->foreign('visitor_id')
                ->references('id')
                ->on('visitors')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analytics_sessions');
    }
};
