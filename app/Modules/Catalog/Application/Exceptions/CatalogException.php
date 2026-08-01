<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Exceptions;

use App\Shared\Domain\Exceptions\BusinessException;

final class CatalogException extends BusinessException
{
    private function __construct(
        string $message,
        private readonly string $businessErrorCode,
    ) {
        parent::__construct($message);
    }

    public static function categoryNotFound(): self
    {
        return new self('The requested category was not found.', 'CATALOG_CATEGORY_NOT_FOUND');
    }

    public static function invalidCategoryParent(): self
    {
        return new self('The selected parent category is not valid for this category.', 'CATALOG_CATEGORY_PARENT_INVALID');
    }

    public static function productNotFound(): self
    {
        return new self('The requested product was not found.', 'CATALOG_PRODUCT_NOT_FOUND');
    }

    public static function variantNotFound(): self
    {
        return new self('The requested product variant was not found.', 'CATALOG_VARIANT_NOT_FOUND');
    }

    public static function skuUnavailable(): self
    {
        return new self('The product SKU is already in use for this tenant.', 'CATALOG_SKU_UNAVAILABLE');
    }

    public static function currencyMismatch(): self
    {
        return new self('The catalog currency must match the parent product currency.', 'CATALOG_CURRENCY_MISMATCH');
    }

    public static function outletNotFound(): self
    {
        return new self('The requested outlet is not available for this tenant.', 'CATALOG_OUTLET_NOT_FOUND');
    }

    public function errorCode(): string
    {
        return $this->businessErrorCode;
    }
}
