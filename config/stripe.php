<?php

return [
    'default' => env('STRIPE_DEFAULT_ACCOUNT', 'account_1'),

    'accounts' => [
        'account_1' => [
            'name' => env('STRIPE_ACCOUNT_1_NAME', 'Primary sandbox'),
            'mode' => env('STRIPE_ACCOUNT_1_MODE', 'test'),
            'active' => true,
            'secret' => env('STRIPE_ACCOUNT_1_SECRET'),
            'publishable_key' => env('STRIPE_ACCOUNT_1_PUBLISHABLE_KEY'),
            'webhook_secret' => env('STRIPE_ACCOUNT_1_WEBHOOK_SECRET'),
        ],
    ],
];
