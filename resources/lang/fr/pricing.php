<?php

return [
    // Page metadata
    'pageTitle' => 'Tarifs - BettrPrompt',
    'title' => 'Tarification simple et transparente',
    'tagline' => 'Commencez gratuitement, passez à un forfait supérieur lorsque vous en avez besoin',

    // Currency display
    'currency' => '€',

    // Tier names and CTAs
    'free' => [
        'name' => 'Gratuit',
        'price' => '0',
        'priceMonthly' => 'Gratuit',
        'cta' => 'Commencer',
    ],
    'pro' => [
        'name' => 'Pro',
        'priceMonthly' => '13,99',
        'priceYearly' => '139',
        'cta' => 'Démarrer Pro',
        'yearlySavings' => 'Économisez {percent}% avec facturation annuelle – seulement {amount}/{period}',
    ],
    'private' => [
        'name' => 'Privé',
        'priceMonthly' => '22,99',
        'priceYearly' => '229',
        'cta' => 'Démarrer Privé',
        'yearlySavings' => 'Économisez {percent}% avec facturation annuelle – seulement {amount}/{period}',
    ],

    // Billing period labels
    'billing' => [
        'monthly' => 'Mensuel',
        'yearly' => 'Annuel',
    ],

    'period' => [
        'month' => 'mois',
        'year' => 'an',
    ],

    // Feature descriptions
    'features' => [
        'free' => [
            'limit' => '10 prompts par mois',
            'calibration' => 'Calibrage de personnalité',
            'optimization' => 'Optimisation de base des prompts',
        ],
        'pro' => [
            'unlimited' => 'Prompts illimités',
            'calibration' => 'Calibrage de personnalité',
            'optimization' => 'Optimisation avancée des prompts',
            'history' => 'Historique des prompts',
        ],
        'private' => [
            'unlimited' => 'Prompts illimités',
            'calibration' => 'Calibrage de personnalité',
            'optimization' => 'Optimisation avancée des prompts',
            'mode' => 'Mode privé (accès restreint)',
            'support' => 'Support prioritaire',
            'history' => 'Historique des prompts',
        ],
        'privacy' => 'Mode privé',
    ],

    // Badges
    'popularBadge' => 'Le Plus Populaire',

    // Action labels
    'actions' => [
        'processing' => 'En cours...',
        'subscribe' => 'S\'abonner',
    ],

    // FAQ section
    'faq' => [
        'title' => 'Questions Fréquemment Posées',
        'items' => [
            'limit' => [
                'question' => 'Que se passe-t-il lorsque j\'atteins ma limite mensuelle ?',
                'answer' => 'Les utilisateurs gratuits obtiennent 10 prompts par mois. Une fois que vous les avez utilisés, vous devrez passer à Pro ou Privé pour en obtenir plus. Votre allocation mensuelle se réinitialise le 1er de chaque mois.',
            ],
            'privacy' => [
                'question' => 'Qu\'est-ce que le mode privé ?',
                'answer' => 'Le mode privé garantit que vos données ne sont pas utilisées pour l\'entraînement ou l\'amélioration des produits. Il comprend un accès restreint du personnel (uniquement avec votre consentement explicite) et un chiffrement renforcé.',
            ],
            'cancel' => [
                'question' => 'Puis-je annuler mon abonnement ?',
                'answer' => 'Oui, vous pouvez annuler à tout moment. Vous conserverez l\'accès jusqu\'à la fin de votre période de facturation actuelle. Vos données resteront chiffrées en toute sécurité.',
            ],
            'payment' => [
                'question' => 'Quels modes de paiement acceptez-vous ?',
                'answer' => 'Nous acceptons toutes les principales cartes de crédit et de débit via Stripe. Vos informations de paiement sont traitées en toute sécurité.',
            ],
        ],
    ],

    // Subscription status (shared with backend)
    'currentPlan' => 'Forfait Actuel',
    'upgrade' => 'Mettre à niveau',
    'upgradeToPro' => 'Passer à Pro',
    'upgradeToPrivate' => 'Passer à Privé',
];
