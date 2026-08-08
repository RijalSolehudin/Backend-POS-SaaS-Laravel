<?php

declare(strict_types=1);

namespace App\Modules\Reservation\Application\Exceptions;

use App\Shared\Domain\Exceptions\BusinessException;

final class ReservationException extends BusinessException
{
    private function __construct(
        string $message,
        private readonly string $businessErrorCode,
    ) {
        parent::__construct($message);
    }

    public static function notFound(): self
    {
        return new self('The requested reservation was not found.', 'RESERVATION_NOT_FOUND');
    }

    public static function invalidState(): self
    {
        return new self('The reservation is not in a valid state for this action.', 'RESERVATION_INVALID_STATE');
    }

    public function errorCode(): string
    {
        return $this->businessErrorCode;
    }

    public function httpStatus(): int
    {
        return $this->businessErrorCode === 'RESERVATION_NOT_FOUND' ? 404 : 409;
    }
}
