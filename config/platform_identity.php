<?php

declare(strict_types=1);

return [
    'issuer' => env('PLATFORM_TOTP_ISSUER', env('APP_NAME', 'POS F&B').' Platform'),
    'session' => [
        'cookie' => env('PLATFORM_SESSION_COOKIE', 'pos_platform_session'),
        'table' => 'platform_sessions',
        'idle_minutes' => 15,
        'absolute_minutes' => 240,
        'max_active' => 2,
        'secure' => env('PLATFORM_SESSION_SECURE_COOKIE', env('APP_ENV') === 'production'),
        'same_site' => 'lax',
        'path' => '/platform',
    ],
    'challenge_ttl_seconds' => 300,
    'sensitive_confirmation_seconds' => 600,
    'password' => [
        'min' => 12,
        'max' => 128,
        'check_compromised' => env('PLATFORM_PASSWORD_CHECK_COMPROMISED', true),
    ],
    'security_mailbox' => env('PLATFORM_SECURITY_MAILBOX'),
    'emergency_allowed_environments' => ['local', 'staging', 'production'],
];
