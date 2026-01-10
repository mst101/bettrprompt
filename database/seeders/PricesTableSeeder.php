<?php

namespace Database\Seeders;

use App\Models\Price;
use Illuminate\Database\Seeder;

class PricesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Price data: currency_code, tier, interval, amount (in decimal), stripe_price_id
        // These will be populated from environment variables when the stripe_price_id is available
        $prices = [
            // GBP Prices
            ['currency_code' => 'GBP', 'tier' => 'pro', 'interval' => 'monthly', 'amount' => 12.00, 'stripe_price_id' => env('STRIPE_PRICE_PRO_MONTHLY_GBP', 'price_gbp_pro_monthly')],
            ['currency_code' => 'GBP', 'tier' => 'pro', 'interval' => 'yearly', 'amount' => 120.00, 'stripe_price_id' => env('STRIPE_PRICE_PRO_YEARLY_GBP', 'price_gbp_pro_yearly')],
            ['currency_code' => 'GBP', 'tier' => 'private', 'interval' => 'monthly', 'amount' => 20.00, 'stripe_price_id' => env('STRIPE_PRICE_PRIVATE_MONTHLY_GBP', 'price_gbp_private_monthly')],
            ['currency_code' => 'GBP', 'tier' => 'private', 'interval' => 'yearly', 'amount' => 200.00, 'stripe_price_id' => env('STRIPE_PRICE_PRIVATE_YEARLY_GBP', 'price_gbp_private_yearly')],

            // EUR Prices
            ['currency_code' => 'EUR', 'tier' => 'pro', 'interval' => 'monthly', 'amount' => 13.99, 'stripe_price_id' => env('STRIPE_PRICE_PRO_MONTHLY_EUR', 'price_eur_pro_monthly')],
            ['currency_code' => 'EUR', 'tier' => 'pro', 'interval' => 'yearly', 'amount' => 139.00, 'stripe_price_id' => env('STRIPE_PRICE_PRO_YEARLY_EUR', 'price_eur_pro_yearly')],
            ['currency_code' => 'EUR', 'tier' => 'private', 'interval' => 'monthly', 'amount' => 22.99, 'stripe_price_id' => env('STRIPE_PRICE_PRIVATE_MONTHLY_EUR', 'price_eur_private_monthly')],
            ['currency_code' => 'EUR', 'tier' => 'private', 'interval' => 'yearly', 'amount' => 229.00, 'stripe_price_id' => env('STRIPE_PRICE_PRIVATE_YEARLY_EUR', 'price_eur_private_yearly')],

            // USD Prices
            ['currency_code' => 'USD', 'tier' => 'pro', 'interval' => 'monthly', 'amount' => 15.99, 'stripe_price_id' => env('STRIPE_PRICE_PRO_MONTHLY_USD', 'price_usd_pro_monthly')],
            ['currency_code' => 'USD', 'tier' => 'pro', 'interval' => 'yearly', 'amount' => 159.00, 'stripe_price_id' => env('STRIPE_PRICE_PRO_YEARLY_USD', 'price_usd_pro_yearly')],
            ['currency_code' => 'USD', 'tier' => 'private', 'interval' => 'monthly', 'amount' => 26.99, 'stripe_price_id' => env('STRIPE_PRICE_PRIVATE_MONTHLY_USD', 'price_usd_private_monthly')],
            ['currency_code' => 'USD', 'tier' => 'private', 'interval' => 'yearly', 'amount' => 269.00, 'stripe_price_id' => env('STRIPE_PRICE_PRIVATE_YEARLY_USD', 'price_usd_private_yearly')],
        ];

        // Delete existing prices to avoid duplicates
        Price::truncate();

        // Insert prices
        foreach ($prices as $price) {
            Price::create($price);
        }
    }
}
