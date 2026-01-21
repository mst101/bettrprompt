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
        // 1. Remove old 'private' tier prices
        DB::table('prices')->where('tier', 'private')->delete();

        // 2. Update existing 'pro' tier prices to new amounts
        $proPrices = [
            ['currency_code' => 'GBP', 'interval' => 'monthly', 'amount' => 24.99],
            ['currency_code' => 'GBP', 'interval' => 'yearly', 'amount' => 249.00],
            ['currency_code' => 'USD', 'interval' => 'monthly', 'amount' => 27.99],
            ['currency_code' => 'USD', 'interval' => 'yearly', 'amount' => 279.00],
            ['currency_code' => 'EUR', 'interval' => 'monthly', 'amount' => 29.99],
            ['currency_code' => 'EUR', 'interval' => 'yearly', 'amount' => 299.00],
        ];

        foreach ($proPrices as $price) {
            DB::table('prices')
                ->where('tier', 'pro')
                ->where('currency_code', $price['currency_code'])
                ->where('interval', $price['interval'])
                ->update(['amount' => $price['amount']]);
        }

        // 3. Insert new 'starter' tier prices
        $starterPrices = [
            ['tier' => 'starter', 'currency_code' => 'GBP', 'interval' => 'monthly', 'amount' => 9.99, 'stripe_price_id' => env('STRIPE_PRICE_STARTER_MONTHLY_GBP')],
            ['tier' => 'starter', 'currency_code' => 'GBP', 'interval' => 'yearly', 'amount' => 99.00, 'stripe_price_id' => env('STRIPE_PRICE_STARTER_YEARLY_GBP')],
            ['tier' => 'starter', 'currency_code' => 'USD', 'interval' => 'monthly', 'amount' => 11.99, 'stripe_price_id' => env('STRIPE_PRICE_STARTER_MONTHLY_USD')],
            ['tier' => 'starter', 'currency_code' => 'USD', 'interval' => 'yearly', 'amount' => 119.00, 'stripe_price_id' => env('STRIPE_PRICE_STARTER_YEARLY_USD')],
            ['tier' => 'starter', 'currency_code' => 'EUR', 'interval' => 'monthly', 'amount' => 11.99, 'stripe_price_id' => env('STRIPE_PRICE_STARTER_MONTHLY_EUR')],
            ['tier' => 'starter', 'currency_code' => 'EUR', 'interval' => 'yearly', 'amount' => 119.00, 'stripe_price_id' => env('STRIPE_PRICE_STARTER_YEARLY_EUR')],
        ];

        foreach ($starterPrices as $price) {
            DB::table('prices')->insert($price + ['created_at' => now(), 'updated_at' => now()]);
        }

        // 4. Insert new 'premium' tier prices (renamed from 'private')
        $premiumPrices = [
            ['tier' => 'premium', 'currency_code' => 'GBP', 'interval' => 'monthly', 'amount' => 49.99, 'stripe_price_id' => env('STRIPE_PRICE_PREMIUM_MONTHLY_GBP')],
            ['tier' => 'premium', 'currency_code' => 'GBP', 'interval' => 'yearly', 'amount' => 499.00, 'stripe_price_id' => env('STRIPE_PRICE_PREMIUM_YEARLY_GBP')],
            ['tier' => 'premium', 'currency_code' => 'USD', 'interval' => 'monthly', 'amount' => 54.99, 'stripe_price_id' => env('STRIPE_PRICE_PREMIUM_MONTHLY_USD')],
            ['tier' => 'premium', 'currency_code' => 'USD', 'interval' => 'yearly', 'amount' => 549.00, 'stripe_price_id' => env('STRIPE_PRICE_PREMIUM_YEARLY_USD')],
            ['tier' => 'premium', 'currency_code' => 'EUR', 'interval' => 'monthly', 'amount' => 59.99, 'stripe_price_id' => env('STRIPE_PRICE_PREMIUM_MONTHLY_EUR')],
            ['tier' => 'premium', 'currency_code' => 'EUR', 'interval' => 'yearly', 'amount' => 599.00, 'stripe_price_id' => env('STRIPE_PRICE_PREMIUM_YEARLY_EUR')],
        ];

        foreach ($premiumPrices as $price) {
            DB::table('prices')->insert($price + ['created_at' => now(), 'updated_at' => now()]);
        }

        // 5. Migrate existing 'private' tier users to 'premium'
        DB::table('users')
            ->where('subscription_tier', 'private')
            ->update(['subscription_tier' => 'premium']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Remove new 'starter' tier prices
        DB::table('prices')->where('tier', 'starter')->delete();

        // 2. Remove new 'premium' tier prices
        DB::table('prices')->where('tier', 'premium')->delete();

        // 3. Restore old 'pro' tier prices
        $oldProPrices = [
            ['currency_code' => 'GBP', 'interval' => 'monthly', 'amount' => 12.00],
            ['currency_code' => 'GBP', 'interval' => 'yearly', 'amount' => 120.00],
            ['currency_code' => 'USD', 'interval' => 'monthly', 'amount' => 14.99],
            ['currency_code' => 'USD', 'interval' => 'yearly', 'amount' => 149.00],
            ['currency_code' => 'EUR', 'interval' => 'monthly', 'amount' => 14.99],
            ['currency_code' => 'EUR', 'interval' => 'yearly', 'amount' => 149.00],
        ];

        foreach ($oldProPrices as $price) {
            DB::table('prices')
                ->where('tier', 'pro')
                ->where('currency_code', $price['currency_code'])
                ->where('interval', $price['interval'])
                ->update(['amount' => $price['amount']]);
        }

        // 4. Restore old 'private' tier prices
        $privatePrice = env('STRIPE_PRICE_PRIVATE_MONTHLY_GBP');
        $privatePrices = [
            ['tier' => 'private', 'currency_code' => 'GBP', 'interval' => 'monthly', 'amount' => 20.00, 'stripe_price_id' => $privatePrice],
            ['tier' => 'private', 'currency_code' => 'GBP', 'interval' => 'yearly', 'amount' => 200.00, 'stripe_price_id' => env('STRIPE_PRICE_PRIVATE_YEARLY_GBP')],
            ['tier' => 'private', 'currency_code' => 'USD', 'interval' => 'monthly', 'amount' => 24.99, 'stripe_price_id' => env('STRIPE_PRICE_PRIVATE_MONTHLY_USD')],
            ['tier' => 'private', 'currency_code' => 'USD', 'interval' => 'yearly', 'amount' => 249.00, 'stripe_price_id' => env('STRIPE_PRICE_PRIVATE_YEARLY_USD')],
            ['tier' => 'private', 'currency_code' => 'EUR', 'interval' => 'monthly', 'amount' => 24.99, 'stripe_price_id' => env('STRIPE_PRICE_PRIVATE_MONTHLY_EUR')],
            ['tier' => 'private', 'currency_code' => 'EUR', 'interval' => 'yearly', 'amount' => 249.00, 'stripe_price_id' => env('STRIPE_PRICE_PRIVATE_YEARLY_EUR')],
        ];

        foreach ($privatePrices as $price) {
            DB::table('prices')->insert($price + ['created_at' => now(), 'updated_at' => now()]);
        }

        // 5. Migrate 'premium' tier users back to 'private'
        DB::table('users')
            ->where('subscription_tier', 'premium')
            ->update(['subscription_tier' => 'private']);
    }
};
