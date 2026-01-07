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
        Schema::table('users', function (Blueprint $table) {
            // Subscription tracking
            $table->string('subscription_tier', 20)->default('free')->after('trial_ends_at');
            $table->timestamp('subscription_ends_at')->nullable()->after('subscription_tier');

            // Usage tracking for free tier
            $table->unsignedInteger('monthly_prompt_count')->default(0)->after('subscription_ends_at');
            $table->timestamp('prompt_count_reset_at')->nullable()->after('monthly_prompt_count');

            // Index for subscription queries
            $table->index('subscription_tier');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['subscription_tier']);
            $table->dropColumn([
                'subscription_tier',
                'subscription_ends_at',
                'monthly_prompt_count',
                'prompt_count_reset_at',
            ]);
        });
    }
};
