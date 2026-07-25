<?php

declare(strict_types=1);

namespace App\Modules\PlatformIdentity\Application\Contracts;

interface RecoveryCodeGenerator
{
    /**
     * @return list<string>
     */
    public function generateSet(): array;
}
