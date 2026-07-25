<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Application\Actions;

use App\Modules\Identity\Application\Contracts\InitialTenantOwnerCreator;
use App\Modules\Identity\Application\Data\InitialTenantOwnerData;
use App\Modules\Identity\Application\Exceptions\IdentityException;
use App\Modules\Tenancy\Application\Contracts\TenancyAuditRecorder;
use App\Modules\Tenancy\Application\Data\ProvisionTenantData;
use App\Modules\Tenancy\Application\Data\ProvisionTenantResult;
use App\Modules\Tenancy\Application\Data\TenancyAuditData;
use App\Modules\Tenancy\Application\Exceptions\TenantProvisioningException;
use App\Modules\Tenancy\Application\Services\ProvisioningFingerprint;
use App\Modules\Tenancy\Application\Services\ProvisionTenantValidator;
use App\Modules\Tenancy\Domain\Enums\MembershipType;
use App\Modules\Tenancy\Domain\Enums\OutletStatus;
use App\Modules\Tenancy\Domain\Enums\ProvisioningStatus;
use App\Modules\Tenancy\Domain\Enums\TenantStatus;
use App\Modules\Tenancy\Domain\Models\Outlet;
use App\Modules\Tenancy\Domain\Models\Tenant;
use App\Modules\Tenancy\Domain\Models\TenantMembership;
use App\Modules\Tenancy\Domain\Models\TenantProvisioningRequest;
use App\Shared\Application\Context\ActorContext;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

