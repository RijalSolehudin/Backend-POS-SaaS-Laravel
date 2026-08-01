<?php

declare(strict_types=1);

namespace App\Modules\Sales\Application\Actions;

use App\Modules\Sales\Application\Exceptions\ApprovalException;
use App\Modules\Sales\Domain\Enums\SensitiveActionApprovalStatus;
use App\Modules\Sales\Domain\Models\SensitiveActionApproval;
use App\Modules\Tenancy\Application\Contracts\SensitiveActionApprovalAuthority;
use Illuminate\Support\Facades\DB;

final readonly class ApproveSensitiveActionApproval
{
    public function __construct(
        private RecordSalesAuditEvent $audit,
        private SensitiveActionApprovalAuthority $authority,
    ) {}

    public function approve(string $tenantId, string $approvalId, string $approverUserId, string $reason): SensitiveActionApproval
    {
        return $this->decide($tenantId, $approvalId, $approverUserId, $reason, true);
    }

    public function reject(string $tenantId, string $approvalId, string $approverUserId, string $reason): SensitiveActionApproval
    {
        return $this->decide($tenantId, $approvalId, $approverUserId, $reason, false);
    }

    private function decide(string $tenantId, string $approvalId, string $approverUserId, string $reason, bool $approved): SensitiveActionApproval
    {
        $reason = trim($reason);

        if ($reason === '') {
            throw ApprovalException::reasonRequired();
        }

        return DB::transaction(function () use ($tenantId, $approvalId, $approverUserId, $reason, $approved): SensitiveActionApproval {
            $approval = SensitiveActionApproval::query()
                ->where('tenant_id', $tenantId)
                ->whereKey($approvalId)
                ->lockForUpdate()
                ->first();

            if (! $approval instanceof SensitiveActionApproval) {
                throw ApprovalException::notFound();
            }

            if ($approval->performer_user_id === $approverUserId) {
                throw ApprovalException::sameActor();
            }

            if ($approval->expires_at->isPast()) {
                $this->expire($approval);

                throw ApprovalException::expired();
            }

            if ($approval->status !== SensitiveActionApprovalStatus::Pending) {
                throw ApprovalException::invalidState();
            }

            if (! $this->authority->canApproveForOutlet($tenantId, $approval->outlet_id, $approverUserId)) {
                throw ApprovalException::forbidden();
            }

            $approval->forceFill([
                'approver_user_id' => $approverUserId,
                'status' => $approved ? SensitiveActionApprovalStatus::Approved : SensitiveActionApprovalStatus::Rejected,
                'decision_reason' => $reason,
                'approved_at' => $approved ? now() : null,
                'rejected_at' => $approved ? null : now(),
            ])->save();

            $this->audit->handle(
                tenantId: $tenantId,
                outletId: $approval->outlet_id,
                actorUserId: $approverUserId,
                eventType: $approved ? 'approval.approved' : 'approval.rejected',
                targetType: 'sales_sensitive_action_approval',
                targetId: $approval->id,
                outcome: $approved ? 'approved' : 'rejected',
                reason: $reason,
                correlationId: $approval->id,
                metadata: [
                    'action' => $approval->action,
                    'target_type' => $approval->target_type,
                    'target_id' => $approval->target_id,
                ],
            );

            return $approval->refresh();
        });
    }

    private function expire(SensitiveActionApproval $approval): void
    {
        if ($approval->status !== SensitiveActionApprovalStatus::Expired) {
            $approval->forceFill(['status' => SensitiveActionApprovalStatus::Expired])->save();
        }

        $this->audit->handle(
            tenantId: $approval->tenant_id,
            outletId: $approval->outlet_id,
            actorUserId: null,
            eventType: 'approval.expired',
            targetType: 'sales_sensitive_action_approval',
            targetId: $approval->id,
            outcome: 'expired',
            correlationId: $approval->id,
        );
    }
}
