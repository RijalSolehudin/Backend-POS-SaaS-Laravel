<?php

declare(strict_types=1);

namespace App\Modules\PlatformIdentity\Infrastructure\Security;

use App\Modules\PlatformIdentity\Application\Contracts\TotpAuthenticator;
use InvalidArgumentException;
use OTPHP\InternalClock;
use OTPHP\TOTP;
use Psr\Clock\ClockInterface;

final readonly class OtphpTotpAuthenticator implements TotpAuthenticator
{
    public function __construct(
        private ClockInterface $clock,
        private string $issuer,
    ) {}

    public function generateSecret(): string
    {
        return TOTP::generate($this->clock, 20)->getSecret();
    }

    public function provisioningUri(string $secret, string $email): string
    {
        if ($secret === '' || $email === '' || $this->issuer === '') {
            throw new InvalidArgumentException('TOTP secret, account label, and issuer must not be empty.');
        }

        return TOTP::createFromSecret($secret, $this->clock)
            ->withLabel($email)
            ->withIssuer($this->issuer)
            ->getProvisioningUri();
    }

    public function matchingTimeStep(string $secret, string $code): ?int
    {
        if ($secret === '' || preg_match('/^\d{6}$/', $code) !== 1) {
            return null;
        }

        $totp = TOTP::createFromSecret($secret, $this->clock);
        $currentTimestamp = $this->clock->now()->getTimestamp();

        foreach ([0, -30, 30] as $offset) {
            $timestamp = $currentTimestamp + $offset;

            if ($timestamp < 0) {
                continue;
            }

            if (hash_equals($totp->at($timestamp), $code)) {
                return intdiv($timestamp, 30);
            }
        }

        return null;
    }

    public static function clock(): ClockInterface
    {
        return new InternalClock;
    }
}
