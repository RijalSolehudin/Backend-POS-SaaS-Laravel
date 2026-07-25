<?php

declare(strict_types=1);

namespace App\Modules\PlatformIdentity\Application\Actions;

use App\Modules\PlatformIdentity\Application\Contracts\TotpAuthenticator;
use App\Modules\PlatformIdentity\Domain\Enums\SecondFactorMethod;
use App\Modules\PlatformIdentity\Domain\Models\PlatformRecoveryCode;
use App\Modules\PlatformIdentity\Domain\Models\PlatformUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

final readonly class VerifyPlatformSecondFactor
{
    public function __construct(private TotpAuthenticator $totp) {}

    public function handle(string $platformUserId, string $code): ?SecondFactorMethod
    {
        return DB::transaction(function () use ($platformUserId, $code): ?SecondFactorMethod {
            $user = PlatformUser::query()->lockForUpdate()->find($platformUserId);

            if (! $user instanceof PlatformUser || ! is_string($user->totp_secret)) {
                return null;
            }

            $step = $this->totp->matchingTimeStep($user->totp_secret, trim($code));

            if ($step !== null && ($user->totp_last_used_step === null || $step > (int) $user->totp_last_used_step)) {
                $user->forceFill(['totp_last_used_step' => $step])->save();

                return SecondFactorMethod::Totp;
            }

            $normalizedCode = str_replace('-', '', mb_strtoupper(trim($code)));
            $recoveryCodes = PlatformRecoveryCode::query()
                ->where('platform_user_id', $platformUserId)
                ->whereNull('used_at')
                ->lockForUpdate()
                ->get();

            foreach ($recoveryCodes as $recoveryCode) {
                if (Hash::check($normalizedCode, $recoveryCode->code_hash)) {
                    $recoveryCode->forceFill(['used_at' => now()])->save();

                    return SecondFactorMethod::RecoveryCode;
                }
            }

            return null;
        });
    }
}
