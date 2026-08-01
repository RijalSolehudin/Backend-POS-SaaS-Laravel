<?php

declare(strict_types=1);

namespace App\Modules\Recipe\Application\Exceptions;

use App\Shared\Domain\Exceptions\BusinessException;

final class RecipeException extends BusinessException
{
    private function __construct(
        string $message,
        private readonly string $businessErrorCode,
    ) {
        parent::__construct($message);
    }

    public static function notFound(): self
    {
        return new self('The requested recipe was not found.', 'RECIPE_NOT_FOUND');
    }

    public static function versionNotFound(): self
    {
        return new self('The requested recipe version was not found.', 'RECIPE_VERSION_NOT_FOUND');
    }

    public static function invalidVersionState(): self
    {
        return new self('The recipe version is not in a valid state for this action.', 'RECIPE_VERSION_INVALID_STATE');
    }

    public static function mappingRequired(): self
    {
        return new self('A recipe mapping is required before completing this sales item.', 'RECIPE_MAPPING_REQUIRED');
    }

    public static function insufficientStock(): self
    {
        return new self('Recipe stock deduction cannot be completed because inventory stock is insufficient.', 'RECIPE_INSUFFICIENT_STOCK');
    }

    public static function deductionAlreadyRecorded(): self
    {
        return new self('Recipe deduction has already been recorded for this sales order item.', 'RECIPE_DEDUCTION_ALREADY_RECORDED');
    }

    public static function crossTenantReference(): self
    {
        return new self('The selected recipe reference belongs to another tenant.', 'RECIPE_CROSS_TENANT_REFERENCE');
    }

    public static function skuUnavailable(): self
    {
        return new self('The recipe SKU is already in use for this tenant.', 'RECIPE_SKU_UNAVAILABLE');
    }

    public function errorCode(): string
    {
        return $this->businessErrorCode;
    }

    public function httpStatus(): int
    {
        return match ($this->businessErrorCode) {
            'RECIPE_NOT_FOUND', 'RECIPE_VERSION_NOT_FOUND' => 404,
            'RECIPE_VERSION_INVALID_STATE', 'RECIPE_MAPPING_REQUIRED', 'RECIPE_INSUFFICIENT_STOCK', 'RECIPE_DEDUCTION_ALREADY_RECORDED' => 409,
            default => 422,
        };
    }
}
