<?php

declare(strict_types=1);

namespace App\Modules\Sales\Application\Actions;

use App\Modules\Sales\Application\Exceptions\ApprovalException;
use App\Modules\Sales\Application\Services\IdempotencyStore;
use App\Modules\Sales\Domain\Enums\SensitiveActionApprovalStatus;
use App\Modules\Sales\Domain\Models\IdempotencyRecord;
use App\Modules\Sales\Domain\Models\SensitiveActionApproval;
use Illuminate\Support\Facades\DB;

final readonly class RequestSensitiveActionApproval
{
    public function __construct(
        private RecordSalesAuditEvent $audit,
        private IdempotencyStore $idempotency,
    ) {}

    public function handle(
        string $tenantId,
        string $outletId,
        string $performerUserId,
        string $action,
        string $targetType,
        string $targetId,
        string $requestFingerprint,
        string $reason,
        string $idempotencyKey,
    ): SensitiveActionApproval {
        $reason = trim($reason);

        if ($reason === '') {
            throw ApprovalException::reasonRequired();
        }

        if (trim($idempotencyKey) === '') {
            throw ApprovalException::idempotencyKeyRequired();
        }

        $requestHash = hash('sha256', json_encode([
            'outlet_id' => $outletId,
            'performer_user_id' => $performerUserId,
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'request_fingerprint' => $requestFingerprint,
            'reason' => $reason,
        ], JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($tenantId, $outletId, $performerUserId, $action, $targetType, $targetId, $requestFingerprint, $reason, $idempotencyKey, $requestHash): SensitiveActionApproval {
            $record = $this->idempotency->findForUpdate(
                tenantId: $tenantId,
                outletId: $outletId,
                userId: $performerUserId,
                action: 'approvals.request',
                idempotencyKey: $idempotencyKey,
            );

            if ($record instanceof IdempotencyRecord) {
                if ($record->request_hash !== $requestHash || $record->resource_id === null) {
                    throw ApprovalException::idempotencyConflict();
                }

                $approval = SensitiveActionApproval::query()
                    ->where('tenant_id', $tenantId)
                    ->whereKey($record->resource_id)
                    ->first();

                if (! $approval instanceof SensitiveActionApproval) {
                    throw ApprovalException::notFound();
                }

                return $approval;
            }

            $approval = SensitiveActionApproval::query()->create([
                'tenant_id' => $tenantId,
                'outlet_id' => $outletId,
                'performer_user_id' => $performerUserId,
                'action' => $action,
                'target_type' => $targetType,
                'target_id' => $targetId,
                'request_fingerprint' => $requestFingerprint,
                'status' => SensitiveActionApprovalStatus::Pending,
                'reason' => $reason,
                'requested_at' => now(),
                'expires_at' => now()->addMinutes(15),
            ]);

            $this->idempotency->create(
                tenantId: $tenantId,
                outletId: $outletId,
                userId: $performerUserId,
                action: 'approvals.request',
                idempotencyKey: $idempotencyKey,
                requestHash: $requestHash,
                resourceType: 'sales_sensitive_action_approval',
                resourceId: $approval->id,
                responseStatus: 201,
                responseBody: ['approval_id' => $approval->id],
            );

            $this->audit->handle(
                tenantId: $tenantId,
                outletId: $outletId,
                actorUserId: $performerUserId,
                eventType: 'approval.created',
                targetType: 'sales_sensitive_action_approval',
                targetId: $approval->id,
                outcome: 'pending',
                reason: $reason,
                correlationId: $idempotencyKey,
                metadata: [
                    'action' => $action,
                    'target_type' => $targetType,
                    'target_id' => $targetId,
                ],
            );

            return $approval;
        });
    }
}
