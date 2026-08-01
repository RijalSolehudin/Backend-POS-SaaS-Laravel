<?php

declare(strict_types=1);

namespace App\Modules\Procurement\Application\Exceptions;

use App\Shared\Domain\Exceptions\BusinessException;

final class ProcurementException extends BusinessException
{
    private function __construct(
        string $message,
        private readonly string $businessErrorCode,
    ) {
        parent::__construct($message);
    }

    public static function supplierNotFound(): self
    {
        return new self('The requested supplier was not found.', 'PROCUREMENT_SUPPLIER_NOT_FOUND');
    }

    public static function supplierItemNotFound(): self
    {
        return new self('The requested supplier item was not found.', 'PROCUREMENT_SUPPLIER_ITEM_NOT_FOUND');
    }

    public static function supplierCodeUnavailable(): self
    {
        return new self('The supplier code is already in use for this tenant.', 'PROCUREMENT_SUPPLIER_CODE_UNAVAILABLE');
    }

    public static function supplierItemUnavailable(): self
    {
        return new self('The supplier item mapping is already in use for this tenant.', 'PROCUREMENT_SUPPLIER_ITEM_UNAVAILABLE');
    }

    public static function poNotFound(): self
    {
        return new self('The requested purchase order was not found.', 'PROCUREMENT_PO_NOT_FOUND');
    }

    public static function poInvalidState(): self
    {
        return new self('The purchase order is not in a valid state for this action.', 'PROCUREMENT_PO_INVALID_STATE');
    }

    public static function poApprovalRequired(): self
    {
        return new self('A submitted purchase order must be approved before it can be ordered or received.', 'PROCUREMENT_PO_APPROVAL_REQUIRED');
    }

    public static function receiptOverReceived(): self
    {
        return new self('Goods receipt quantity cannot exceed the remaining purchase order quantity.', 'PROCUREMENT_RECEIPT_OVER_RECEIVED');
    }

    public static function returnQuantityInvalid(): self
    {
        return new self('Purchase return quantity cannot exceed received quantity remaining.', 'PROCUREMENT_RETURN_QUANTITY_INVALID');
    }

    public static function crossTenantReference(): self
    {
        return new self('The selected procurement reference belongs to another tenant.', 'PROCUREMENT_CROSS_TENANT_REFERENCE');
    }

    public static function reasonRequired(): self
    {
        return new self('A reason is required for this procurement action.', 'PROCUREMENT_REASON_REQUIRED');
    }

    public static function idempotencyKeyRequired(): self
    {
        return new self('An idempotency key is required for this procurement mutation.', 'PROCUREMENT_IDEMPOTENCY_KEY_REQUIRED');
    }

    public static function idempotencyConflict(): self
    {
        return new self('The idempotency key was already used with a different procurement request.', 'PROCUREMENT_IDEMPOTENCY_CONFLICT');
    }

    public function errorCode(): string
    {
        return $this->businessErrorCode;
    }

    public function httpStatus(): int
    {
        return match ($this->businessErrorCode) {
            'PROCUREMENT_SUPPLIER_NOT_FOUND', 'PROCUREMENT_SUPPLIER_ITEM_NOT_FOUND', 'PROCUREMENT_PO_NOT_FOUND' => 404,
            'PROCUREMENT_PO_INVALID_STATE', 'PROCUREMENT_PO_APPROVAL_REQUIRED', 'PROCUREMENT_RECEIPT_OVER_RECEIVED', 'PROCUREMENT_RETURN_QUANTITY_INVALID', 'PROCUREMENT_IDEMPOTENCY_CONFLICT' => 409,
            'PROCUREMENT_REASON_REQUIRED', 'PROCUREMENT_IDEMPOTENCY_KEY_REQUIRED' => 422,
            default => 403,
        };
    }
}
