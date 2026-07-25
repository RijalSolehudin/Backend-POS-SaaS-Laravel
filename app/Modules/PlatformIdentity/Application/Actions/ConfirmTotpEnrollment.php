<?php

declare(strict_types=1);

namespace App\Modules\PlatformIdentity\Application\Actions;

use App\Modules\PlatformIdentity\Application\Contracts\RecoveryCodeGenerator;
use App\Modules\PlatformIdentity\Application\Contracts\SecurityAuditRecorder;
use App\Modules\PlatformIdentity\Application\Contracts\TotpAuthenticator;
use App\Modules\PlatformIdentity\Application\Data\SecurityAuditData;
use App\Modules\PlatformIdentity\Application\Exceptions\PlatformIdentityException;
use App\Modules\PlatformIdentity\Domain\Enums\PlatformUserStatus;
use App\Modules\PlatformIdentity\Domain\Models\PlatformRecoveryCode;
use App\Modules\PlatformIdentity\Domain\Models\PlatformUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

final readonly class ConfirmTotpEnrollment
{
    public function __construct(
        private TotpAuthenticator $totp,
        private RecoveryCodeGenerator $recoveryCodes,
        private SecurityAuditRecorder $audit,
    ) {}

    /**
     * @return list<string>
     */
    public function handle(
        string $platformUserId,
        string $secret,
        string $code,
        SecurityAuditData $auditData,
    ): array {
        return DB::transaction(function () use ($platformUserId, $secret, $code, $auditData): array {
            $user = PlatformUser::query()->lockForUpdate()->findOrFail($platformUserId);
            $step = $this->totp->matchingTimeStep($secret, $code);

            if ($step === null) {
                throw PlatformIdentityException::invalidSecondFactor();
            }

            $codes = $this->recoveryCodes->generateSet();

            PlatformRecoveryCode::query()
                ->where('platform_user_id', $user->getKey())
                ->delete();

            foreach ($codes as $recoveryCode) {
                PlatformRecoveryCode::query()->create([
                    'platform_user_id' => $user->getKey(),
                    'code_hash' => Hash::make($this->normalizeRecoveryCode($recoveryCode)),
                ]);
            }

            $user->forceFill([
                'totp_secret' => $secret,
                'totp_last_used_step' => $step,
                'totp_confirmed_at' => now(),
                'status' => PlatformUserStatus::Active,
            ])->save();

            $this->audit->record($auditData);

            return $codes;
        });
    }

    private function normalizeRecoveryCode(string $code): string
    {
        return str_replace('-', '', mb_strtoupper(trim($code)));
    }
}
