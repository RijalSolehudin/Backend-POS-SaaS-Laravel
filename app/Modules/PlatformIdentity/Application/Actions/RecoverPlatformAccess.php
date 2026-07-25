<?php

declare(strict_types=1);

namespace App\Modules\PlatformIdentity\Application\Actions;

use App\Modules\PlatformIdentity\Application\Contracts\PlatformSessionRepository;
use App\Modules\PlatformIdentity\Application\Contracts\SecurityAuditRecorder;
use App\Modules\PlatformIdentity\Application\Data\CliOperatorData;
use App\Modules\PlatformIdentity\Application\Data\SecurityAuditData;
use App\Modules\PlatformIdentity\Application\Exceptions\PlatformIdentityException;
use App\Modules\PlatformIdentity\Application\Services\PlatformPasswordPolicy;
use App\Modules\PlatformIdentity\Domain\Enums\PlatformUserStatus;
use App\Modules\PlatformIdentity\Domain\Models\PlatformRecoveryCode;
use App\Modules\PlatformIdentity\Domain\Models\PlatformUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

final readonly class RecoverPlatformAccess
{
    public function __construct(
        private PlatformPasswordPolicy $passwordPolicy,
        private PlatformSessionRepository $sessions,
        private SecurityAuditRecorder $audit,
    ) {}

    /**
     * @throws PlatformIdentityException
     * @throws ValidationException
     */
    public function handle(
        string $email,
        string $password,
        CliOperatorData $operator,
        string $correlationId,
    ): PlatformUser {
        $this->passwordPolicy->validate($password);
        $existingUser = PlatformUser::query()
            ->where('email', mb_strtolower(trim($email)))
            ->first();

        if (! $existingUser instanceof PlatformUser) {
            $this->audit->record(new SecurityAuditData(
                eventType: 'platform_access.emergency_recovery_failed',
                outcome: 'user_not_found',
                correlationId: $correlationId,
                actorType: 'cli_operator_claim',
                actorId: $operator->identity,
                reason: $operator->reason,
                metadata: [
                    'reference' => $operator->reference,
                    'os_user' => $operator->osUser,
                    'hostname' => $operator->hostname,
                ],
                sendAlert: true,
            ));

            throw PlatformIdentityException::userNotFound();
        }

        return DB::transaction(function () use ($existingUser, $password, $operator, $correlationId): PlatformUser {
            $user = PlatformUser::query()
                ->whereKey($existingUser->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $this->sessions->revokeAll((string) $user->getKey());
            PlatformRecoveryCode::query()
                ->where('platform_user_id', $user->getKey())
                ->delete();

            $user->forceFill([
                'password' => Hash::make($password),
                'password_changed_at' => now(),
                'status' => PlatformUserStatus::PendingMfaSetup,
                'totp_secret' => null,
                'totp_last_used_step' => null,
                'totp_confirmed_at' => null,
            ])->save();

            $this->audit->record(new SecurityAuditData(
                eventType: 'platform_access.emergency_recovered',
                outcome: 'success',
                correlationId: $correlationId,
                actorType: 'cli_operator_claim',
                actorId: $operator->identity,
                subjectType: 'platform_user',
                subjectId: (string) $user->getKey(),
                reason: $operator->reason,
                metadata: [
                    'reference' => $operator->reference,
                    'os_user' => $operator->osUser,
                    'hostname' => $operator->hostname,
                ],
                sendAlert: true,
            ));

            return $user;
        });
    }
}