final readonly class ProvisionTenant
{
    public function __construct(
        private InitialTenantOwnerCreator $ownerCreator,
        private ProvisionTenantValidator $validator,
        private ProvisioningFingerprint $fingerprint,
        private TenancyAuditRecorder $audit,
    ) {}

    /**
     * @throws TenantProvisioningException
     * @throws ValidationException
     */
    public function handle(ProvisionTenantData $data, ActorContext $actor): ProvisionTenantResult
    {
        $data = $this->normalize($data);
        $this->validator->validate($data);
        $fingerprint = $this->fingerprint->for($data);

        try {
            $result = DB::transaction(
                fn (): ProvisionTenantResult => $this->provisionWithinTransaction($data, $actor, $fingerprint),
                attempts: 3,
            );
        } catch (ValidationException $exception) {
            $this->recordFailure($data, $actor, 'validation_failed');

            throw $exception;
        } catch (TenantProvisioningException $exception) {
            $this->recordFailure($data, $actor, $exception->errorCode());

            throw $exception;
        } catch (QueryException $exception) {
            $failure = TenantProvisioningException::conflict($exception);
            $this->recordFailure($data, $actor, $failure->errorCode());

            throw $failure;
        } catch (Throwable $exception) {
            $failure = TenantProvisioningException::failed($actor->correlationId, $exception);
            $this->recordFailure($data, $actor, $failure->errorCode());

            throw $failure;
        }

        if ($result->wasReplayed) {
            $this->audit->record(new TenancyAuditData(
                eventType: 'tenant.provisioning_replayed',
                outcome: 'success',
                actorType: $actor->actorType,
                actorId: $actor->actorId,
                correlationId: $actor->correlationId,
                targetTenantId: $result->tenantId,
                reason: $data->reason,
                metadata: [
                    'tenant_code' => $data->tenantCode,
                    'idempotency_key' => $data->idempotencyKey,
                ],
            ));
        }

        return $result;
    }

    private function provisionWithinTransaction(
        ProvisionTenantData $data,
        ActorContext $actor,
        string $fingerprint,
    ): ProvisionTenantResult {
        $requestId = strtolower((string) Str::ulid());
        $now = now();
        $inserted = DB::table('tenant_provisioning_requests')->insertOrIgnore([
            'id' => $requestId,
            'idempotency_key' => $data->idempotencyKey,
            'input_hash' => $fingerprint,
            'status' => ProvisioningStatus::Processing->value,
            'correlation_id' => $actor->correlationId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $request = TenantProvisioningRequest::query()
            ->where('idempotency_key', $data->idempotencyKey)
            ->lockForUpdate()
            ->first();

        if (! $request instanceof TenantProvisioningRequest) {
            throw TenantProvisioningException::failed($actor->correlationId);
        }

        if (! hash_equals((string) $request->input_hash, $fingerprint)) {
            throw TenantProvisioningException::idempotencyMismatch();
        }

        if ($inserted === 0) {
            if (
                $request->status !== ProvisioningStatus::Succeeded
                || $request->tenant_id === null
                || $request->outlet_id === null
                || $request->owner_user_id === null
                || $request->membership_id === null
                || $request->role_assignment_id === null
            ) {
                throw TenantProvisioningException::provisioningInProgress();
            }

            return new ProvisionTenantResult(
                tenantId: (string) $request->tenant_id,
                outletId: (string) $request->outlet_id,
                ownerUserId: (string) $request->owner_user_id,
                membershipId: (string) $request->membership_id,
                roleAssignmentId: (string) $request->role_assignment_id,
                ownerEmail: $data->ownerEmail,
                wasReplayed: true,
            );
        }

        if (Tenant::query()->where('code', $data->tenantCode)->exists()) {
            throw TenantProvisioningException::tenantCodeUnavailable();
        }

        $tenant = Tenant::query()->create([
            'name' => $data->tenantName,
            'code' => $data->tenantCode,
            'currency' => $data->currency,
            'timezone' => $data->timezone,
            'status' => TenantStatus::Active,
        ]);

        $outlet = Outlet::query()->create([
            'tenant_id' => $tenant->getKey(),
            'name' => $data->outletName,
            'code' => $data->outletCode,
            'status' => OutletStatus::Active,
        ]);

        try {
            $owner = $this->ownerCreator->handle(new InitialTenantOwnerData(
                name: $data->ownerName,
                email: $data->ownerEmail,
                password: $data->ownerPassword,
            ));
        } catch (IdentityException $exception) {
            if ($exception->errorCode() === 'IDENTITY_EMAIL_UNAVAILABLE') {
                throw TenantProvisioningException::ownerEmailUnavailable();
            }

            throw $exception;
        }

        $membership = TenantMembership::query()->create([
            'tenant_id' => $tenant->getKey(),
            'user_id' => $owner->userId,
            'membership_type' => MembershipType::Owner,
        ]);

        $request->forceFill([
            'status' => ProvisioningStatus::Succeeded,
            'tenant_id' => $tenant->getKey(),
            'outlet_id' => $outlet->getKey(),
            'owner_user_id' => $owner->userId,
            'membership_id' => $membership->getKey(),
            'role_assignment_id' => $owner->roleAssignmentId,
            'completed_at' => now(),
        ])->save();

        $this->audit->record(new TenancyAuditData(
            eventType: 'tenant.provisioned',
            outcome: 'success',
            actorType: $actor->actorType,
            actorId: $actor->actorId,
            correlationId: $actor->correlationId,
            targetTenantId: (string) $tenant->getKey(),
            reason: $data->reason,
            metadata: [
                'tenant_code' => $data->tenantCode,
                'outlet_id' => (string) $outlet->getKey(),
                'owner_user_id' => $owner->userId,
                'idempotency_key' => $data->idempotencyKey,
            ],
        ));

        return new ProvisionTenantResult(
            tenantId: (string) $tenant->getKey(),
            outletId: (string) $outlet->getKey(),
            ownerUserId: $owner->userId,
            membershipId: (string) $membership->getKey(),
            roleAssignmentId: $owner->roleAssignmentId,
            ownerEmail: $owner->normalizedEmail,
            wasReplayed: false,
        );
    }

    private function normalize(ProvisionTenantData $data): ProvisionTenantData
    {
        return new ProvisionTenantData(
            idempotencyKey: mb_strtolower(trim($data->idempotencyKey)),
            tenantName: trim($data->tenantName),
            tenantCode: mb_strtolower(trim($data->tenantCode)),
            outletName: trim($data->outletName),
            outletCode: mb_strtoupper(trim($data->outletCode)),
            ownerName: trim($data->ownerName),
            ownerEmail: mb_strtolower(trim($data->ownerEmail)),
            ownerPassword: $data->ownerPassword,
            currency: mb_strtoupper(trim($data->currency)),
            timezone: trim($data->timezone),
            reason: trim($data->reason),
        );
    }

    private function recordFailure(
        ProvisionTenantData $data,
        ActorContext $actor,
        string $failureCode,
    ): void {
        try {
            $this->audit->record(new TenancyAuditData(
                eventType: 'tenant.provisioning_failed',
                outcome: 'failure',
                actorType: $actor->actorType,
                actorId: $actor->actorId,
                correlationId: $actor->correlationId,
                reason: $data->reason,
                metadata: [
                    'tenant_code' => $data->tenantCode,
                    'idempotency_key' => $data->idempotencyKey,
                    'failure_code' => $failureCode,
                ],
            ));
        } catch (Throwable $auditFailure) {
            report($auditFailure);
        }
    }
}
