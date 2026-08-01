<?php

declare(strict_types=1);

namespace App\Modules\Sales\Application\Exceptions;

use App\Shared\Domain\Exceptions\BusinessException;

final class CashMovementException extends BusinessException
{
    private function __construct(
        string $message,
        private readonly string $businessErrorCode,
    ) {
        parent::__construct($message);
    }

    public static function shiftNotOpen(): self
    {
        return new self('Cash movement can only be recorded on an open shift.', 'CASH_MOVEMENT_SHIFT_NOT_OPEN');
    }

    public static function reasonRequired(): self
    {
        return new self('A reason is required for cash movement.', 'CASH_MOVEMENT_REASON_REQUIRED');
    }

    public static function idempotencyKeyRequired(): self
    {
        return new self('An Idempotency-Key header is required for this cash movement request.', 'IDEMPOTENCY_KEY_REQUIRED');
    }

    public static function idempotencyConflict(): self
    {
        return new self('The Idempotency-Key was already used with a different cash movement request.', 'IDEMPOTENCY_CONFLICT');
    }

    public function errorCode(): string
    {
        return $this->businessErrorCode;
    }
}
