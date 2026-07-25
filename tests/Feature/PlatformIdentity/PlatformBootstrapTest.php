<?php

declare(strict_types=1);

namespace Tests\Feature\PlatformIdentity;

use App\Modules\PlatformIdentity\Application\Actions\BootstrapPlatformAdministrator;
use App\Modules\PlatformIdentity\Application\Data\CliOperatorData;
use App\Modules\PlatformIdentity\Application\Exceptions\PlatformIdentityException;
use App\Modules\PlatformIdentity\Domain\Enums\PlatformUserStatus;
use App\Modules\PlatformIdentity\Domain\Models\PlatformUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PlatformBootstrapTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_the_first_platform_administrator_can_be_bootstrapped(): void
    {
        config()->set('platform_identity.password.check_compromised', false);
        $action = app(BootstrapPlatformAdministrator::class);
        $operator = new CliOperatorData(
            identity: 'operator@example.com',
            reason: 'Initial bootstrap',
            reference: 'INC-001',
            osUser: 'deploy',
            hostname: 'pos-01',
        );

        $user = $action->handle(
            name: 'Platform Admin',
            email: 'ADMIN@example.com',
            password: 'correct horse battery staple',
            operator: $operator,
            correlationId: '01k123456789abcdefghjkmnp',
        );

        self::assertSame('admin@example.com', $user->email);
        self::assertSame(PlatformUserStatus::PendingMfaSetup, $user->status);
        self::assertSame(strtolower((string) $user->getKey()), $user->getKey());
        self::assertSame(1, PlatformUser::query()->count());

        $this->expectException(PlatformIdentityException::class);

        $action->handle(
            name: 'Another Admin',
            email: 'other@example.com',
            password: 'another correct horse battery staple',
            operator: $operator,
            correlationId: '01k123456789abcdefghjkmnq',
        );
    }
}
