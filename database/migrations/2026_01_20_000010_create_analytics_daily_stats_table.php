<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_daily_stats', function (Blueprint $table) {
            $table->id();
            $table->date('date')->index();

            // Traffic metrics
            $table->unsignedInteger('unique_visitors')->default(0);
            $table->unsignedInteger('total_sessions')->default(0);
            $table->unsignedInteger('total_page_views')->default(0);
            $table->decimal('avg_session_duration_seconds', 10, 2)->nullable();
            $table->decimal('bounce_rate', 5, 4)->nullable();

            // Conversion metrics
            $table->unsignedInteger('registrations')->default(0);
            $table->unsignedInteger('subscriptions_free')->default(0);
            $table->unsignedInteger('subscriptions_pro')->default(0);
            $table->unsignedInteger('subscriptions_private')->default(0);
            $table->decimal('total_revenue_usd', 12, 2)->default(0);

            // Prompt metrics
            $table->unsignedInteger('prompts_started')->default(0);
            $table->unsignedInteger('prompts_completed')->default(0);
            $table->decimal('prompt_completion_rate', 5, 4)->nullable();
            $table->decimal('avg_prompt_rating', 3, 2)->nullable();

            // Dimensional breakdowns (JSON)
            $table->json('by_utm_source')->nullable();
            $table->json('by_country')->nullable();
            $table->json('by_device_type')->nullable();

            $table->timestamps();

            $table->unique('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_daily_stats');
    }
};
