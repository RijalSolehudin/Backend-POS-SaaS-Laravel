<?php

declare(strict_types=1);

namespace App\Modules\PlatformIdentity\Infrastructure\Security;

use App\Modules\PlatformIdentity\Application\Contracts\QrCodeRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

final class BaconQrCodeRenderer implements QrCodeRenderer
{
    public function asSvg(string $content): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle(256, 2),
            new SvgImageBackEnd,
        );

        return (new Writer($renderer))->writeString($content);
    }
}
