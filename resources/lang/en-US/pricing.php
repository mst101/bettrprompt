<?php

return [
    // Page metadata
    'pageTitle' => 'Pricing - BettrPrompt',
    'title' => 'Simple, transparent pricing',
    'tagline' => 'Start free, upgrade when you need more',

    // Currency display
    'currency' => '£',

    // Tier names and CTAs
    'free' => [
        'name' => 'Free',
        'price' => '0',
        'priceMonthly' => 'Free',
        'cta' => 'Get Started',
    ],
    'pro' => [
        'name' => 'Pro',
        'priceMonthly' => '12',
        'priceYearly' => '120',
        'cta' => 'Start Pro',
        'yearlySavings' => 'Save {percent}% when billed annually – just {amount}/{period}',
    ],
    'private' => [
        'name' => 'Private',
        'priceMonthly' => '20',
        'priceYearly' => '200',
        'cta' => 'Start Private',
        'yearlySavings' => 'Save {percent}% when billed annually – just {amount}/{period}',
    ],

    // Billing period labels
    'billing' => [
        'monthly' => 'Monthly',
        'yearly' => 'Annual',
    ],

    'period' => [
        'month' => 'month',
        'year' => 'year',
    ],

    // Feature descriptions
    'features' => [
        'free' => [
            'limit' => '10 prompts per month',
            'calibration' => 'Personality calibration',
            'optimization' => 'Basic prompt optimisation',
        ],
        'pro' => [
            'unlimited' => 'Unlimited prompts',
            'calibration' => 'Personality calibration',
            'optimization' => 'Advanced prompt optimisation',
            'history' => 'Prompt history',
        ],
        'private' => [
            'unlimited' => 'Unlimited prompts',
            'calibration' => 'Personality calibration',
            'optimization' => 'Advanced prompt optimisation',
            'mode' => 'Private mode (restricted access)',
            'support' => 'Priority support',
            'history' => 'Prompt history',
        ],
        'privacy' => 'Private mode',
    ],

    // Badges
    'popularBadge' => 'Most Popular',

    // Action labels
    'actions' => [
        'processing' => 'Processing...',
        'subscribe' => 'Subscribe',
    ],

    // FAQ section
    'faq' => [
        'title' => 'Frequently Asked Questions',
        'items' => [
            'limit' => [
                'question' => 'What happens when I reach my monthly limit?',
                'answer' => 'Free users get 10 prompts per month. Once you\'ve used them, you\'ll need to upgrade to Pro or Private for more. Your monthly allowance resets on the 1st of each month.',
            ],
            'privacy' => [
                'question' => 'What is Private mode?',
                'answer' => 'Private mode ensures your data is not used for training or product improvement. It includes restricted staff access (only with your explicit consent) and enhanced encryption.',
            ],
            'cancel' => [
                'question' => 'Can I cancel my subscription?',
                'answer' => 'Yes, you can cancel anytime. You\'ll retain access until the end of your current billing period. Your data will remain safely encrypted.',
            ],
            'payment' => [
                'question' => 'What payment methods do you accept?',
                'answer' => 'We accept all major credit and debit cards via Stripe. Your payment information is handled securely.',
            ],
        ],
    ],

    // Subscription status (shared with backend)
    'currentPlan' => 'Current Plan',
    'upgrade' => 'Upgrade',
    'upgradeToPro' => 'Upgrade to Pro',
    'upgradeToPrivate' => 'Upgrade to Private',
];
