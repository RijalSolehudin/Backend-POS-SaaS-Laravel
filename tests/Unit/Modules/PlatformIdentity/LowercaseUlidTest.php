<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\PlatformIdentity;

use App\Modules\PlatformIdentity\Domain\Models\PlatformUser;
use PHPUnit\Framework\TestCase;

final class LowercaseUlidTest extends TestCase
{
    public function test_platform_identity_models_generate_canonical_lowercase_ulids(): void
    {
        $id = (new PlatformUser)->newUniqueId();

        self::assertSame(26, strlen($id));
        self::assertSame(strtolower($id), $id);
        self::assertMatchesRegularExpression('/^[0-9a-hjkmnp-tv-z]{26}$/', $id);
    }
}
