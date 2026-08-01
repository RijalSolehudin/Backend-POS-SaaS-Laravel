<?php

declare(strict_types=1);

namespace App\Modules\Sales\Application\Exceptions;

use App\Shared\Domain\Exceptions\BusinessException;

final class OrderException extends BusinessException
{
    private function __construct(
        string $message,
        private readonly string $businessErrorCode,
    ) {
        parent::__construct($message);
    }

    public static function activeShiftRequired(): self
    {
        return new self('An open shift is required before creating an order.', 'ORDER_ACTIVE_SHIFT_REQUIRED');
    }

    public static function notFound(): self
    {
        return new self('The requested order was not found.', 'ORDER_NOT_FOUND');
    }

    public static function itemNotFound(): self
    {
        return new self('The requested order item was not found.', 'ORDER_ITEM_NOT_FOUND');
    }

    public static function notDraft(): self
    {
        return new self('Only draft orders can be changed.', 'ORDER_NOT_DRAFT');
    }

    public static function itemsRequired(): self
    {
        return new self('At least one order item is required before completion.', 'ORDER_ITEMS_REQUIRED');
    }

    public static function productUnavailable(): self
    {
        return new self('The requested product is not available for this outlet.', 'ORDER_PRODUCT_UNAVAILABLE');
    }

    public static function paymentAmountMismatch(): self
    {
        return new self('Payment amount must equal the order total.', 'PAYMENT_AMOUNT_MISMATCH');
    }

    public static function paymentCurrencyMismatch(): self
    {
        return new self('Payment currency must match the order currency.', 'PAYMENT_CURRENCY_MISMATCH');
    }

    public static function idempotencyKeyRequired(): self
    {
        return new self('An Idempotency-Key header is required for this request.', 'IDEMPOTENCY_KEY_REQUIRED');
    }

    public static function idempotencyConflict(): self
    {
        return new self('The Idempotency-Key was already used with a different request.', 'IDEMPOTENCY_CONFLICT');
    }

    public function errorCode(): string
    {
        return $this->businessErrorCode;
    }
}
