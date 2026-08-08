<?php

declare(strict_types=1);

namespace App\Modules\Sync\Application\Exceptions;

use App\Shared\Domain\Exceptions\BusinessException;

final class SyncException extends BusinessException
{
    private function __construct(
        string $message,
        private readonly string $businessErrorCode,
    ) {
        parent::__construct($message);
    }

    public static function deviceRevoked(): self
    {
        return new self('The POS device is revoked and cannot sync new mutations.', 'SYNC_DEVICE_REVOKED');
    }

    public static function sequenceConflict(): self
    {
        return new self('The sync sequence conflicts with the accepted device state.', 'SYNC_SEQUENCE_CONFLICT');
    }

    public static function payloadConflict(): self
    {
        return new self('The sync payload conflicts with a previous mutation for the same scope.', 'SYNC_PAYLOAD_CONFLICT');
    }

    public static function operationNotAllowedOffline(): self
    {
        return new self('This operation is not allowed while offline.', 'SYNC_OPERATION_NOT_ALLOWED_OFFLINE');
    }

    public static function conflictRequiresReview(): self
    {
        return new self('The sync conflict requires operator review.', 'SYNC_CONFLICT_REQUIRES_REVIEW');
    }

    public static function offlineOrderNotFound(): self
    {
        return new self('The offline order draft was not found.', 'OFFLINE_ORDER_NOT_FOUND');
    }

    public static function offlineOrderInvalidState(): self
    {
        return new self('The offline order is not in a valid state for this action.', 'OFFLINE_ORDER_INVALID_STATE');
    }

    public static function performanceBaselineFailed(): self
    {
        return new self('The performance baseline failed its target.', 'PERFORMANCE_BASELINE_FAILED');
    }

    public static function recoveryObjectiveFailed(): self
    {
        return new self('The recovery objective evidence does not satisfy the configured target.', 'RECOVERY_OBJECTIVE_FAILED');
    }

    public function errorCode(): string
    {
        return $this->businessErrorCode;
    }

    public function httpStatus(): int
    {
        return match ($this->businessErrorCode) {
            'OFFLINE_ORDER_NOT_FOUND' => 404,
            'SYNC_DEVICE_REVOKED' => 403,
            default => 409,
        };
    }
}
