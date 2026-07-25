<?php

declare(strict_types=1);

namespace Tests\Feature\PlatformIdentity;

use App\Modules\PlatformIdentity\Domain\Enums\PlatformUserStatus;
use App\Modules\PlatformIdentity\Domain\Models\PlatformRecoveryCode;
use App\Modules\PlatformIdentity\Domain\Models\PlatformUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class PlatformRecoveryCodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_recovery_code_is_single_use_and_authenticates_one_login(): void
    {
        $user = $this->platformUser();
        $recoveryCode = '2345-6789-ABCD-EFGH';
        $storedCode = PlatformRecoveryCode::query()->create([
            'platform_user_id' => $user->getKey(),
            'code_hash' => Hash::make(str_replace('-', '', $recoveryCode)),
        ]);

        $this->post(route('platform.login.store'), [
            'email' => $user->email,
            'password' => 'correct horse battery staple',
        ])->assertRedirect(route('platform.mfa.challenge'));

        $this->post(route('platform.mfa.challenge.store'), [
            'code' => $recoveryCode,
        ])->assertRedirect(route('platform.security'));

        self::assertNotNull($storedCode->fresh()?->used_at);
        $this->assertAuthenticatedAs($user, 'platform');
    }

    private function platformUser(): PlatformUser
    {
        return PlatformUser::query()->create([
            'name' => 'Platform Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('correct horse battery staple'),
            'status' => PlatformUserStatus::Active,
            'totp_secret' => 'JBSWY3DPEHPK3PXP',
            'totp_confirmed_at' => now(),
            'password_changed_at' => now(),
        ]);
    }
}
