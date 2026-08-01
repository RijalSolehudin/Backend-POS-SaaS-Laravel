<?php

declare(strict_types=1);

namespace App\Modules\Sales\Application\Actions;

use App\Modules\Sales\Application\Exceptions\ApprovalException;
use App\Modules\Sales\Domain\Enums\SensitiveActionApprovalStatus;
use App\Modules\Sales\Domain\Models\SensitiveActionApproval;

final readonly class ConsumeSensitiveActionApproval
{
    public function __construct(private RecordSalesAuditEvent $audit) {}

    public function handle(
        string $tenantId,
        string $outletId,
        string $performerUserId,
        ?string $approvalId,
        string $action,
        string $targetType,
        string $targetId,
        string $requestFingerprint,
    ): SensitiveActionApproval {
        if ($approvalId === null || trim($approvalId) === '') {
            throw ApprovalException::required();
        }

        $approval = SensitiveActionApproval::query()
            ->where('tenant_id', $tenantId)
            ->where('outlet_id', $outletId)
            ->whereKey($approvalId)
            ->lockForUpdate()
            ->first();

        if (! $approval instanceof SensitiveActionApproval) {
            throw ApprovalException::notFound();
        }

        if ($approval->status === SensitiveActionApprovalStatus::Consumed) {
            throw ApprovalException::alreadyConsumed();
        }

        if ($approval->expires_at->isPast()) {
            $approval->forceFill(['status' => SensitiveActionApprovalStatus::Expired])->save();

            $this->audit->handle(
                tenantId: $tenantId,
                outletId: $outletId,
                actorUserId: null,
                eventType: 'approval.expired',
                targetType: 'sales_sensitive_action_approval',
                targetId: $approval->id,
                outcome: 'expired',
                correlationId: $approval->id,
            );

            throw ApprovalException::expired();
        }

        if ($approval->status !== SensitiveActionApprovalStatus::Approved) {
            throw ApprovalException::invalidState();
        }

        if ($approval->performer_user_id !== $performerUserId
            || $approval->action !== $action
            || $approval->target_type !== $targetType
            || $approval->target_id !== $targetId
            || $approval->request_fingerprint !== $requestFingerprint) {
            throw ApprovalException::targetMismatch();
        }

        $approval->forceFill([
            'status' => SensitiveActionApprovalStatus::Consumed,
            'consumed_at' => now(),
        ])->save();

        $this->audit->handle(
            tenantId: $tenantId,
            outletId: $outletId,
            actorUserId: $performerUserId,
            eventType: 'approval.consumed',
            targetType: 'sales_sensitive_action_approval',
            targetId: $approval->id,
            outcome: 'consumed',
            correlationId: $approval->id,
            metadata: [
                'action' => $action,
                'target_type' => $targetType,
                'target_id' => $targetId,
            ],
        );

        return $approval->refresh();
    }
}
