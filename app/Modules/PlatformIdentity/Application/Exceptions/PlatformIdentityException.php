<?php

declare(strict_types=1);

namespace App\Modules\PlatformIdentity\Application\Exceptions;

use App\Shared\Domain\Exceptions\BusinessException;

final class PlatformIdentityException extends BusinessException
{
    private function __construct(
        string $message,
        private readonly string $businessErrorCode,
    ) {
        parent::__construct($message);
    }

    public static function bootstrapAlreadyCompleted(): self
    {
        return new self(
            'Platform Administrator bootstrap has already been completed.',
            'PLATFORM_BOOTSTRAP_ALREADY_COMPLETED',
        );
    }

    public static function invalidCredentials(): self
    {
        return new self('The supplied platform credentials are invalid.', 'PLATFORM_INVALID_CREDENTIALS');
    }

    public static function invalidSecondFactor(): self
    {
        return new self('The supplied second factor is invalid.', 'PLATFORM_INVALID_SECOND_FACTOR');
    }

    public static function userNotFound(): self
    {
        return new self('The requested Platform Administrator was not found.', 'PLATFORM_USER_NOT_FOUND');
    }

    public function errorCode(): string
    {
        return $this->businessErrorCode;
    }
}
