<?php

declare(strict_types=1);

namespace Tests\Feature\PlatformIdentity;

use App\Modules\PlatformIdentity\Application\Actions\RecoverPlatformAccess;
use App\Modules\PlatformIdentity\Application\Data\CliOperatorData;
use App\Modules\PlatformIdentity\Domain\Enums\PlatformUserStatus;
use App\Modules\PlatformIdentity\Domain\Models\PlatformRecoveryCode;
use App\Modules\PlatformIdentity\Domain\Models\PlatformUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class PlatformEmergencyRecoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_emergency_recovery_revokes_every_factor_and_requires_mfa_enrollment(): void
    {
        config()->set('platform_identity.password.check_compromised', false);
        $user = PlatformUser::query()->create([
            'name' => 'Platform Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('old correct horse battery staple'),
            'status' => PlatformUserStatus::Active,
            'totp_secret' => 'JBSWY3DPEHPK3PXP',
            'totp_confirmed_at' => now(),
            'password_changed_at' => now(),
        ]);
        PlatformRecoveryCode::query()->create([
            'platform_user_id' => $user->getKey(),
            'code_hash' => Hash::make('23456789ABCDEFGH'),
        ]);
        DB::table('platform_sessions')->insert([
            'id' => 'existing-session',
            'user_id' => $user->getKey(),
            'payload' => 'encrypted-payload',
            'last_activity' => now()->getTimestamp(),
            'created_at' => now(),
        ]);

        app(RecoverPlatformAccess::class)->handle(
            email: $user->email,
            password: 'new correct horse battery staple',
            operator: new CliOperatorData(
                identity: 'operator@example.com',
                reason: 'Lost password and authenticator',
                reference: 'INC-002',
                osUser: 'deploy',
                hostname: 'pos-01',
            ),
            correlationId: '01k123456789abcdefghjkmnr',
        );

        $user->refresh();
        self::assertSame(PlatformUserStatus::PendingMfaSetup, $user->status);
        self::assertNull($user->totp_secret);
        self::assertTrue(Hash::check('new correct horse battery staple', $user->password));
        self::assertSame(0, PlatformRecoveryCode::query()->where('platform_user_id', $user->getKey())->count());
        self::assertSame(0, DB::table('platform_sessions')->where('user_id', $user->getKey())->count());
    }
}
