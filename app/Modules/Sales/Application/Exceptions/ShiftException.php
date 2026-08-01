<?php

declare(strict_types=1);

namespace App\Modules\Sales\Application\Exceptions;

use App\Shared\Domain\Exceptions\BusinessException;

final class ShiftException extends BusinessException
{
    private function __construct(
        string $message,
        private readonly string $businessErrorCode,
    ) {
        parent::__construct($message);
    }

    public static function alreadyOpen(): self
    {
        return new self('A shift is already open for this cashier and outlet.', 'SHIFT_ALREADY_OPEN');
    }

    public static function notFound(): self
    {
        return new self('The requested shift was not found.', 'SHIFT_NOT_FOUND');
    }

    public static function notOpen(): self
    {
        return new self('The requested shift is not open.', 'SHIFT_NOT_OPEN');
    }

    public function errorCode(): string
    {
        return $this->businessErrorCode;
    }
}
