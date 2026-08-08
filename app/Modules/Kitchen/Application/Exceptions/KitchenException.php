<?php

declare(strict_types=1);

namespace App\Modules\Kitchen\Application\Exceptions;

use App\Shared\Domain\Exceptions\BusinessException;

final class KitchenException extends BusinessException
{
    private function __construct(
        string $message,
        private readonly string $businessErrorCode,
    ) {
        parent::__construct($message);
    }

    public static function stationNotFound(): self
    {
        return new self('The requested kitchen station was not found.', 'KITCHEN_STATION_NOT_FOUND');
    }

    public static function routingMissing(): self
    {
        return new self('No kitchen routing rule or fallback station is available.', 'KITCHEN_ROUTING_MISSING');
    }

    public static function ticketNotFound(): self
    {
        return new self('The requested kitchen ticket was not found.', 'KITCHEN_TICKET_NOT_FOUND');
    }

    public static function ticketInvalidState(): self
    {
        return new self('The kitchen ticket is not in a valid state for this action.', 'KITCHEN_TICKET_INVALID_STATE');
    }

    public static function printJobNotFound(): self
    {
        return new self('The requested kitchen print job was not found.', 'KITCHEN_PRINT_JOB_NOT_FOUND');
    }

    public static function printFailed(string $message): self
    {
        return new self($message, 'KITCHEN_PRINT_FAILED');
    }

    public static function outletNotFound(): self
    {
        return new self('The requested outlet is not available for this tenant.', 'KITCHEN_OUTLET_NOT_FOUND');
    }

    public static function orderNotFound(): self
    {
        return new self('The requested order is not available for kitchen dispatch.', 'KITCHEN_ORDER_NOT_FOUND');
    }

    public static function stationCodeUnavailable(): self
    {
        return new self('The kitchen station code is already in use for this outlet.', 'KITCHEN_STATION_CODE_UNAVAILABLE');
    }

    public function errorCode(): string
    {
        return $this->businessErrorCode;
    }

    public function httpStatus(): int
    {
        return match ($this->businessErrorCode) {
            'KITCHEN_STATION_NOT_FOUND', 'KITCHEN_TICKET_NOT_FOUND', 'KITCHEN_PRINT_JOB_NOT_FOUND', 'KITCHEN_OUTLET_NOT_FOUND', 'KITCHEN_ORDER_NOT_FOUND' => 404,
            'KITCHEN_ROUTING_MISSING', 'KITCHEN_TICKET_INVALID_STATE', 'KITCHEN_PRINT_FAILED', 'KITCHEN_STATION_CODE_UNAVAILABLE' => 409,
            default => 403,
        };
    }
}
