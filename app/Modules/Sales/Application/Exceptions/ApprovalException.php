<?php

declare(strict_types=1);

namespace App\Modules\Sales\Application\Exceptions;

use App\Shared\Domain\Exceptions\BusinessException;

final class ApprovalException extends BusinessException
{
    private function __construct(
        string $message,
        private readonly string $businessErrorCode,
    ) {
        parent::__construct($message);
    }

    public static function required(): self
    {
        return new self('A valid supervisor approval is required for this action.', 'APPROVAL_REQUIRED');
    }

    public static function notFound(): self
    {
        return new self('The requested approval was not found.', 'APPROVAL_NOT_FOUND');
    }

    public static function forbidden(): self
    {
        return new self('The selected approver is not allowed to approve this action.', 'APPROVAL_FORBIDDEN');
    }

    public static function sameActor(): self
    {
        return new self('The approver must be different from the performer.', 'APPROVAL_SAME_ACTOR');
    }

    public static function expired(): self
    {
        return new self('The approval has expired.', 'APPROVAL_EXPIRED');
    }

    public static function alreadyConsumed(): self
    {
        return new self('The approval has already been consumed.', 'APPROVAL_ALREADY_CONSUMED');
    }

    public static function invalidState(): self
    {
        return new self('The approval is not in a valid state for this action.', 'APPROVAL_INVALID_STATE');
    }

    public static function targetMismatch(): self
    {
        return new self('The approval does not match this action target.', 'APPROVAL_TARGET_MISMATCH');
    }

    public static function reasonRequired(): self
    {
        return new self('A reason is required for approval actions.', 'APPROVAL_REASON_REQUIRED');
    }

    public static function idempotencyKeyRequired(): self
    {
        return new self('An Idempotency-Key header is required for this approval request.', 'IDEMPOTENCY_KEY_REQUIRED');
    }

    public static function idempotencyConflict(): self
    {
        return new self('The Idempotency-Key was already used with a different approval request.', 'IDEMPOTENCY_CONFLICT');
    }

    public function errorCode(): string
    {
        return $this->businessErrorCode;
    }
}
