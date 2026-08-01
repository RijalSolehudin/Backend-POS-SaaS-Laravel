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

    public function errorCode(): string
    {
        return $this->businessErrorCode;
    }
}
