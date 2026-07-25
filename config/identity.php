<?php

declare(strict_types=1);

return [
    'session' => [
        'idle_minutes' => 30,
        'absolute_minutes' => 480,
    ],
    'login' => [
        'max_attempts' => 5,
        'decay_seconds' => 60,
    ],
    'password' => [
        'min' => 12,
        'max' => 128,
        'check_compromised' => env('TENANT_PASSWORD_CHECK_COMPROMISED', true),
    ],
];
