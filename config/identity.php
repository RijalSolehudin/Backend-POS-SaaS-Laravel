<?php

declare(strict_types=1);

return [
    'password' => [
        'min' => 12,
        'max' => 128,
        'check_compromised' => env('TENANT_PASSWORD_CHECK_COMPROMISED', true),
    ],
];
