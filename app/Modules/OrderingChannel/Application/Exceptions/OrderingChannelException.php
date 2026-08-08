<?php

declare(strict_types=1);

namespace App\Modules\OrderingChannel\Application\Exceptions;

use App\Shared\Domain\Exceptions\BusinessException;

final class OrderingChannelException extends BusinessException
{
    private function __construct(
        string $message,
        private readonly string $businessErrorCode,
    ) {
        parent::__construct($message);
    }

    public static function qrSessionNotFound(): self
    {
        return new self('The requested QR session was not found.', 'QR_SESSION_NOT_FOUND');
    }

    public static function qrSessionExpired(): self
    {
        return new self('The QR session is expired or no longer active.', 'QR_SESSION_EXPIRED');
    }

    public static function orderRequestNotFound(): self
    {
        return new self('The requested order request was not found.', 'ORDER_REQUEST_NOT_FOUND');
    }

    public static function orderRequestInvalidState(): self
    {
        return new self('The order request is not in a valid state for this action.', 'ORDER_REQUEST_INVALID_STATE');
    }

    public static function cartInvalid(): self
    {
        return new self('The customer cart contains unavailable catalog selections.', 'ORDERING_CART_INVALID');
    }

    public function errorCode(): string
    {
        return $this->businessErrorCode;
    }

    public function httpStatus(): int
    {
        return match ($this->businessErrorCode) {
            'QR_SESSION_NOT_FOUND', 'ORDER_REQUEST_NOT_FOUND' => 404,
            'QR_SESSION_EXPIRED', 'ORDER_REQUEST_INVALID_STATE' => 409,
            default => 422,
        };
    }
}
