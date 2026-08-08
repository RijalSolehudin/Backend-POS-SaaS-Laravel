<?php

declare(strict_types=1);

namespace App\Modules\PaymentsGateway\Application\Exceptions;

use App\Shared\Domain\Exceptions\BusinessException;

final class PaymentGatewayException extends BusinessException
{
    private function __construct(
        string $message,
        private readonly string $businessErrorCode,
    ) {
        parent::__construct($message);
    }

    public static function signatureInvalid(): self
    {
        return new self('The payment provider webhook signature is invalid.', 'PAYMENT_PROVIDER_SIGNATURE_INVALID');
    }

    public static function intentNotFound(): self
    {
        return new self('The requested payment intent was not found.', 'PAYMENT_INTENT_NOT_FOUND');
    }

    public static function intentInvalidState(): self
    {
        return new self('The payment intent is not in a valid state for this action.', 'PAYMENT_INTENT_INVALID_STATE');
    }

    public function errorCode(): string
    {
        return $this->businessErrorCode;
    }

    public function httpStatus(): int
    {
        return match ($this->businessErrorCode) {
            'PAYMENT_PROVIDER_SIGNATURE_INVALID' => 401,
            'PAYMENT_INTENT_NOT_FOUND' => 404,
            default => 409,
        };
    }
}
