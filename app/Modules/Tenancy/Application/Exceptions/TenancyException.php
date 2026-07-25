<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Application\Exceptions;

use App\Shared\Domain\Exceptions\BusinessException;

final class TenancyException extends BusinessException
{
    private function __construct(
        string $message,
        private readonly string $businessErrorCode,
    ) {
        parent::__construct($message);
    }

    public static function forbidden(): self
    {
        return new self('Tenant owner access is required.', 'TENANCY_FORBIDDEN');
    }

    public static function outletNotFound(): self
    {
        return new self('The requested outlet was not found.', 'OUTLET_NOT_FOUND');
    }

    public static function outletCodeUnavailable(): self
    {
        return new self('The outlet code is already in use for this tenant.', 'OUTLET_CODE_UNAVAILABLE');
    }

    public static function userNotFound(): self
    {
        return new self('The requested tenant user was not found.', 'TENANT_USER_NOT_FOUND');
    }

    public function errorCode(): string
    {
        return $this->businessErrorCode;
    }
}
