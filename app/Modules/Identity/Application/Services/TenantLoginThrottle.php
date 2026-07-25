<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Services;

use Illuminate\Support\Facades\RateLimiter;

final class TenantLoginThrottle
{
    public function retryAfter(string $email, string $ip): int
    {
        $key = $this->key($email, $ip);

        return RateLimiter::tooManyAttempts($key, (int) config('identity.login.max_attempts', 5))
            ? RateLimiter::availableIn($key)
            : 0;
    }

    public function recordFailure(string $email, string $ip): void
    {
        RateLimiter::hit($this->key($email, $ip), (int) config('identity.login.decay_seconds', 60));
    }

    public function clear(string $email, string $ip): void
    {
        RateLimiter::clear($this->key($email, $ip));
    }

    private function key(string $email, string $ip): string
    {
        return 'tenant-login:'.hash('sha256', mb_strtolower(trim($email)).'|'.$ip);
    }
}
