<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Application\Exceptions;

use App\Shared\Domain\Exceptions\BusinessException;

final class InventoryException extends BusinessException
{
    private function __construct(
        string $message,
        private readonly string $businessErrorCode,
    ) {
        parent::__construct($message);
    }

    public static function unitNotFound(): self
    {
        return new self('The requested inventory unit was not found.', 'INVENTORY_UNIT_NOT_FOUND');
    }

    public static function itemNotFound(): self
    {
        return new self('The requested inventory item was not found.', 'INVENTORY_ITEM_NOT_FOUND');
    }

    public static function crossTenantReference(): self
    {
        return new self('The selected inventory reference belongs to another tenant.', 'INVENTORY_CROSS_TENANT_REFERENCE');
    }

    public static function skuUnavailable(): self
    {
        return new self('The inventory SKU is already in use for this tenant.', 'INVENTORY_SKU_UNAVAILABLE');
    }

    public static function unitSymbolUnavailable(): self
    {
        return new self('The inventory unit symbol is already in use for this tenant.', 'INVENTORY_UNIT_SYMBOL_UNAVAILABLE');
    }

    public static function outletNotFound(): self
    {
        return new self('The requested outlet is not available for this tenant.', 'INVENTORY_OUTLET_NOT_FOUND');
    }

    public static function itemInactive(): self
    {
        return new self('Inactive inventory items cannot be used for new stock mutations.', 'INVENTORY_ITEM_INACTIVE');
    }

    public static function unitMismatch(): self
    {
        return new self('The stock movement unit must match the inventory item base unit.', 'INVENTORY_UNIT_MISMATCH');
    }

    public static function currencyMismatch(): self
    {
        return new self('The inventory movement currency must match the tenant currency.', 'INVENTORY_CURRENCY_MISMATCH');
    }

    public static function insufficientStock(): self
    {
        return new self('The stock mutation would make the inventory balance negative.', 'INVENTORY_INSUFFICIENT_STOCK');
    }

    public static function openingBalanceAlreadyRecorded(): self
    {
        return new self('Opening balance has already been recorded for this item and outlet.', 'INVENTORY_OPENING_BALANCE_ALREADY_RECORDED');
    }

    public static function idempotencyKeyRequired(): self
    {
        return new self('An Idempotency-Key header or idempotency_key field is required for this inventory mutation.', 'INVENTORY_IDEMPOTENCY_KEY_REQUIRED');
    }

    public static function idempotencyConflict(): self
    {
        return new self('The Idempotency-Key was already used with a different inventory request.', 'INVENTORY_IDEMPOTENCY_CONFLICT');
    }

    public static function approvalRequired(): self
    {
        return new self('A valid supervisor approval is required for this inventory mutation.', 'INVENTORY_APPROVAL_REQUIRED');
    }

    public static function reasonRequired(): self
    {
        return new self('A reason is required for this inventory mutation.', 'INVENTORY_REASON_REQUIRED');
    }

    public static function invalidAdjustmentType(): self
    {
        return new self('The stock adjustment type is not valid.', 'INVENTORY_ADJUSTMENT_TYPE_INVALID');
    }

    public function errorCode(): string
    {
        return $this->businessErrorCode;
    }

    public function httpStatus(): int
    {
        return match ($this->businessErrorCode) {
            'INVENTORY_UNIT_NOT_FOUND', 'INVENTORY_ITEM_NOT_FOUND', 'INVENTORY_OUTLET_NOT_FOUND' => 404,
            'INVENTORY_ITEM_INACTIVE', 'INVENTORY_INSUFFICIENT_STOCK', 'INVENTORY_OPENING_BALANCE_ALREADY_RECORDED', 'INVENTORY_IDEMPOTENCY_CONFLICT', 'INVENTORY_APPROVAL_REQUIRED' => 409,
            'INVENTORY_UNIT_MISMATCH', 'INVENTORY_CURRENCY_MISMATCH', 'INVENTORY_IDEMPOTENCY_KEY_REQUIRED', 'INVENTORY_REASON_REQUIRED', 'INVENTORY_ADJUSTMENT_TYPE_INVALID' => 422,
            default => 403,
        };
    }
}
