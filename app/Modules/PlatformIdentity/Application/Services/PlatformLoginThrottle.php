<?php

declare(strict_types=1);

namespace App\Modules\PlatformIdentity\Application\Services;

use Illuminate\Support\Facades\RateLimiter;

final class PlatformLoginThrottle
{
    public function retryAfter(string $email, string $ipAddress): int
    {
        $blockedKey = $this->blockedKey($email, $ipAddress);

        if (RateLimiter::tooManyAttempts($blockedKey, 1)) {
            return RateLimiter::availableIn($blockedKey);
        }

        if (RateLimiter::tooManyAttempts($this->ipKey($ipAddress), 30)) {
            return RateLimiter::availableIn($this->ipKey($ipAddress));
        }

        return 0;
    }

    public function recordFailure(string $email, string $ipAddress): int
    {
        $attemptKey = $this->attemptKey($email, $ipAddress);
        RateLimiter::hit($attemptKey, 900);
        RateLimiter::hit($this->ipKey($ipAddress), 900);

        if (! RateLimiter::tooManyAttempts($attemptKey, 5)) {
            return 0;
        }

        $strikeKey = $this->strikeKey($email, $ipAddress);
        RateLimiter::hit($strikeKey, 86_400);
        $strikes = RateLimiter::attempts($strikeKey);
        $cooldown = match (true) {
            $strikes <= 1 => 60,
            $strikes === 2 => 300,
            default => 900,
        };

        RateLimiter::clear($attemptKey);
        RateLimiter::hit($this->blockedKey($email, $ipAddress), $cooldown);

        return $cooldown;
    }

    public function clearAfterFullAuthentication(string $email, string $ipAddress): void
    {
        RateLimiter::clear($this->attemptKey($email, $ipAddress));
        RateLimiter::clear($this->blockedKey($email, $ipAddress));
        RateLimiter::clear($this->strikeKey($email, $ipAddress));
    }

    private function attemptKey(string $email, string $ipAddress): string
    {
        return 'platform-login:attempt:'.hash('sha256', $email.'|'.$ipAddress);
    }

    private function blockedKey(string $email, string $ipAddress): string
    {
        return 'platform-login:blocked:'.hash('sha256', $email.'|'.$ipAddress);
    }

    private function strikeKey(string $email, string $ipAddress): string
    {
        return 'platform-login:strikes:'.hash('sha256', $email.'|'.$ipAddress);
    }

    private function ipKey(string $ipAddress): string
    {
        return 'platform-login:ip:'.hash('sha256', $ipAddress);
    }
}
