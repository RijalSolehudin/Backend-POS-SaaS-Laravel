<?php

declare(strict_types=1);

namespace App\Modules\Sales\Application\Actions;

use App\Modules\Sales\Application\Exceptions\ApprovalException;
use App\Modules\Sales\Domain\Enums\SensitiveActionApprovalStatus;
use App\Modules\Sales\Domain\Models\IdempotencyRecord;
use App\Modules\Sales\Domain\Models\SensitiveActionApproval;
use Illuminate\Support\Facades\DB;

final readonly class RequestSensitiveActionApproval
{
    public function __construct(private RecordSalesAuditEvent $audit) {}

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
            $record = IdempotencyRecord::query()
                ->where('tenant_id', $tenantId)
                ->where('outlet_id', $outletId)
                ->where('user_id', $performerUserId)
                ->where('action', 'approvals.request')
                ->where('idempotency_key', $idempotencyKey)
                ->lockForUpdate()
                ->first();

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

            IdempotencyRecord::query()->create([
                'tenant_id' => $tenantId,
                'outlet_id' => $outletId,
                'user_id' => $performerUserId,
                'action' => 'approvals.request',
                'idempotency_key' => $idempotencyKey,
                'request_hash' => $requestHash,
                'resource_type' => 'sales_sensitive_action_approval',
                'resource_id' => $approval->id,
                'response_status' => 201,
                'response_body' => ['approval_id' => $approval->id],
                'expires_at' => now()->addDay(),
            ]);

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
