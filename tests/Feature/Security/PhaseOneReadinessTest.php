<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Modules\Identity\Domain\Enums\PredefinedRole;
use App\Modules\Identity\Domain\Models\User;
use App\Modules\Identity\Domain\Models\UserRoleAssignment;
use App\Modules\PlatformIdentity\Application\Contracts\SecurityAuditRecorder;
use App\Modules\PlatformIdentity\Application\Data\SecurityAuditData;
use App\Modules\Tenancy\Application\Contracts\TenancyAuditRecorder;
use App\Modules\Tenancy\Application\Data\TenancyAuditData;
use App\Modules\Tenancy\Domain\Enums\MembershipType;
use App\Modules\Tenancy\Domain\Enums\TenantStatus;
use App\Modules\Tenancy\Domain\Models\Tenant;
use App\Modules\Tenancy\Domain\Models\TenantMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class PhaseOneReadinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_recorders_redact_sensitive_metadata(): void
    {
        app(SecurityAuditRecorder::class)->record(new SecurityAuditData(
            eventType: 'security.test',
            outcome: 'succeeded',
            correlationId: 'security-redaction-platform',
            metadata: [
                'plain_token' => 'token-value',
                'totp_secret' => 'secret-value',
                'safe_count' => 2,
            ],
        ));

        $tenant = $this->tenant();
        app(TenancyAuditRecorder::class)->record(new TenancyAuditData(
            eventType: 'tenancy.test',
            outcome: 'succeeded',
            actorType: 'tenant_user',
            actorId: 'actor-1',
            correlationId: 'security-redaction-tenant',
            targetTenantId: $tenant->id,
            metadata: [
                'initial_password' => 'password-value',
                'sql_detail' => 'select secret',
                'safe_count' => 3,
            ],
        ));

        $platformMetadata = DB::table('platform_security_audit_events')
            ->where('correlation_id', 'security-redaction-platform')
            ->value('metadata');
        $tenancyMetadata = DB::table('tenancy_audit_events')
            ->where('correlation_id', 'security-redaction-tenant')
            ->value('metadata');

        self::assertJson($platformMetadata);
        self::assertJson($tenancyMetadata);
        self::assertSame('[redacted]', json_decode($platformMetadata, true, flags: JSON_THROW_ON_ERROR)['plain_token']);
        self::assertSame('[redacted]', json_decode($platformMetadata, true, flags: JSON_THROW_ON_ERROR)['totp_secret']);
        self::assertSame(2, json_decode($platformMetadata, true, flags: JSON_THROW_ON_ERROR)['safe_count']);
        self::assertSame('[redacted]', json_decode($tenancyMetadata, true, flags: JSON_THROW_ON_ERROR)['initial_password']);
        self::assertSame('[redacted]', json_decode($tenancyMetadata, true, flags: JSON_THROW_ON_ERROR)['sql_detail']);
        self::assertSame(3, json_decode($tenancyMetadata, true, flags: JSON_THROW_ON_ERROR)['safe_count']);
    }

    public function test_tenant_owner_role_does_not_grant_platform_access(): void
    {
        $tenant = $this->tenant();
        $owner = $this->tenantUser($tenant, MembershipType::Owner, PredefinedRole::TenantOwner);

        $this->actingAs($owner, 'web')
            ->get('/platform/tenants')
            ->assertRedirect('/platform/login');
    }

    public function test_tenant_context_rejects_other_tenant_route_without_disclosure(): void
    {
        $tenant = $this->tenant();
        $otherTenant = $this->tenant('Other Tenant', 'other-tenant');
        $owner = $this->tenantUser($tenant, MembershipType::Owner, PredefinedRole::TenantOwner);

        $this->actingAs($owner, 'web')->withSession([
            'tenant.authenticated_at' => now()->getTimestamp(),
            'tenant.last_activity_at' => now()->getTimestamp(),
        ]);

        $this->get(route('tenant.home', ['tenant' => $otherTenant->id]))
            ->assertNotFound();
    }

    public function test_api_problem_details_do_not_echo_credentials(): void
    {
        config(['app.debug' => false]);

        $this->postJson(route('api.v1.pos.auth.login'), [
            'email' => 'missing@example.com',
            'password' => 'SuperSecretPasswordValue',
            'installation_id' => '01k123456789abcdefghjkmnpq',
            'outlet_id' => '01k123456789abcdefghjkmnpr',
        ])
            ->assertUnauthorized()
            ->assertHeader('Content-Type', 'application/problem+json')
            ->assertJsonPath('code', 'IDENTITY_INVALID_CREDENTIALS')
            ->assertDontSee('SuperSecretPasswordValue')
            ->assertDontSee('Trace')
            ->assertDontSee('SQLSTATE');
    }

    public function test_expired_sanctum_token_cleanup_prunes_only_retained_expired_tokens(): void
    {
        $user = User::factory()->create();

        DB::table('personal_access_tokens')->insert([
            [
                'tokenable_type' => User::class,
                'tokenable_id' => $user->id,
                'name' => 'expired-old',
                'token' => hash('sha256', 'expired-old'),
                'abilities' => json_encode(['pos:*']),
                'expires_at' => now()->subDays(2),
                'created_at' => now()->subDays(3),
                'updated_at' => now()->subDays(3),
            ],
            [
                'tokenable_type' => User::class,
                'tokenable_id' => $user->id,
                'name' => 'expired-recent',
                'token' => hash('sha256', 'expired-recent'),
                'abilities' => json_encode(['pos:*']),
                'expires_at' => now()->subHour(),
                'created_at' => now()->subHours(2),
                'updated_at' => now()->subHours(2),
            ],
            [
                'tokenable_type' => User::class,
                'tokenable_id' => $user->id,
                'name' => 'active',
                'token' => hash('sha256', 'active'),
                'abilities' => json_encode(['pos:*']),
                'expires_at' => now()->addDay(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        Artisan::call('sanctum:prune-expired', ['--hours' => 24]);

        $this->assertDatabaseMissing('personal_access_tokens', ['name' => 'expired-old']);
        $this->assertDatabaseHas('personal_access_tokens', ['name' => 'expired-recent']);
        $this->assertDatabaseHas('personal_access_tokens', ['name' => 'active']);
    }

    private function tenant(string $name = 'Tenant One', string $code = 'tenant-one'): Tenant
    {
        return Tenant::query()->create([
            'name' => $name,
            'code' => $code,
            'currency' => 'IDR',
            'timezone' => 'Asia/Jakarta',
            'status' => TenantStatus::Active,
        ]);
    }

    private function tenantUser(Tenant $tenant, MembershipType $membershipType, PredefinedRole $role): User
    {
        $user = User::factory()->create();
        TenantMembership::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'membership_type' => $membershipType,
        ]);
        UserRoleAssignment::query()->create([
            'user_id' => $user->id,
            'role' => $role,
        ]);

        return $user;
    }
}
