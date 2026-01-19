<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Stripe API Keys
    |--------------------------------------------------------------------------
    |
    | The Stripe publishable key and secret key give you access to Stripe's
    | API. The "publishable" key is typically used when interacting with
    | Stripe.js while the "secret" key accesses private API endpoints.
    |
    */

    'key' => env('STRIPE_PUBLIC'),
    'secret' => env('STRIPE_SECRET'),
    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Stripe Price IDs
    |--------------------------------------------------------------------------
    |
    | Stripe Price IDs for Pro and Private tiers across GBP/EUR/USD.
    | Create these in the Stripe Dashboard under Products > Prices.
    | Structure: prices[currency][tier][interval]
    |
    | Example: prices['GBP']['pro']['monthly']
    |
    */

    'prices' => [
        'GBP' => [
            'pro' => [
                'monthly' => env('STRIPE_PRICE_PRO_MONTHLY_GBP'),
                'yearly' => env('STRIPE_PRICE_PRO_YEARLY_GBP'),
            ],
            'private' => [
                'monthly' => env('STRIPE_PRICE_PRIVATE_MONTHLY_GBP'),
                'yearly' => env('STRIPE_PRICE_PRIVATE_YEARLY_GBP'),
            ],
        ],
        'EUR' => [
            'pro' => [
                'monthly' => env('STRIPE_PRICE_PRO_MONTHLY_EUR'),
                'yearly' => env('STRIPE_PRICE_PRO_YEARLY_EUR'),
            ],
            'private' => [
                'monthly' => env('STRIPE_PRICE_PRIVATE_MONTHLY_EUR'),
                'yearly' => env('STRIPE_PRICE_PRIVATE_YEARLY_EUR'),
            ],
        ],
        'USD' => [
            'pro' => [
                'monthly' => env('STRIPE_PRICE_PRO_MONTHLY_USD'),
                'yearly' => env('STRIPE_PRICE_PRO_YEARLY_USD'),
            ],
            'private' => [
                'monthly' => env('STRIPE_PRICE_PRIVATE_MONTHLY_USD'),
                'yearly' => env('STRIPE_PRICE_PRIVATE_YEARLY_USD'),
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Free Tier Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the free tier limitations.
    |
    */

    'free_tier' => [
        'monthly_prompt_limit' => env('FREE_TIER_PROMPT_LIMIT', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | Stripe Tax
    |--------------------------------------------------------------------------
    |
    | Enable Stripe Tax for automatic tax calculation.
    | Note: This adds £0.50 per transaction when enabled.
    |
    */

    'tax' => [
        'enabled' => env('STRIPE_TAX_ENABLED', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cashier Configuration
    |--------------------------------------------------------------------------
    |
    | Currency and locale settings for Cashier.
    |
    */

    'currency' => env('CASHIER_CURRENCY', 'gbp'),
    'currency_locale' => env('CASHIER_CURRENCY_LOCALE', 'en_GB'),
];
