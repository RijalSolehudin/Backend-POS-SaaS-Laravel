<?php

declare(strict_types=1);

namespace App\Modules\Sales\Application\Exceptions;

use App\Shared\Domain\Exceptions\BusinessException;

final class RefundException extends BusinessException
{
    private function __construct(
        string $message,
        private readonly string $businessErrorCode,
    ) {
        parent::__construct($message);
    }

    public static function orderNotRefundable(): self
    {
        return new self('Only completed orders with recorded payment can be refunded.', 'REFUND_ORDER_NOT_REFUNDABLE');
    }

    public static function alreadyRefunded(): self
    {
        return new self('This payment has already been refunded.', 'REFUND_ALREADY_RECORDED');
    }

    public static function amountMismatch(): self
    {
        return new self('Refund amount must equal the remaining refundable payment amount.', 'REFUND_AMOUNT_MISMATCH');
    }

    public static function currencyMismatch(): self
    {
        return new self('Refund currency must match the original payment currency.', 'REFUND_CURRENCY_MISMATCH');
    }

    public static function reasonRequired(): self
    {
        return new self('A reason is required for refund.', 'REFUND_REASON_REQUIRED');
    }

    public static function idempotencyKeyRequired(): self
    {
        return new self('An Idempotency-Key header is required for this refund request.', 'IDEMPOTENCY_KEY_REQUIRED');
    }

    public static function idempotencyConflict(): self
    {
        return new self('The Idempotency-Key was already used with a different refund request.', 'IDEMPOTENCY_CONFLICT');
    }

    public function errorCode(): string
    {
        return $this->businessErrorCode;
    }
}
