<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Experiments: core definitions
        Schema::create('experiments', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 100)->unique(); // URL-safe identifier
            $table->string('name', 200);
            $table->text('description')->nullable();
            $table->text('hypothesis')->nullable();

            // Status
            $table->enum('status', ['draft', 'running', 'paused', 'completed', 'archived'])
                ->default('draft')->index();

            // Timing
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->unsignedInteger('minimum_runtime_hours')->default(168); // 1 week

            // Goal
            $table->string('goal_event', 100); // e.g., 'subscription_success'
            $table->string('goal_type', 50)->default('conversion'); // conversion, revenue, engagement

            // Targeting (JSON rules, null = all visitors)
            $table->json('targeting_rules')->nullable();

            // Traffic allocation
            $table->unsignedTinyInteger('traffic_percentage')->default(100); // 0-100

            // Statistical settings
            $table->unsignedInteger('minimum_sample_size')->nullable();
            $table->decimal('minimum_detectable_effect', 5, 2)->nullable(); // e.g., 0.05 = 5%

            // Winner
            $table->foreignId('winner_variant_id')->nullable();
            $table->timestamp('winner_declared_at')->nullable();

            // Metadata
            $table->boolean('is_personality_research')->default(false);
            $table->string('personality_hypothesis', 500)->nullable();

            $table->timestamps();

            $table->index(['status', 'started_at']);
        });

        // Experiment variants: treatment groups
        Schema::create('experiment_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('experiment_id')->constrained()->cascadeOnDelete();

            $table->string('slug', 100); // e.g., 'control', 'variant_a'
            $table->string('name', 200);
            $table->text('description')->nullable();

            $table->boolean('is_control')->default(false);
            $table->unsignedTinyInteger('weight')->default(50); // Relative weight for allocation

            // Variant-specific configuration (JSON)
            $table->json('config')->nullable();

            $table->timestamps();

            $table->unique(['experiment_id', 'slug']);
            $table->index(['experiment_id', 'is_control']);
        });

        // Experiment assignments: bucketing records
        Schema::create('experiment_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('experiment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variant_id')->constrained('experiment_variants')->cascadeOnDelete();

            // Identity (visitor_id is the canonical bucketing key)
            $table->uuid('visitor_id')->index();
            $table->foreignId('user_id')->nullable()->index(); // For convenience, not bucketing

            // Assignment metadata
            $table->timestamp('assigned_at');
            $table->json('segment_snapshot')->nullable(); // Targeting context at assignment time

            $table->timestamps();

            // One assignment per visitor per experiment
            $table->unique(['experiment_id', 'visitor_id']);
            $table->index(['visitor_id', 'assigned_at']);
        });

        // Experiment exposures: when variant is actually rendered
        Schema::create('experiment_exposures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('experiment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variant_id')->constrained('experiment_variants')->cascadeOnDelete();
            $table->foreignId('assignment_id')->constrained('experiment_assignments')->cascadeOnDelete();

            // Identity
            $table->uuid('visitor_id')->index();
            $table->foreignId('user_id')->nullable();
            $table->uuid('session_id')->nullable();

            // Context
            $table->string('page_path', 255)->nullable();
            $table->string('component', 100)->nullable(); // Which component rendered the variant

            $table->timestamp('occurred_at')->index();
            $table->timestamps();

            // Indexes for attribution queries
            $table->index(['experiment_id', 'visitor_id', 'occurred_at']);
            $table->index(['visitor_id', 'occurred_at']);
        });

        // Many-to-many: analytics events ↔ experiments (supports overlapping experiments)
        Schema::create('analytics_event_experiments', function (Blueprint $table) {
            $table->id();
            $table->uuid('event_id'); // References analytics_events.event_id
            $table->foreignId('experiment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variant_id')->constrained('experiment_variants')->cascadeOnDelete();
            $table->foreignId('exposure_id')->nullable()
                ->constrained('experiment_exposures')->nullOnDelete();

            $table->timestamps();

            // Prevent duplicate attribution
            $table->unique(['event_id', 'experiment_id']);

            // Query indexes
            $table->index(['experiment_id', 'variant_id']);

            // Foreign key to events (not constrained for performance)
            $table->foreign('event_id')->references('event_id')->on('analytics_events')
                ->cascadeOnDelete();
        });

        // Experiment conversions: aggregation for fast stats
        Schema::create('experiment_conversions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('experiment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variant_id')->constrained('experiment_variants')->cascadeOnDelete();

            // Counts (updated by processor)
            $table->unsignedInteger('exposures')->default(0);
            $table->unsignedInteger('conversions')->default(0);
            $table->unsignedInteger('unique_visitors_exposed')->default(0);
            $table->unsignedInteger('unique_visitors_converted')->default(0);

            // Revenue (if goal_type = revenue)
            $table->decimal('total_revenue', 12, 2)->default(0);

            // Derived (updated by processor)
            $table->decimal('conversion_rate', 8, 6)->nullable();
            $table->decimal('revenue_per_visitor', 10, 4)->nullable();

            $table->timestamps();

            $table->unique(['experiment_id', 'variant_id']);
        });

        // Exclusion groups: prevent mutually-exclusive experiments
        Schema::create('experiment_exclusion_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Exclusion group memberships
        Schema::create('experiment_exclusion_group_members', function (Blueprint $table) {
            $table->foreignId('exclusion_group_id')
                ->constrained('experiment_exclusion_groups')->cascadeOnDelete();
            $table->foreignId('experiment_id')->constrained()->cascadeOnDelete();
            $table->primary(['exclusion_group_id', 'experiment_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('experiment_exclusion_group_members');
        Schema::dropIfExists('experiment_exclusion_groups');
        Schema::dropIfExists('analytics_event_experiments');
        Schema::dropIfExists('experiment_conversions');
        Schema::dropIfExists('experiment_exposures');
        Schema::dropIfExists('experiment_assignments');
        Schema::dropIfExists('experiment_variants');
        Schema::dropIfExists('experiments');
    }
};
