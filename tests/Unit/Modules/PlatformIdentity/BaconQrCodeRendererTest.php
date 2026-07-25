<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\PlatformIdentity;

use App\Modules\PlatformIdentity\Infrastructure\Security\BaconQrCodeRenderer;
use PHPUnit\Framework\TestCase;

final class BaconQrCodeRendererTest extends TestCase
{
    public function test_it_renders_an_inline_svg(): void
    {
        $svg = (new BaconQrCodeRenderer)->asSvg('otpauth://totp/POS:test@example.com?secret=ABC123');

        self::assertStringContainsString('<svg', $svg);
        self::assertStringContainsString('</svg>', $svg);
        self::assertStringNotContainsString('<script', $svg);
    }
}
