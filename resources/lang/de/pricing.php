<?php

return [
    // Page metadata
    'pageTitle' => 'Preise - BettrPrompt',
    'title' => 'Einfache, transparente Preise',
    'tagline' => 'Kostenlos starten, upgraden wenn Sie mehr brauchen',

    // Currency display
    'currency' => '€',

    // Tier names and CTAs
    'free' => [
        'name' => 'Kostenlos',
        'price' => '0',
        'priceMonthly' => 'Kostenlos',
        'cta' => 'Loslegen',
    ],
    'pro' => [
        'name' => 'Pro',
        'priceMonthly' => '13,99',
        'priceYearly' => '139',
        'cta' => 'Pro starten',
        'yearlySavings' => 'Sparen Sie {percent}% bei jährlicher Abrechnung – nur {amount}/{period}',
    ],
    'private' => [
        'name' => 'Privat',
        'priceMonthly' => '22,99',
        'priceYearly' => '229',
        'cta' => 'Privat starten',
        'yearlySavings' => 'Sparen Sie {percent}% bei jährlicher Abrechnung – nur {amount}/{period}',
    ],

    // Billing period labels
    'billing' => [
        'monthly' => 'Monatlich',
        'yearly' => 'Jährlich',
    ],

    'period' => [
        'month' => 'Monat',
        'year' => 'Jahr',
    ],

    // Feature descriptions
    'features' => [
        'free' => [
            'limit' => '10 Prompts pro Monat',
            'calibration' => 'Persönlichkeitskalibrierung',
            'optimization' => 'Grundlegende Prompt-Optimierung',
        ],
        'pro' => [
            'unlimited' => 'Unbegrenzte Prompts',
            'calibration' => 'Persönlichkeitskalibrierung',
            'optimization' => 'Erweiterte Prompt-Optimierung',
            'history' => 'Prompt-Verlauf',
        ],
        'private' => [
            'unlimited' => 'Unbegrenzte Prompts',
            'calibration' => 'Persönlichkeitskalibrierung',
            'optimization' => 'Erweiterte Prompt-Optimierung',
            'mode' => 'Privater Modus (eingeschränkter Zugriff)',
            'support' => 'Prioritäts-Support',
            'history' => 'Prompt-Verlauf',
        ],
        'privacy' => 'Privater Modus',
    ],

    // Badges
    'popularBadge' => 'Beliebteste',

    // Action labels
    'actions' => [
        'processing' => 'Wird verarbeitet...',
        'subscribe' => 'Abonnieren',
    ],

    // FAQ section
    'faq' => [
        'title' => 'Häufig gestellte Fragen',
        'items' => [
            'limit' => [
                'question' => 'Was passiert, wenn ich mein monatliches Limit erreiche?',
                'answer' => 'Kostenlose Nutzer erhalten 10 Prompts pro Monat. Sobald Sie diese verwendet haben, müssen Sie auf Pro oder Privat upgraden, um mehr zu erhalten. Ihr monatliches Kontingent wird am 1. jedes Monats zurückgesetzt.',
            ],
            'privacy' => [
                'question' => 'Was ist der private Modus?',
                'answer' => 'Der private Modus stellt sicher, dass Ihre Daten nicht für Training oder Produktverbesserungen verwendet werden. Er umfasst eingeschränkten Mitarbeiterzugriff (nur mit Ihrer ausdrücklichen Zustimmung) und erweiterte Verschlüsselung.',
            ],
            'cancel' => [
                'question' => 'Kann ich mein Abonnement kündigen?',
                'answer' => 'Ja, Sie können jederzeit kündigen. Sie behalten den Zugriff bis zum Ende Ihres aktuellen Abrechnungszeitraums. Ihre Daten bleiben sicher verschlüsselt.',
            ],
            'payment' => [
                'question' => 'Welche Zahlungsmethoden akzeptieren Sie?',
                'answer' => 'Wir akzeptieren alle gängigen Kredit- und Debitkarten über Stripe. Ihre Zahlungsinformationen werden sicher verarbeitet.',
            ],
        ],
    ],

    // Subscription status (shared with backend)
    'currentPlan' => 'Aktueller Plan',
    'upgrade' => 'Upgraden',
    'upgradeToPro' => 'Auf Pro upgraden',
    'upgradeToPrivate' => 'Auf Privat upgraden',
];
