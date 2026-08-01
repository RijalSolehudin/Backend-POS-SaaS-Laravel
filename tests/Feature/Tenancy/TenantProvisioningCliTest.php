<?php

declare(strict_types=1);

namespace Tests\Feature\Tenancy;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\PendingCommand;
use Tests\TestCase;

final class TenantProvisioningCliTest extends TestCase
{
    use RefreshDatabase;

    public function test_controlled_cli_uses_interactive_secret_input_and_provisions_the_same_state(): void
    {
        config()->set('identity.password.check_compromised', false);

        $command = $this->artisan('tenant:provision');
        self::assertInstanceOf(PendingCommand::class, $command);

        $command
            ->expectsQuestion('Operator name or email', 'operator@example.com')
            ->expectsQuestion('Provisioning reason / ticket', 'Pilot onboarding CLI-001')
            ->expectsQuestion('Idempotency key', '01k123456789abcdefghjkmnpq')
            ->expectsQuestion('Tenant name', 'Kopi Nusantara')
            ->expectsQuestion('Tenant code', 'kopi-nusantara')
            ->expectsQuestion('Currency', 'IDR')
            ->expectsQuestion('Timezone', 'Asia/Jakarta')
            ->expectsQuestion('Initial outlet name', 'Main Outlet')
            ->expectsQuestion('Initial outlet code', 'MAIN')
            ->expectsQuestion('Tenant Owner name', 'Tenant Owner')
            ->expectsQuestion('Tenant Owner email', 'owner@example.com')
            ->expectsQuestion('Initial owner password (12-128 characters)', 'tenant owner secure password')
            ->expectsQuestion('Confirm initial owner password', 'tenant owner secure password')
            ->expectsConfirmation('Provision this tenant?', 'yes')
            ->expectsOutputToContain('Tenant provisioned successfully.')
            ->doesntExpectOutput('tenant owner secure password')
            ->assertExitCode(0)
            ->run();

        $this->assertDatabaseHas('tenants', ['code' => 'kopi-nusantara']);
        $this->assertDatabaseHas('users', ['email' => 'owner@example.com']);
        $this->assertDatabaseHas('tenant_memberships', ['membership_type' => 'owner']);
        $this->assertDatabaseHas('user_role_assignments', ['role' => 'tenant_owner']);
    }
}
