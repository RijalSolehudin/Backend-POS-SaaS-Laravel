<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Application\Exceptions;

use App\Shared\Domain\Exceptions\BusinessException;
use Throwable;

final class TenantProvisioningException extends BusinessException
{
    private function __construct(
        string $message,
        private readonly string $businessErrorCode,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, previous: $previous);
    }

    public static function idempotencyMismatch(): self
    {
        return new self(
            'This idempotency key was already used with different provisioning input.',
            'TENANT_IDEMPOTENCY_MISMATCH',
        );
    }

    public static function provisioningInProgress(): self
    {
        return new self(
            'This provisioning request is still in progress.',
            'TENANT_PROVISIONING_IN_PROGRESS',
        );
    }

    public static function tenantCodeUnavailable(): self
    {
        return new self(
            'The requested tenant code is already in use.',
            'TENANT_CODE_UNAVAILABLE',
        );
    }

    public static function ownerEmailUnavailable(): self
    {
        return new self(
            'The tenant owner email is already in use.',
            'TENANT_OWNER_EMAIL_UNAVAILABLE',
        );
    }

    public static function conflict(?Throwable $previous = null): self
    {
        return new self(
            'Provisioning conflicts with existing tenant or identity data.',
            'TENANT_PROVISIONING_CONFLICT',
            $previous,
        );
    }

    public static function failed(string $correlationId, ?Throwable $previous = null): self
    {
        return new self(
            "Tenant provisioning could not be completed. Correlation ID: {$correlationId}.",
            'TENANT_PROVISIONING_FAILED',
            $previous,
        );
    }

    public static function tenantNotFound(): self
    {
        return new self('The requested tenant was not found.', 'TENANT_NOT_FOUND');
    }

    public function errorCode(): string
    {
        return $this->businessErrorCode;
    }
}
