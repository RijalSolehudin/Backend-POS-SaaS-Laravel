<?php

declare(strict_types=1);

namespace Tests\Feature\Tenancy;

use App\Modules\Identity\Domain\Enums\PredefinedRole;
use App\Modules\Identity\Domain\Models\User;
use App\Modules\Tenancy\Application\Actions\DisableTenant;
use App\Modules\Tenancy\Application\Actions\ProvisionTenant;
use App\Modules\Tenancy\Application\Contracts\TenancyAuditRecorder;
use App\Modules\Tenancy\Application\Data\ProvisionTenantData;
use App\Modules\Tenancy\Application\Data\TenancyAuditData;
use App\Modules\Tenancy\Application\Exceptions\TenantProvisioningException;
use App\Modules\Tenancy\Domain\Enums\TenantStatus;
use App\Modules\Tenancy\Domain\Models\Tenant;
use App\Shared\Application\Context\ActorContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

final class TenantProvisioningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('identity.password.check_compromised', false);
    }

    public function test_provisioning_atomically_creates_the_complete_initial_tenant_state(): void
    {
        $result = app(ProvisionTenant::class)->handle($this->data(), $this->actor());

        self::assertFalse($result->wasReplayed);
        $this->assertDatabaseHas('tenants', [
            'id' => $result->tenantId,
            'code' => 'kopi-nusantara',
            'currency' => 'IDR',
            'timezone' => 'Asia/Jakarta',
            'status' => TenantStatus::Active->value,
        ]);
        $this->assertDatabaseHas('outlets', [
            'id' => $result->outletId,
            'tenant_id' => $result->tenantId,
            'code' => 'MAIN',
        ]);
        $this->assertDatabaseHas('tenant_memberships', [
            'id' => $result->membershipId,
            'tenant_id' => $result->tenantId,
            'user_id' => $result->ownerUserId,
            'membership_type' => 'owner',
        ]);
        $this->assertDatabaseHas('user_role_assignments', [
            'id' => $result->roleAssignmentId,
            'user_id' => $result->ownerUserId,
            'role' => PredefinedRole::TenantOwner->value,
        ]);
        $this->assertDatabaseHas('tenant_provisioning_requests', [
            'idempotency_key' => $this->idempotencyKey,
            'status' => 'succeeded',
            'tenant_id' => $result->tenantId,
        ]);
        $this->assertDatabaseHas('tenancy_audit_events', [
            'event_type' => 'tenant.provisioned',
            'outcome' => 'success',
            'actor_type' => 'platform_user',
            'actor_id' => $this->platformActorId,
            'target_tenant_id' => $result->tenantId,
        ]);

        $owner = User::query()->findOrFail($result->ownerUserId);
        self::assertSame('owner@example.com', $owner->email);
        self::assertTrue($owner->must_change_password);
        self::assertTrue(Hash::check('correct horse battery staple', $owner->password));
    }

    public function test_same_idempotency_key_and_input_returns_the_original_result_without_duplicates(): void
    {
        $action = app(ProvisionTenant::class);
        $first = $action->handle($this->data(), $this->actor());
        $second = $action->handle($this->data(), $this->actor());

        self::assertTrue($second->wasReplayed);
        self::assertSame($first->tenantId, $second->tenantId);
        self::assertSame(1, Tenant::query()->count());
        $this->assertDatabaseCount('outlets', 1);
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('tenant_memberships', 1);
        $this->assertDatabaseCount('user_role_assignments', 1);
        $this->assertDatabaseCount('tenant_provisioning_requests', 1);
        $this->assertDatabaseHas('tenancy_audit_events', [
            'event_type' => 'tenant.provisioning_replayed',
            'target_tenant_id' => $first->tenantId,
        ]);
    }

    public function test_reusing_an_idempotency_key_with_different_input_is_rejected(): void
    {
        $action = app(ProvisionTenant::class);
        $action->handle($this->data(), $this->actor());

        try {
            $action->handle($this->data(tenantName: 'Different Business'), $this->actor());
            self::fail('Expected idempotency mismatch.');
        } catch (TenantProvisioningException $exception) {
            self::assertSame('TENANT_IDEMPOTENCY_MISMATCH', $exception->errorCode());
        }

        self::assertSame(1, Tenant::query()->count());
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseHas('tenancy_audit_events', [
            'event_type' => 'tenant.provisioning_failed',
            'outcome' => 'failure',
        ]);
    }

    public function test_duplicate_owner_email_rolls_back_the_second_tenant_and_outlet(): void
    {
        $action = app(ProvisionTenant::class);
        $action->handle($this->data(), $this->actor());

        try {
            $action->handle($this->data(
                idempotencyKey: strtolower((string) Str::ulid()),
                tenantName: 'Second Business',
                tenantCode: 'second-business',
            ), $this->actor());
            self::fail('Expected duplicate owner email failure.');
        } catch (TenantProvisioningException $exception) {
            self::assertSame('TENANT_OWNER_EMAIL_UNAVAILABLE', $exception->errorCode());
        }

        self::assertSame(1, Tenant::query()->count());
        $this->assertDatabaseCount('outlets', 1);
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('tenant_memberships', 1);
        $this->assertDatabaseCount('tenant_provisioning_requests', 1);
    }

    public function test_failure_at_the_final_audit_stage_rolls_back_every_created_record(): void
    {
        $this->app->instance(TenancyAuditRecorder::class, new class implements TenancyAuditRecorder
        {
            public function record(TenancyAuditData $data): string
            {
                throw new RuntimeException('Simulated final-stage failure.');
            }
        });

        $this->expectException(TenantProvisioningException::class);

        try {
            app(ProvisionTenant::class)->handle($this->data(), $this->actor());
        } finally {
            $this->assertDatabaseCount('tenants', 0);
            $this->assertDatabaseCount('outlets', 0);
            $this->assertDatabaseCount('users', 0);
            $this->assertDatabaseCount('tenant_memberships', 0);
            $this->assertDatabaseCount('user_role_assignments', 0);
            $this->assertDatabaseCount('tenant_provisioning_requests', 0);
        }
    }

    public function test_disable_tenant_is_immediate_audited_and_idempotent(): void
    {
        $provisioned = app(ProvisionTenant::class)->handle($this->data(), $this->actor());
        $action = app(DisableTenant::class);

        $first = $action->handle($provisioned->tenantId, 'Pilot contract has ended.', $this->actor());
        $second = $action->handle($provisioned->tenantId, 'Pilot contract has ended.', $this->actor());

        self::assertTrue($first->wasChanged);
        self::assertFalse($second->wasChanged);
        $tenant = Tenant::query()->findOrFail($provisioned->tenantId);
        self::assertSame(TenantStatus::Disabled, $tenant->status);
        self::assertNotNull($tenant->disabled_at);
        $this->assertDatabaseHas('tenancy_audit_events', [
            'event_type' => 'tenant.disabled',
            'target_tenant_id' => $provisioned->tenantId,
        ]);
        $this->assertDatabaseHas('tenancy_audit_events', [
            'event_type' => 'tenant.disable_replayed',
            'target_tenant_id' => $provisioned->tenantId,
        ]);
    }

    private string $idempotencyKey;

    private string $platformActorId;

    private function data(
        ?string $idempotencyKey = null,
        string $tenantName = 'Kopi Nusantara',
        string $tenantCode = 'kopi-nusantara',
    ): ProvisionTenantData {
        $this->idempotencyKey = $idempotencyKey ?? ($this->idempotencyKey ?? strtolower((string) Str::ulid()));

        return new ProvisionTenantData(
            idempotencyKey: $this->idempotencyKey,
            tenantName: $tenantName,
            tenantCode: $tenantCode,
            outletName: 'Main Outlet',
            outletCode: 'MAIN',
            ownerName: 'Tenant Owner',
            ownerEmail: 'owner@example.com',
            ownerPassword: 'correct horse battery staple',
            currency: 'IDR',
            timezone: 'Asia/Jakarta',
            reason: 'Pilot onboarding',
        );
    }

    private function actor(): ActorContext
    {
        $this->platformActorId ??= strtolower((string) Str::ulid());

        return new ActorContext(
            actorType: 'platform_user',
            actorId: $this->platformActorId,
            correlationId: strtolower((string) Str::ulid()),
        );
    }
}
