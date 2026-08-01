<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Exceptions;

use App\Shared\Domain\Exceptions\BusinessException;

final class IdentityException extends BusinessException
{
    private function __construct(
        string $message,
        private readonly string $businessErrorCode,
    ) {
        parent::__construct($message);
    }

    public static function emailUnavailable(): self
    {
        return new self(
            'The tenant owner email is already in use.',
            'IDENTITY_EMAIL_UNAVAILABLE',
        );
    }

    public static function invalidCredentials(): self
    {
        return new self(
            'The tenant credentials are invalid or inactive.',
            'IDENTITY_INVALID_CREDENTIALS',
        );
    }

    public function errorCode(): string
    {
        return $this->businessErrorCode;
    }
}
