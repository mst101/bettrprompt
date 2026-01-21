<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Migrate existing 'private' tier users to 'premium'
        // Price data is handled by PricesTableSeeder, not in migrations
        DB::table('users')
            ->where('subscription_tier', 'private')
            ->update(['subscription_tier' => 'premium']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Migrate 'premium' tier users back to 'private'
        // Price data reversal is handled by PricesTableSeeder rollback
        DB::table('users')
            ->where('subscription_tier', 'premium')
            ->update(['subscription_tier' => 'private']);
    }
};
