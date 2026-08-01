<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Application\Exceptions;

use App\Shared\Domain\Exceptions\BusinessException;

final class DeviceRegistryException extends BusinessException
{
    private function __construct(
        string $message,
        private readonly string $businessErrorCode,
    ) {
        parent::__construct($message);
    }

    public static function notRegistered(): self
    {
        return new self('The POS device is not registered.', 'DEVICE_NOT_REGISTERED');
    }

    public static function revoked(): self
    {
        return new self('The POS device has been revoked.', 'DEVICE_REVOKED');
    }

    public function errorCode(): string
    {
        return $this->businessErrorCode;
    }
}
