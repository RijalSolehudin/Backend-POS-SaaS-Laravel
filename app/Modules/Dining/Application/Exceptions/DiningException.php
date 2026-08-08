<?php

declare(strict_types=1);

namespace App\Modules\Dining\Application\Exceptions;

use App\Shared\Domain\Exceptions\BusinessException;

final class DiningException extends BusinessException
{
    private function __construct(
        string $message,
        private readonly string $businessErrorCode,
    ) {
        parent::__construct($message);
    }

    public static function floorNotFound(): self
    {
        return new self('The requested dining floor was not found.', 'DINING_FLOOR_NOT_FOUND');
    }

    public static function tableNotFound(): self
    {
        return new self('The requested dining table was not found.', 'DINING_TABLE_NOT_FOUND');
    }

    public static function tableOccupied(): self
    {
        return new self('The selected dining table already has an open session.', 'DINING_TABLE_OCCUPIED');
    }

    public static function tableSessionNotFound(): self
    {
        return new self('The requested dining table session was not found.', 'DINING_TABLE_SESSION_NOT_FOUND');
    }

    public static function tableSessionInvalidState(): self
    {
        return new self('The dining table session is not in a valid state for this action.', 'DINING_TABLE_SESSION_INVALID_STATE');
    }

    public static function orderNotFound(): self
    {
        return new self('The selected order is not available for this table session.', 'DINING_ORDER_NOT_FOUND');
    }

    public static function outletNotFound(): self
    {
        return new self('The requested outlet is not available for this tenant.', 'DINING_OUTLET_NOT_FOUND');
    }

    public static function floorCodeUnavailable(): self
    {
        return new self('The dining floor code is already in use for this outlet.', 'DINING_FLOOR_CODE_UNAVAILABLE');
    }

    public static function tableCodeUnavailable(): self
    {
        return new self('The dining table code is already in use for this outlet.', 'DINING_TABLE_CODE_UNAVAILABLE');
    }

    public static function crossOutletFloor(): self
    {
        return new self('The selected dining floor belongs to another outlet.', 'DINING_CROSS_OUTLET_FLOOR');
    }

    public static function floorOutletCannotChange(): self
    {
        return new self('Dining floor outlet cannot be changed after creation.', 'DINING_FLOOR_OUTLET_IMMUTABLE');
    }

    public function errorCode(): string
    {
        return $this->businessErrorCode;
    }

    public function httpStatus(): int
    {
        return match ($this->businessErrorCode) {
            'DINING_FLOOR_NOT_FOUND', 'DINING_TABLE_NOT_FOUND', 'DINING_OUTLET_NOT_FOUND', 'DINING_TABLE_SESSION_NOT_FOUND', 'DINING_ORDER_NOT_FOUND' => 404,
            'DINING_FLOOR_CODE_UNAVAILABLE', 'DINING_TABLE_CODE_UNAVAILABLE', 'DINING_TABLE_OCCUPIED', 'DINING_TABLE_SESSION_INVALID_STATE' => 409,
            'DINING_CROSS_OUTLET_FLOOR', 'DINING_FLOOR_OUTLET_IMMUTABLE' => 422,
            default => 403,
        };
    }
}
