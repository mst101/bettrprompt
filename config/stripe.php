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

    'key' => env('STRIPE_KEY'),
    'secret' => env('STRIPE_SECRET'),
    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Stripe Price IDs
    |--------------------------------------------------------------------------
    |
    | These are the Stripe Price IDs for the Pro subscription plan.
    | Create these in the Stripe Dashboard under Products > Prices.
    |
    */

    'prices' => [
        'monthly' => env('STRIPE_PRICE_MONTHLY'),
        'yearly' => env('STRIPE_PRICE_YEARLY'),
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
