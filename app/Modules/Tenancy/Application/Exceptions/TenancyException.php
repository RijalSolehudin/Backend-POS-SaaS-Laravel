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
        return new self('The requested tenant action is not allowed.', 'TENANCY_FORBIDDEN');
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

    public static function invalidRole(): self
    {
        return new self('The requested role is not available in the predefined MVP matrix.', 'TENANT_ROLE_INVALID');
    }

    public static function roleNotRemovable(): self
    {
        return new self('This role assignment cannot be removed while it is required for tenant ownership.', 'TENANT_ROLE_NOT_REMOVABLE');
    }

    public static function deviceNotFound(): self
    {
        return new self('The requested POS device was not found.', 'POS_DEVICE_NOT_FOUND');
    }

    public static function deviceInstallationUnavailable(): self
    {
        return new self('The installation ID is already registered for this tenant.', 'POS_DEVICE_INSTALLATION_UNAVAILABLE');
    }

    public function errorCode(): string
    {
        return $this->businessErrorCode;
    }
}
