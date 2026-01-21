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
    | Stripe Price IDs for Starter, Pro and Premium tiers across GBP/EUR/USD.
    | Create these in the Stripe Dashboard under Products > Prices.
    | Structure: prices[currency][tier][interval]
    |
    | Example: prices['GBP']['pro']['monthly']
    |
    */

    'prices' => [
        'GBP' => [
            'starter' => [
                'monthly' => env('STRIPE_PRICE_STARTER_MONTHLY_GBP'),
                'yearly' => env('STRIPE_PRICE_STARTER_YEARLY_GBP'),
            ],
            'pro' => [
                'monthly' => env('STRIPE_PRICE_PRO_MONTHLY_GBP'),
                'yearly' => env('STRIPE_PRICE_PRO_YEARLY_GBP'),
            ],
            'premium' => [
                'monthly' => env('STRIPE_PRICE_PREMIUM_MONTHLY_GBP'),
                'yearly' => env('STRIPE_PRICE_PREMIUM_YEARLY_GBP'),
            ],
        ],
        'EUR' => [
            'starter' => [
                'monthly' => env('STRIPE_PRICE_STARTER_MONTHLY_EUR'),
                'yearly' => env('STRIPE_PRICE_STARTER_YEARLY_EUR'),
            ],
            'pro' => [
                'monthly' => env('STRIPE_PRICE_PRO_MONTHLY_EUR'),
                'yearly' => env('STRIPE_PRICE_PRO_YEARLY_EUR'),
            ],
            'premium' => [
                'monthly' => env('STRIPE_PRICE_PREMIUM_MONTHLY_EUR'),
                'yearly' => env('STRIPE_PRICE_PREMIUM_YEARLY_EUR'),
            ],
        ],
        'USD' => [
            'starter' => [
                'monthly' => env('STRIPE_PRICE_STARTER_MONTHLY_USD'),
                'yearly' => env('STRIPE_PRICE_STARTER_YEARLY_USD'),
            ],
            'pro' => [
                'monthly' => env('STRIPE_PRICE_PRO_MONTHLY_USD'),
                'yearly' => env('STRIPE_PRICE_PRO_YEARLY_USD'),
            ],
            'premium' => [
                'monthly' => env('STRIPE_PRICE_PREMIUM_MONTHLY_USD'),
                'yearly' => env('STRIPE_PRICE_PREMIUM_YEARLY_USD'),
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Subscription Tier Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for each subscription tier including prompt limits.
    |
    */

    'tiers' => [
        'free' => [
            'monthly_prompt_limit' => env('FREE_TIER_PROMPT_LIMIT', 10),
        ],
        'starter' => [
            'monthly_prompt_limit' => env('STARTER_TIER_PROMPT_LIMIT', 25),
        ],
        'pro' => [
            'monthly_prompt_limit' => env('PRO_TIER_PROMPT_LIMIT', 90),
        ],
        'premium' => [
            'monthly_prompt_limit' => null, // Unlimited
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Free Tier Configuration (Legacy)
    |--------------------------------------------------------------------------
    |
    | Deprecated: Use tiers.free.monthly_prompt_limit instead.
    |
    */

    'free_tier' => [
        'monthly_prompt_limit' => env('FREE_TIER_PROMPT_LIMIT', 10),
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
