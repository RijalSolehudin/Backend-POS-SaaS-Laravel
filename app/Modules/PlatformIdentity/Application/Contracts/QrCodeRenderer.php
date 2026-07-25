<?php

declare(strict_types=1);

namespace App\Modules\PlatformIdentity\Application\Contracts;

interface QrCodeRenderer
{
    public function asSvg(string $content): string;
}
