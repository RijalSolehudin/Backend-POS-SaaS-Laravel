<?php

declare(strict_types=1);

namespace App\Modules\PlatformIdentity\Application\Actions;

use App\Modules\PlatformIdentity\Application\Contracts\RecoveryCodeGenerator;
use App\Modules\PlatformIdentity\Application\Contracts\SecurityAuditRecorder;
use App\Modules\PlatformIdentity\Application\Data\SecurityAuditData;
use App\Modules\PlatformIdentity\Domain\Models\PlatformRecoveryCode;
use App\Modules\PlatformIdentity\Domain\Models\PlatformUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

final readonly class RegeneratePlatformRecoveryCodes
{
    public function __construct(
        private RecoveryCodeGenerator $recoveryCodes,
        private SecurityAuditRecorder $audit,
    ) {}

    /**
     * @return list<string>
     */
    public function handle(PlatformUser $user, SecurityAuditData $auditData): array
    {
        return DB::transaction(function () use ($user, $auditData): array {
            PlatformRecoveryCode::query()
                ->where('platform_user_id', $user->getKey())
                ->delete();

            $codes = $this->recoveryCodes->generateSet();

            foreach ($codes as $code) {
                PlatformRecoveryCode::query()->create([
                    'platform_user_id' => $user->getKey(),
                    'code_hash' => Hash::make(str_replace('-', '', $code)),
                ]);
            }

            $this->audit->record($auditData);

            return $codes;
        });
    }
}
