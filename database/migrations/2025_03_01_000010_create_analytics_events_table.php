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
        Schema::create('analytics_events', function (Blueprint $table) {
            // Primary key
            $table->uuid('event_id')->primary(); // Client-generated, idempotency key

            // Event identification
            $table->string('name', 100)->index(); // e.g., 'subscription_success', 'prompt_completed'
            $table->string('type', 50)->index(); // Derived category: 'conversion', 'engagement', 'exposure'
            $table->json('properties')->nullable(); // Event-specific data

            // Identity (server-derived, never from client)
            $table->uuid('visitor_id')->nullable()->index();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->uuid('session_id')->nullable()->index(); // analytics_session_id from header

            // Source
            $table->enum('source', ['client', 'server'])->default('client');

            // Context (denormalised for query performance)
            $table->string('page_path', 255)->nullable();
            $table->string('referrer', 500)->nullable();
            $table->string('device_type', 20)->nullable(); // desktop, mobile, tablet
            $table->string('country_code', 2)->nullable();

            // Prompt context (when applicable)
            $table->foreignId('prompt_run_id')->nullable()->constrained()->cascadeOnDelete();

            // Timestamps
            $table->timestamp('occurred_at')->index(); // When event happened (client time)

            // Query indexes
            $table->index(['visitor_id', 'occurred_at']);
            $table->index(['name', 'occurred_at']);
            $table->index(['type', 'occurred_at']);
            $table->index(['session_id', 'occurred_at']);

            $table->foreign('visitor_id')
                ->references('id')
                ->on('visitors')
                ->nullOnDelete();

            $table->foreign('session_id')
                ->references('id')
                ->on('analytics_sessions')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analytics_events');
    }
};
