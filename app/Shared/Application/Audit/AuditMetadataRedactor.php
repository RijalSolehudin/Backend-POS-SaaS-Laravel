<?php

declare(strict_types=1);

namespace App\Shared\Application\Audit;

final readonly class AuditMetadataRedactor
{
    private const REDACTED = '[redacted]';

    private const SENSITIVE_KEY_PARTS = [
        'credential',
        'password',
        'recovery_code',
        'secret',
        'sql',
        'token',
        'totp',
    ];

    /**
     * @param  array<string, bool|int|string|null>  $metadata
     * @return array<string, bool|int|string|null>
     */
    public function redact(array $metadata): array
    {
        $redacted = [];

        foreach ($metadata as $key => $value) {
            $redacted[$key] = $this->isSensitiveKey($key) ? self::REDACTED : $value;
        }

        return $redacted;
    }

    private function isSensitiveKey(string $key): bool
    {
        $normalized = mb_strtolower($key);

        foreach (self::SENSITIVE_KEY_PARTS as $part) {
            if (str_contains($normalized, $part)) {
                return true;
            }
        }

        return false;
    }
}
