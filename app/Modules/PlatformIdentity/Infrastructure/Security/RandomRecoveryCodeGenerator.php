<?php

declare(strict_types=1);

namespace App\Modules\PlatformIdentity\Infrastructure\Security;

use App\Modules\PlatformIdentity\Application\Contracts\RecoveryCodeGenerator;

final class RandomRecoveryCodeGenerator implements RecoveryCodeGenerator
{
    private const ALPHABET = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';

    public function generateSet(): array
    {
        $codes = [];

        for ($codeIndex = 0; $codeIndex < 10; $codeIndex++) {
            $plain = '';

            for ($characterIndex = 0; $characterIndex < 16; $characterIndex++) {
                $plain .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
            }

            $codes[] = implode('-', str_split($plain, 4));
        }

        return $codes;
    }
}
