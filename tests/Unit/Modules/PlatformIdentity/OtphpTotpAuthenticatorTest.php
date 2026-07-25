<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\PlatformIdentity;

use App\Modules\PlatformIdentity\Infrastructure\Security\OtphpTotpAuthenticator;
use DateTimeImmutable;
use DateTimeZone;
use OTPHP\TOTP;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;

final class OtphpTotpAuthenticatorTest extends TestCase
{
    public function test_it_generates_a_provisioning_uri_and_matches_a_single_time_step(): void
    {
        $clock = new class implements ClockInterface
        {
            public function now(): DateTimeImmutable
            {
                return new DateTimeImmutable('2026-07-24 12:00:00', new DateTimeZone('UTC'));
            }
        };
        $authenticator = new OtphpTotpAuthenticator($clock, 'POS Platform');
        $secret = 'JBSWY3DPEHPK3PXP';
        $code = TOTP::createFromSecret($secret, $clock)->now();

        $uri = $authenticator->provisioningUri($secret, 'admin@example.com');

        self::assertStringStartsWith('otpauth://totp/', $uri);
        self::assertStringContainsString('issuer=POS%20Platform', $uri);
        self::assertSame(intdiv($clock->now()->getTimestamp(), 30), $authenticator->matchingTimeStep($secret, $code));
        self::assertNull($authenticator->matchingTimeStep($secret, '00000x'));
    }
}
