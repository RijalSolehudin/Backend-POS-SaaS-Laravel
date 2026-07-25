<?php

declare(strict_types=1);

namespace App\Modules\PlatformIdentity\Application\Actions;

use App\Modules\PlatformIdentity\Application\Contracts\SecurityAuditRecorder;
use App\Modules\PlatformIdentity\Application\Data\CliOperatorData;
use App\Modules\PlatformIdentity\Application\Data\SecurityAuditData;
use App\Modules\PlatformIdentity\Application\Exceptions\PlatformIdentityException;
use App\Modules\PlatformIdentity\Application\Services\PlatformPasswordPolicy;
use App\Modules\PlatformIdentity\Domain\Enums\PlatformUserStatus;
use App\Modules\PlatformIdentity\Domain\Models\PlatformUser;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

final readonly class BootstrapPlatformAdministrator
{
    public function __construct(
        private PlatformPasswordPolicy $passwordPolicy,
        private SecurityAuditRecorder $audit,
    ) {}

    /**
     * @throws LockTimeoutException
     * @throws PlatformIdentityException
     * @throws ValidationException
     */
    public function handle(
        string $name,
        string $email,
        string $password,
        CliOperatorData $operator,
        string $correlationId,
    ): PlatformUser {
        $this->passwordPolicy->validate($password);

        return Cache::lock('platform-identity:first-bootstrap', 30)->block(5, function () use (
            $name,
            $email,
            $password,
            $operator,
            $correlationId,
        ): PlatformUser {
            if (PlatformUser::query()->exists()) {
                $this->audit->record(new SecurityAuditData(
                    eventType: 'platform_user.bootstrap_rejected',
                    outcome: 'already_completed',
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

                throw PlatformIdentityException::bootstrapAlreadyCompleted();
            }

            return DB::transaction(function () use (
                $name,
                $email,
                $password,
                $operator,
                $correlationId,
            ): PlatformUser {
                $user = PlatformUser::query()->create([
                    'name' => trim($name),
                    'email' => mb_strtolower(trim($email)),
                    'password' => Hash::make($password),
                    'status' => PlatformUserStatus::PendingMfaSetup,
                    'password_changed_at' => now(),
                ]);

                $this->audit->record(new SecurityAuditData(
                    eventType: 'platform_user.bootstrapped',
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
        });
    }
}
