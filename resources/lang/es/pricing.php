<?php

return [
    // Page metadata
    'pageTitle' => 'Precios - BettrPrompt',
    'title' => 'Precios simples y transparentes',
    'tagline' => 'Comienza gratis, actualiza cuando necesites más',

    // Currency display
    'currency' => '$',

    // Tier names and CTAs
    'free' => [
        'name' => 'Gratis',
        'price' => '0',
        'priceMonthly' => 'Gratis',
        'cta' => 'Comenzar',
    ],
    'pro' => [
        'name' => 'Pro',
        'priceMonthly' => '15,99',
        'priceYearly' => '159',
        'cta' => 'Iniciar Pro',
        'yearlySavings' => 'Ahorra {percent}% con facturación anual – solo {amount}/{period}',
    ],
    'private' => [
        'name' => 'Privado',
        'priceMonthly' => '26,99',
        'priceYearly' => '269',
        'cta' => 'Iniciar Privado',
        'yearlySavings' => 'Ahorra {percent}% con facturación anual – solo {amount}/{period}',
    ],

    // Billing period labels
    'billing' => [
        'monthly' => 'Mensual',
        'yearly' => 'Anual',
    ],

    'period' => [
        'month' => 'mes',
        'year' => 'año',
    ],

    // Feature descriptions
    'features' => [
        'free' => [
            'limit' => '10 prompts por mes',
            'calibration' => 'Calibración de personalidad',
            'optimization' => 'Optimización básica de prompts',
        ],
        'pro' => [
            'unlimited' => 'Prompts ilimitados',
            'calibration' => 'Calibración de personalidad',
            'optimization' => 'Optimización avanzada de prompts',
            'history' => 'Historial de prompts',
        ],
        'private' => [
            'unlimited' => 'Prompts ilimitados',
            'calibration' => 'Calibración de personalidad',
            'optimization' => 'Optimización avanzada de prompts',
            'mode' => 'Modo privado (acceso restringido)',
            'support' => 'Soporte prioritario',
            'history' => 'Historial de prompts',
        ],
        'privacy' => 'Modo privado',
    ],

    // Badges
    'popularBadge' => 'Más Popular',

    // Action labels
    'actions' => [
        'processing' => 'Procesando...',
        'subscribe' => 'Suscribirse',
    ],

    // FAQ section
    'faq' => [
        'title' => 'Preguntas Frecuentes',
        'items' => [
            'limit' => [
                'question' => '¿Qué sucede cuando alcanzo mi límite mensual?',
                'answer' => 'Los usuarios gratuitos obtienen 10 prompts por mes. Una vez que los hayas usado, necesitarás actualizar a Pro o Privado para obtener más. Tu asignación mensual se reinicia el día 1 de cada mes.',
            ],
            'privacy' => [
                'question' => '¿Qué es el modo privado?',
                'answer' => 'El modo privado garantiza que tus datos no se utilicen para entrenamiento o mejora del producto. Incluye acceso restringido del personal (solo con tu consentimiento explícito) y cifrado mejorado.',
            ],
            'cancel' => [
                'question' => '¿Puedo cancelar mi suscripción?',
                'answer' => 'Sí, puedes cancelar en cualquier momento. Conservarás el acceso hasta el final de tu período de facturación actual. Tus datos permanecerán cifrados de forma segura.',
            ],
            'payment' => [
                'question' => '¿Qué métodos de pago aceptan?',
                'answer' => 'Aceptamos todas las tarjetas de crédito y débito principales a través de Stripe. Tu información de pago se maneja de forma segura.',
            ],
        ],
    ],

    // Subscription status (shared with backend)
    'currentPlan' => 'Plan Actual',
    'upgrade' => 'Actualizar',
    'upgradeToPro' => 'Actualizar a Pro',
    'upgradeToPrivate' => 'Actualizar a Privado',
];
