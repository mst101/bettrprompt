<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PricesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Price data: currency_code, tier, interval, amount (in decimal), stripe_price_id
        // Stripe Price IDs are fetched from config/stripe.php which reads from environment
        // In test environments or when not configured, use placeholder IDs
        $stripePrices = config('stripe.prices');

        $prices = [];

        // Build prices from config
        foreach ($stripePrices as $currency => $tiers) {
            foreach ($tiers as $tier => $intervals) {
                foreach ($intervals as $interval => $priceId) {
                    // Use placeholder if price ID is null (not configured in environment)
                    if (! $priceId) {
                        $priceId = "price_{$this->sanitizeForId($currency)}_{$tier}_{$interval}";
                    }

                    $prices[] = [
                        'currency_code' => $currency,
                        'tier' => $tier,
                        'interval' => $interval,
                        'amount' => $this->getAmount($currency, $tier, $interval),
                        'stripe_price_id' => $priceId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
        }

        // Delete existing prices to avoid duplicates
        DB::table('prices')->truncate();

        // Insert prices
        DB::table('prices')->insert($prices);
    }

    /**
     * Sanitize currency code for use in price ID
     */
    private function sanitizeForId(string $currency): string
    {
        return strtolower($currency);
    }

    /**
     * Get the amount for a given currency, tier, and interval
     */
    private function getAmount(string $currency, string $tier, string $interval): float
    {
        $amounts = [
            'GBP' => [
                'pro' => ['monthly' => 12.00, 'yearly' => 120.00],
                'private' => ['monthly' => 20.00, 'yearly' => 200.00],
            ],
            'EUR' => [
                'pro' => ['monthly' => 13.99, 'yearly' => 139.00],
                'private' => ['monthly' => 22.99, 'yearly' => 229.00],
            ],
            'USD' => [
                'pro' => ['monthly' => 15.99, 'yearly' => 159.00],
                'private' => ['monthly' => 26.99, 'yearly' => 269.00],
            ],
        ];

        return $amounts[$currency][$tier][$interval] ?? 0;
    }
}
