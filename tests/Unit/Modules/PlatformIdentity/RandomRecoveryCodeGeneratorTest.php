<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\PlatformIdentity;

use App\Modules\PlatformIdentity\Infrastructure\Security\RandomRecoveryCodeGenerator;
use PHPUnit\Framework\TestCase;

final class RandomRecoveryCodeGeneratorTest extends TestCase
{
    public function test_it_generates_ten_unique_unambiguous_codes(): void
    {
        $codes = (new RandomRecoveryCodeGenerator)->generateSet();

        self::assertCount(10, $codes);
        self::assertCount(10, array_unique($codes));

        foreach ($codes as $code) {
            self::assertMatchesRegularExpression('/^[2-9A-HJ-NP-Z]{4}(?:-[2-9A-HJ-NP-Z]{4}){3}$/', $code);
        }
    }
}
