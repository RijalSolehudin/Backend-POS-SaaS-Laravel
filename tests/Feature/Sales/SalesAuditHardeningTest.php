<?php

declare(strict_types=1);

namespace Tests\Feature\Sales;

use App\Modules\Identity\Domain\Enums\PredefinedRole;
use App\Modules\Identity\Domain\Models\User;
use App\Modules\Identity\Domain\Models\UserRoleAssignment;
use App\Modules\Sales\Application\Actions\RecordSalesAuditEvent;
use App\Modules\Sales\Domain\Models\SalesAuditEvent;
use App\Modules\Tenancy\Domain\Enums\MembershipType;
use App\Modules\Tenancy\Domain\Enums\OutletStatus;
use App\Modules\Tenancy\Domain\Enums\TenantStatus;
use App\Modules\Tenancy\Domain\Models\Outlet;
use App\Modules\Tenancy\Domain\Models\Tenant;
use App\Modules\Tenancy\Domain\Models\TenantMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

final class SalesAuditHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_audit_recorder_redacts_sensitive_metadata_recursively(): void
    {
        [$tenant, $outlet, $user] = $this->auditContext();

        $eventId = app(RecordSalesAuditEvent::class)->handle(
            tenantId: $tenant->id,
            outletId: $outlet->id,
            actorUserId: $user->id,
            eventType: 'audit.redaction.checked',
            targetType: 'sales_order',
            targetId: '01k123456789abcdefghjkmnpq',
            outcome: 'checked',
            reason: 'Audit hardening check',
            correlationId: 'audit-redaction-test',
            metadata: [
                'safe_note' => 'visible',
                'password' => 'plain-password',
                'api_token' => 'secret-token',
                'nested' => [
                    'card_number' => '4111111111111111',
                    'secret_value' => 'hidden',
                    'safe_amount_minor' => 26000,
                ],
            ],
        );

        $event = SalesAuditEvent::query()->findOrFail($eventId);

        self::assertSame('audit.redaction.checked', $event->event_type);
        self::assertSame($tenant->id, $event->tenant_id);
        self::assertSame($outlet->id, $event->outlet_id);
        self::assertSame($user->id, $event->actor_user_id);
        self::assertSame('audit-redaction-test', $event->correlation_id);

        $metadata = $event->metadata;
        self::assertIsArray($metadata);
        self::assertSame('visible', $metadata['safe_note']);
        self::assertSame('[REDACTED]', $metadata['password']);
        self::assertSame('[REDACTED]', $metadata['api_token']);

        $nested = $metadata['nested'] ?? null;
        self::assertIsArray($nested);
        self::assertSame('[REDACTED]', $nested['card_number']);
        self::assertSame('[REDACTED]', $nested['secret_value']);
        self::assertSame(26000, $nested['safe_amount_minor']);
    }

    public function test_sales_audit_retention_command_prunes_only_events_past_retention(): void
    {
        [$tenant, $outlet, $user] = $this->auditContext();

        SalesAuditEvent::query()->create([
            'tenant_id' => $tenant->id,
            'outlet_id' => $outlet->id,
            'actor_user_id' => $user->id,
            'event_type' => 'audit.old',
            'target_type' => 'sales_order',
            'target_id' => '01k123456789abcdefghjkmnpq',
            'outcome' => 'recorded',
            'reason' => 'Old event',
            'correlation_id' => 'old-event',
            'occurred_at' => now()->subYears(3),
        ]);
        SalesAuditEvent::query()->create([
            'tenant_id' => $tenant->id,
            'outlet_id' => $outlet->id,
            'actor_user_id' => $user->id,
            'event_type' => 'audit.recent',
            'target_type' => 'sales_order',
            'target_id' => '01k223456789abcdefghjkmnpq',
            'outcome' => 'recorded',
            'reason' => 'Recent event',
            'correlation_id' => 'recent-event',
            'occurred_at' => now()->subYear(),
        ]);

        self::assertSame(0, Artisan::call('sales:prune-audit-events', ['--pretend' => true]));
        self::assertStringContainsString('1 Sales audit event(s) are older than 2 year(s).', Artisan::output());
        self::assertSame(2, SalesAuditEvent::query()->count());

        self::assertSame(0, Artisan::call('sales:prune-audit-events'));

        $this->assertDatabaseMissing('sales_audit_events', ['event_type' => 'audit.old']);
        $this->assertDatabaseHas('sales_audit_events', ['event_type' => 'audit.recent']);
    }

    /**
     * @return array{Tenant, Outlet, User}
     */
    private function auditContext(): array
    {
        $tenant = Tenant::query()->create([
            'name' => 'Tenant One',
            'code' => 'tenant-one',
            'currency' => 'IDR',
            'timezone' => 'Asia/Jakarta',
            'status' => TenantStatus::Active,
        ]);
        $user = User::factory()->create(['email' => 'owner@example.com']);
        TenantMembership::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'membership_type' => MembershipType::Owner,
        ]);
        UserRoleAssignment::query()->create([
            'user_id' => $user->id,
            'role' => PredefinedRole::TenantOwner,
        ]);
        $outlet = Outlet::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Main Outlet',
            'code' => 'MAIN',
            'status' => OutletStatus::Active,
        ]);

        return [$tenant, $outlet, $user];
    }
}
