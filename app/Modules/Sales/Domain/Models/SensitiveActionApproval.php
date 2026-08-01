<?php

declare(strict_types=1);

namespace App\Modules\Sales\Domain\Models;

use App\Modules\Sales\Domain\Enums\SensitiveActionApprovalStatus;
use App\Shared\Domain\Concerns\HasLowercaseUlids;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $outlet_id
 * @property string $performer_user_id
 * @property string|null $approver_user_id
 * @property string $action
 * @property string $target_type
 * @property string $target_id
 * @property string $request_fingerprint
 * @property SensitiveActionApprovalStatus $status
 * @property string $reason
 * @property string|null $decision_reason
 * @property CarbonImmutable $requested_at
 * @property CarbonImmutable|null $approved_at
 * @property CarbonImmutable|null $rejected_at
 * @property CarbonImmutable|null $consumed_at
 * @property CarbonImmutable $expires_at
 */
final class SensitiveActionApproval extends Model
{
    use HasLowercaseUlids;

    protected $table = 'sales_sensitive_action_approvals';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => SensitiveActionApprovalStatus::class,
            'requested_at' => 'immutable_datetime',
            'approved_at' => 'immutable_datetime',
            'rejected_at' => 'immutable_datetime',
            'consumed_at' => 'immutable_datetime',
            'expires_at' => 'immutable_datetime',
        ];
    }
}
