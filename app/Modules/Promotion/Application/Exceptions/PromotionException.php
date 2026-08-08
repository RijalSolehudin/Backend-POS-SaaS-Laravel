<?php

declare(strict_types=1);

namespace App\Modules\Promotion\Application\Exceptions;

use App\Shared\Domain\Exceptions\BusinessException;

final class PromotionException extends BusinessException
{
    private function __construct(
        string $message,
        private readonly string $businessErrorCode,
    ) {
        parent::__construct($message);
    }

    public static function notFound(): self
    {
        return new self('The requested promotion was not found.', 'PROMOTION_NOT_FOUND');
    }

    public static function invalid(): self
    {
        return new self('The promotion is not valid for this order.', 'PROMOTION_INVALID');
    }

    public function errorCode(): string
    {
        return $this->businessErrorCode;
    }

    public function httpStatus(): int
    {
        return $this->businessErrorCode === 'PROMOTION_NOT_FOUND' ? 404 : 409;
    }
}
