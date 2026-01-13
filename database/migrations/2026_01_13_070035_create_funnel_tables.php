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
        // Funnels
        Schema::create('funnels', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('attribution_window_days')->default(30);
            $table->timestamps();
        });

        // Funnel Stages
        Schema::create('funnel_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('funnel_id')->constrained('funnels')->cascadeOnDelete();
            $table->integer('order');
            $table->string('name');
            $table->string('event_name');
            $table->json('event_conditions')->nullable(); // For filtering (e.g., tier filter)
            $table->timestamps();

            $table->unique(['funnel_id', 'order']);
            $table->index('funnel_id');
        });

        // Funnel Progress
        Schema::create('funnel_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('funnel_id')->constrained('funnels')->cascadeOnDelete();
            $table->uuid('visitor_id');
            $table->integer('stage')->default(1); // Current stage (1, 2, 3, 4)
            $table->json('stage_timestamps')->nullable(); // {1: timestamp, 2: timestamp, ...}
            $table->timestamp('conversion_date')->nullable(); // When fully converted
            $table->boolean('is_converted')->default(false);
            $table->timestamps();

            $table->index(['funnel_id', 'visitor_id']);
            $table->index(['funnel_id', 'is_converted']);
            $table->index('conversion_date');
        });

        // Funnel Daily Stats
        Schema::create('funnel_daily_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('funnel_id')->constrained('funnels')->cascadeOnDelete();
            $table->date('date');
            $table->integer('stage');
            $table->integer('starts')->default(0); // Number starting at this stage
            $table->integer('conversions')->default(0); // Number progressing to next
            $table->decimal('conversion_rate', 8, 2)->default(0); // Percentage
            $table->timestamps();

            $table->unique(['funnel_id', 'date', 'stage']);
            $table->index(['funnel_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('funnel_daily_stats');
        Schema::dropIfExists('funnel_progress');
        Schema::dropIfExists('funnel_stages');
        Schema::dropIfExists('funnels');
    }
};
